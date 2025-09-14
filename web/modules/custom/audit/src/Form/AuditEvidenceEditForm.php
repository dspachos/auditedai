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
use Drupal\audit\Entity\AuditEvidence;

/**
 * Form controller for editing audit evidence.
 */
final class AuditEvidenceEditForm extends FormBase {

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
    return 'audit_evidence_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $audit = NULL, ?AuditQuestion $audit_question = NULL, ?AuditEvidence $audit_evidence = NULL): array {
    // Store the audit, audit_question, and audit_evidence entities in the form state for later use.
    $form_state->set('audit', $audit);
    $form_state->set('audit_question', $audit_question);
    $form_state->set('audit_evidence', $audit_evidence);

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

    // Preload existing evidence value.
    $existing_evidence = '';
    if ($audit_evidence && $audit_evidence->hasField('field_evidence')) {
      $existing_evidence = $audit_evidence->get('field_evidence')->value;
    }

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Provide evidence:'),
      '#description' => $this->t('Provide evidence for this audit question.'),
      '#required' => FALSE,
      '#default_value' => $existing_evidence,
    ];

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
      // Preload existing files if any.
      $existing_files = [];
      if ($audit_evidence && $audit_evidence->hasField('field_supporting_files')) {
        $existing_files = $audit_evidence->get('field_supporting_files')->getValue();
      }

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
        '#default_value' => $existing_files,
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
    $audit_evidence = $form_state->get('audit_evidence');

    if (!$audit) {
      $form_state->setError($form, $this->t('Audit is required.'));
    }

    if (!$audit_question) {
      $form_state->setError($form, $this->t('Audit question is required.'));
    }

    if (!$audit_evidence) {
      $form_state->setError($form, $this->t('Audit evidence is required.'));
    }

    // Validate that evidence is provided when saving.
    $description = $form_state->getValue('description');
    if (empty($description)) {
      $form_state->setErrorByName('description', $this->t('Evidence field is required.'));
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
    $audit_evidence = $form_state->get('audit_evidence');
    $description = $form_state->getValue('description');

    // Update the existing audit evidence entity.
    $audit_evidence->set('field_evidence', $description);

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
      $audit_evidence->set('field_supporting_files', $files);
    }

    $audit_evidence->save();

    $this->messenger()->addStatus($this->t('Evidence has been updated.'));
    $form_state->setRedirect('entity.node.canonical', ['node' => $audit->id()]);
  }

  /**
   * Cancel form submission handler.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state): void {
    $audit = $form_state->get('audit');

    if ($audit) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit->id()]);
    }
    else {
      // Fallback redirect.
      $form_state->setRedirect('<front>');
    }
  }

}