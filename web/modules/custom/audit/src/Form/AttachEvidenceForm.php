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

    // Get all available evidence for this audit
    $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');
    $query = $evidence_storage->getQuery()
      ->condition('field_audit', $audit->id())
      ->accessCheck(TRUE);

    $evidence_ids = $query->execute();
    $existing_evidences = $evidence_storage->loadMultiple($evidence_ids);

    // Filter out evidences that are already attached to this question
    $attached_evidence_ids = [];
    if ($existing_evidences) {
      foreach ($existing_evidences as $evidence) {
        $question_ref = $evidence->get('field_audit_question')->referencedEntities();
        if (!empty($question_ref)) {
          $ref_question = reset($question_ref);
          if ($ref_question && $ref_question->id() == $audit_question->id()) {
            $attached_evidence_ids[] = $evidence->id();
          }
        }
      }
    }

    // Prepare options for select
    $options = [];
    if ($existing_evidences) {
      foreach ($existing_evidences as $evidence) {
        // Skip evidence that's already attached to this question
        if (in_array($evidence->id(), $attached_evidence_ids)) {
          continue;
        }


        // Get the evidence number
        $evidence_number = '';
        if ($evidence->hasField('field_evidence_number') && !$evidence->get('field_evidence_number')->isEmpty()) {
          $evidence_number = $evidence->get('field_evidence_number')->value;
        }
        
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
        
        // Build the display label in format "evidence_number - label" with additional info
        $label_parts = [];
        
        // Add evidence number if available
        if (!empty($evidence_number)) {
          $label_parts[] = $evidence_number;
        } else {
          // Fallback to ID if no evidence number
          $label_parts[] = $this->t('Evidence @id', ['@id' => $evidence->id()]);
        }
        
        // Add the main label
        $main_label = $evidence->label();
        if (!empty($main_label)) {
          $label_parts[] = $main_label;
        }
        
        // Add either description snippet or files list as additional context
        if (!empty($description)) {
          // Limit description to 140 characters
          $description_snippet = strlen($description) > 140 ? substr($description, 0, 140) . '...' : $description;
          $label_parts[] = $description_snippet;
        } elseif (!empty($files_list)) {
          $label_parts[] = $files_list;
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
