<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for attaching existing evidence to a question.
 */
class AttachEvidenceForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AttachEvidenceForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'attach_evidence_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $audit = NULL, $audit_question = NULL) {
    // Check if entities are valid
    if (!$audit || !$audit_question) {
      $form['error'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Invalid audit or question specified.') . '</p>',
      ];
      return $form;
    }



    // Store entities in form state
    $form_state->set('audit', $audit);
    $form_state->set('audit_question', $audit_question);
    // Get all available evidence for this audit that are not attached to the specific question
    $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');

    // Query to get evidences that belong to this audit but are not attached to this specific question
    $query = $evidence_storage->getQuery()
      ->condition('field_audit', $audit->id())
      ->accessCheck(TRUE);

    // Get all evidences for this audit
    $all_evidence_ids = $query->execute();

    // Now filter out those that are already attached to this question using a separate query
    $query = $evidence_storage->getQuery()
      ->condition('field_audit', $audit->id())
      ->condition('field_audit_question.target_id', $audit_question->id())
      ->accessCheck(TRUE);

    $attached_evidence_ids = $query->execute();

    // Get the unattached evidence IDs
    $unattached_evidence_ids = array_diff($all_evidence_ids, $attached_evidence_ids);

    // Load the unattached evidences
    $existing_evidences = $evidence_storage->loadMultiple($unattached_evidence_ids);

    // Prepare options for select
    $options = [];
    if ($existing_evidences) {
      foreach ($existing_evidences as $evidence) {

        // Get evidence description
        $description = '';
        if ($evidence->hasField('field_evidence') && !$evidence->get('field_evidence')->isEmpty()) {
          $description = $evidence->get('field_evidence')->value;
        }

        // Get supporting files if no description
        $files_list = '';
        if (empty($description) && $evidence->hasField('field_supporting_files') && !$evidence->get('field_supporting_files')->isEmpty()) {
          $file_references = $evidence->get('field_supporting_files')->referencedEntities();
          if (!empty($file_references)) {
            $file_names = [];
            foreach ($file_references as $file) {
              $file_names[] = $file->getFilename();
            }
            $files_list = implode(', ', $file_names);
          }
        }

        // Build the display label in format "label" with additional info
        $label_parts = [];

        // Add the main label
        $main_label = $evidence->label();
        if (!empty($main_label)) {
          $label_parts[] = $main_label;
        }

        $label = implode(' - ', $label_parts);
        $options[$evidence->id()] = $label;
      }
    }

    if (empty($options)) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('No existing evidence available to attach.') . '</p>',
      ];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['close'] = [
        '#type' => 'submit',
        '#value' => $this->t('Close'),
        '#attributes' => [
          'class' => ['dialog-cancel'],
        ],
      ];

      return $form;
    }

    $form['evidence_to_attach'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Evidence to Attach'),
      '#description' => $this->t('Choose from existing evidence to attach to this question. You can select multiple evidences.'),
      '#options' => $options,
      '#default_value' => [],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['attach'] = [
      '#type' => 'submit',
      '#value' => $this->t('Attach Evidence'),
      '#ajax' => [
        'callback' => '::attachEvidenceCallback',
        'effect' => 'fade',
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['dialog-cancel'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This is handled in the AJAX callback
  }

  /**
   * AJAX callback for attaching evidence.
   */
  public function attachEvidenceCallback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $audit = $form_state->get('audit');
    $audit_question = $form_state->get('audit_question');
    $selected_evidences = array_filter($form_state->getValue('evidence_to_attach')); // Filter out unchecked items

    if (!$audit || !$audit_question || empty($selected_evidences)) {
      $response->addCommand(new MessageCommand($this->t('Invalid parameters.'), NULL, ['type' => 'error']));
      return $response;
    }

    // Load the evidences to attach
    $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');
    $success_count = 0;

    foreach ($selected_evidences as $evidence_id) {
      $evidence = $evidence_storage->load($evidence_id);

      if (!$evidence) {
        $response->addCommand(new MessageCommand($this->t('Some selected evidences could not be found.'), NULL, ['type' => 'error']));
        continue; // Skip to next evidence
      }

      // Add the current question to the evidence's question references (append, don't replace)
      $existing_questions = $evidence->get('field_audit_question')->getValue();
      $new_question_id = $audit_question->id();

      // Check if the question is already attached to avoid duplicates
      $already_attached = FALSE;
      foreach ($existing_questions as $existing_ref) {
        if ($existing_ref['target_id'] == $new_question_id) {
          $already_attached = TRUE;
          break;
        }
      }

      if (!$already_attached) {
        $existing_questions[] = ['target_id' => $new_question_id];
        $evidence->set('field_audit_question', $existing_questions);
      }
      $evidence->save();
      $success_count++;
    }

    if ($success_count > 0) {
      $message = $this->formatPlural($success_count, '1 evidence attached successfully.', '@count evidences attached successfully.');
      $response->addCommand(new MessageCommand($message, NULL, ['type' => 'status']));
    }

    // Close modal and reload the parent page to show the newly attached evidence
    $response->addCommand(new CloseDialogCommand('#drupal-modal'));

    // Get the audit ID from form state to redirect to the correct page
    $audit = $form_state->get('audit');
    if ($audit) {
      // Redirect to the audit page to show the updated evidence list
      $response->addCommand(new RedirectCommand('/node/' . $audit->id()));
    } else {
      // Fallback redirect to front page if no audit is available
      $response->addCommand(new RedirectCommand('<front>'));
    }

    return $response;
  }

  /**
   * Validation for the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_evidences = array_filter($form_state->getValue('evidence_to_attach')); // Filter out unchecked items

    if (!empty($selected_evidences)) {
      $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');
      foreach ($selected_evidences as $evidence_id) {
        $evidence = $evidence_storage->load($evidence_id);
        if (!$evidence) {
          $form_state->setErrorByName('evidence_to_attach', $this->t('One or more selected evidences do not exist.'));
          break; // Exit on first error
        }
      }
    }
  }
}
