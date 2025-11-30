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
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $audit = NULL, ?AuditEvidence $audit_evidence = NULL): array {
    // Store the audit and audit_evidence entities in the form state for later use.
    $form_state->set('audit', $audit);
    $form_state->set('audit_evidence', $audit_evidence);

    // Add information about the Audit at the top of the form.
    if ($audit) {
      $form['audit_info'] = [
        '#type' => 'markup',
        '#markup' => '<h3>' . $audit->label() . '</h3>',
      ];
    }

    // Add the mandatory evidence number field
    $existing_evidence_number = '';
    if ($audit_evidence && $audit_evidence->hasField('field_evidence_number')) {
      $existing_evidence_number = $audit_evidence->get('field_evidence_number')->value;
    }

    $form['field_evidence_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Evidence Number'),
      '#description' => $this->t('Enter the unique evidence number.'),
      '#required' => TRUE,
      '#default_value' => $existing_evidence_number,
    ];
    
    // Add the mandatory label field
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter the label for this evidence.'),
      '#required' => TRUE,
      '#default_value' => $audit_evidence ? $audit_evidence->label() : '',
    ];

    // For this simplified form, we always show the description field (since we don't have access to the question)
    // Preload existing evidence value for the evidence.
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

    // We still show the file upload field since that's a standard feature of evidence
    // Preload existing files if any.
    $existing_files = [];
    if ($audit_evidence && $audit_evidence->hasField('field_supporting_files')) {
      $file_references = $audit_evidence->get('field_supporting_files')->getValue();
      // Extract file IDs from the file references.
      $existing_files = array_map(function ($file_ref) {
        return $file_ref['target_id'];
      }, $file_references);
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
    $audit_evidence = $form_state->get('audit_evidence');

    if (!$audit) {
      $form_state->setError($form, $this->t('Audit is required.'));
    }

    if (!$audit_evidence) {
      $form_state->setError($form, $this->t('Audit evidence is required.'));
    }

    // Validate that evidence text is provided
    $description = $form_state->getValue('description');
    $evidence_number = $form_state->getValue('field_evidence_number');
    $label = $form_state->getValue('label');

    if (empty($evidence_number)) {
      $form_state->setErrorByName('field_evidence_number', $this->t('Evidence Number is required.'));
    }

    if (empty($label)) {
      $form_state->setErrorByName('label', $this->t('Label is required.'));
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
    $audit_evidence = $form_state->get('audit_evidence');

    // Save the evidence
    $description = $form_state->getValue('description');
    $evidence_number = $form_state->getValue('field_evidence_number');
    $label = $form_state->getValue('label');

    $audit_evidence->set('field_evidence_number', $evidence_number);
    $audit_evidence->set('field_evidence', $description);
    $audit_evidence->set('label', $label);

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
    } else {
      // Fallback redirect.
      $form_state->setRedirect('<front>');
    }
  }
}
