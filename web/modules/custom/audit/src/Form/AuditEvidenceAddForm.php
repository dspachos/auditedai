<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\audit\Entity\AuditQuestion;
use Drupal\views\Plugin\views\field\Boolean;

/**
 * Form controller for adding audit evidence.
 */
final class AuditEvidenceAddForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_evidence_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $audit = NULL, ?AuditQuestion $audit_question = NULL): array {
    // Store the audit and audit_question entities in the form state for later use.
    $form_state->set('audit', $audit);
    $form_state->set('audit_question', $audit_question);

    // Add information about the Audit, Cluster and Question at the top of the form.
    if ($audit) {
      $form['audit_info'] = [
        '#type' => 'markup',
        '#markup' => '<h3>' . $audit->label() . '</h3>',
      ];
    }

    if ($audit_question) {

      // Get cluster information if available.
      if ($audit_question->hasField('field_cluster') && !$audit_question->get('field_cluster')->isEmpty()) {
        $cluster_term = $audit_question->get('field_cluster')->entity;
        $form['cluster_info'] = [
          '#type' => 'markup',
          '#markup' => '<h4>' . ($cluster_term ? $cluster_term->label() : $this->t('Cluster term is missing')) . '</h4>',
        ];
      }

      // Display EQAVET and ISO 21001 field information as tags.
      $standards_tags = [];
      if ($audit_question->hasField('field_eqavet') && !$audit_question->get('field_eqavet')->isEmpty()) {
        $eqavet_value = $audit_question->get('field_eqavet')->value;
        if ($eqavet_value) {
          $standards_tags[] = '<span class="standard-tag eqavet-tag">EQAVET</span>';
        }
      }

      if ($audit_question->hasField('field_iso_21001') && !$audit_question->get('field_iso_21001')->isEmpty()) {
        $iso_value = $audit_question->get('field_iso_21001')->value;
        if ($iso_value) {
          $standards_tags[] = '<span class="standard-tag iso-tag">ISO 21001</span>';
        }
      }

      $standards_markup = '';
      if (!empty($standards_tags)) {
        $standards_markup = '<div class="standards-tags">' . implode(' ', $standards_tags) . '</div>';
      }

      $form['question_info'] = [
        '#type' => 'markup',
        '#markup' => '<h5>' . $audit_question->label() . '</h5>' . $standards_markup,
      ];

      // Add CSS for the tags.
      $form['#attached']['library'][] = 'audit/standards-tags';
    }

    // Add the mandatory evidence number field
    $form['field_evidence_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Evidence Number'),
      '#description' => $this->t('Enter the unique evidence number.'),
      '#required' => TRUE,
    ];

    $is_yes_no_question = FALSE;
    if ($audit_question) {
      // Check if the question is a yes/no question
      if ($audit_question->hasField('field_simple_yes_no') && !$audit_question->get('field_simple_yes_no')->isEmpty()) {
        $is_yes_no_question = (bool) $audit_question->get('field_simple_yes_no')->value;
      }
    }

    if ($is_yes_no_question) {
      // For yes/no questions, we don't allow adding evidence via the form since it's handled by the template
      // Just show a message to the user
      $form['yes_no_info'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('Yes/No status is managed directly on the main audit page via a toggle switch. You cannot add evidence in this way for yes/no questions.') . '</div>',
      ];
    } else {
      // For regular questions, show the text evidence field
      $form['description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Provide evidence:'),
        '#description' => $this->t('Provide evidence for this audit question.'),
        '#required' => FALSE,
      ];
    }

    // Check if we should display the file upload field.
    $show_file_upload = FALSE;
    if ($audit_question) {
      // Check if field_eqavet or field_iso_21001 is true.
      if (($audit_question->hasField('field_iso_doc_info') && !$audit_question->get('field_iso_doc_info')->isEmpty() && $audit_question->get('field_iso_doc_info')->value == 'yes') ||
        ($audit_question->hasField('field_eqavet_doc_info') && !$audit_question->get('field_eqavet_doc_info')->isEmpty() && $audit_question->get('field_eqavet_doc_info')->value == 'yes')
      ) {
        $show_file_upload = TRUE;
      }
    }

    // Add file upload field if needed.
    if ($show_file_upload) {
      $form['field_supporting_files'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Supporting Files:'),
        '#description' => $this->t('Upload supporting files for this evidence. You can select multiple files.'),
        '#upload_location' => 'public://audit-evidence/',
        '#upload_validators' => [
          'file_validate_extensions' => ['txt pdf zip docx csv xlsx rtf md markdown'],
        ],
        '#multiple' => TRUE,
        // Unlimited.
        '#cardinality' => -1,
        '#required' => FALSE,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancelForm'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#value']) && $triggering_element['#value'] === $this->t('Cancel')) {
      // Skip validation when canceling.
      return;
    }

    parent::validateForm($form, $form_state);

    // Validate that we have the required entities.
    $audit = $form_state->get('audit');
    $audit_question = $form_state->get('audit_question');

    if (!$audit) {
      $form_state->setError($form, $this->t('Audit is required.'));
    }

    if (!$audit_question) {
      $form_state->setError($form, $this->t('Audit question is required.'));
    }

    // Check if this is a yes/no question
    $is_yes_no_question = FALSE;
    if ($audit_question) {
      if ($audit_question->hasField('field_simple_yes_no') && !$audit_question->get('field_simple_yes_no')->isEmpty()) {
        $is_yes_no_question = (bool) $audit_question->get('field_simple_yes_no')->value;
      }
    }

    // For yes/no questions, we don't allow adding evidence via this form
    if ($is_yes_no_question) {
      // No validation needed for yes/no questions since the form doesn't allow adding
    } else {
      // For regular questions, validate that evidence text is provided
      $description = $form_state->getValue('description');
      $evidence_number = $form_state->getValue('field_evidence_number');
      
      if (empty($description)) {
        $form_state->setErrorByName('description', $this->t('Evidence field is required.'));
      }
      
      if (empty($evidence_number)) {
        $form_state->setErrorByName('field_evidence_number', $this->t('Evidence Number is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#value']) && $triggering_element['#value'] === $this->t('Cancel')) {
      return;
    }

    $audit = $form_state->get('audit');
    $audit_question = $form_state->get('audit_question');

    // Check if this is a yes/no question
    $is_yes_no_question = FALSE;
    if ($audit_question) {
      if ($audit_question->hasField('field_simple_yes_no') && !$audit_question->get('field_simple_yes_no')->isEmpty()) {
        $is_yes_no_question = (bool) $audit_question->get('field_simple_yes_no')->value;
      }
    }

    if ($is_yes_no_question) {
      // For yes/no questions, we don't allow adding evidence via this form
      $this->messenger()->addWarning($this->t('Yes/No status must be updated directly on the main audit page.'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit->id()]);
      return;
    } else {
      // For regular questions, get the text description
      $description = $form_state->getValue('description');
      $evidence_number = $form_state->getValue('field_evidence_number');

      // Create a new audit evidence entity with the text evidence
      $evidence = $this->entityTypeManager->getStorage('audit_evidence')->create([
        'label' => $evidence_number . ' - ' . $this->t('Evidence for @question', ['@question' => $audit_question->label()]),
        'field_evidence_number' => $evidence_number,
        'field_audit' => $audit->id(),
        'field_audit_question' => $audit_question->id(),
        'field_evidence' => $description,
        'uid' => $this->currentUser->id(),
      ]);
    }

    // Handle file uploads if any.
    $file_ids = $form_state->getValue('field_supporting_files', []);
    if (!empty($file_ids)) {
      // Load and save files permanently.
      $files = [];
      foreach ($file_ids as $fid) {
        if ($file = $this->entityTypeManager->getStorage('file')->load($fid)) {
          // Set file status to permanent.
          $file->setPermanent();
          $file->save();
          $files[] = ['target_id' => $fid];
        }
      }

      // Set files to the evidence entity.
      if (!empty($files)) {
        $evidence->set('field_supporting_files', $files);
      }
    }

    $evidence->save();

    $this->messenger()->addStatus($this->t('Evidence has been saved.'));
    $form_state->setRedirect('entity.node.canonical', ['node' => $audit->id()]);
  }

  /**
   * Cancel form submission handler.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state): void {
    $audit = $form_state->get('audit');

    if ($audit) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit->id()]);
    } else {
      // Fallback redirect.
      $form_state->setRedirect('<front>');
    }
  }
}
