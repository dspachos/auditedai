<?php

namespace Drupal\audit\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\audit\Entity\AuditQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding evidence directly to an audit.
 */
class AuditEvidenceAddToAuditForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AuditEvidenceAddToAuditForm.
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
    return 'audit_evidence_add_to_audit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    if (!$node || $node->getType() !== 'audit') {
      $this->messenger()->addError($this->t('Invalid audit node.'));
      return $form;
    }

    $form_state->set('audit', $node);

    // Add the mandatory evidence number field
    $form['field_evidence_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Evidence Number'),
      '#description' => $this->t('Enter the unique evidence number.'),
      '#required' => TRUE,
    ];

    // Add the mandatory label field
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter the label for this evidence.'),
      '#required' => TRUE,
    ];

    // Evidence description
    $form['field_evidence'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Evidence Description'),
      '#description' => $this->t('Provide detailed evidence information.'),
      '#required' => FALSE,
    ];



    // Supporting files
    $form['field_supporting_files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Supporting Files'),
      '#description' => $this->t('Upload supporting files for this evidence. You can select multiple files.'),
      '#upload_location' => 'public://audit-evidence/',
      '#upload_validators' => [
        'file_validate_extensions' => ['txt pdf zip docx csv xlsx rtf md markdown'],
      ],
      '#multiple' => TRUE,
      '#cardinality' => -1,
      '#required' => FALSE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Evidence'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
      '#url' => $audit->toUrl()->mergeOptions(['fragment' => 'evidences']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $audit = $form_state->get('audit');

    $evidence_number = $form_state->getValue('field_evidence_number');
    $label = $form_state->getValue('label');
    $description = $form_state->getValue('field_evidence');
    $file_ids = $form_state->getValue('field_supporting_files', []);

    // Create the new evidence entity
    $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');
    $evidence = $evidence_storage->create([
      'type' => 'audit_evidence', // assuming bundle type
      'label' => $label,
      'field_evidence_number' => $evidence_number,
      'field_audit' => $audit->id(),
      'field_evidence' => $description,
      'uid' => $this->currentUser()->id(),
    ]);

    // Set supporting files if any
    if (!empty($file_ids)) {
      $files = [];
      foreach ($file_ids as $fid) {
        if ($file = $this->entityTypeManager->getStorage('file')->load($fid)) {
          $file->setPermanent();
          $file->save();
          $files[] = ['target_id' => $fid];
        }
      }
      $evidence->set('field_supporting_files', $files);
    }

    $evidence->save();

    $this->messenger()->addStatus($this->t('Evidence @label has been created.', ['@label' => $label]));

    // Redirect back to the audit's evidence list
    $form_state->setRedirect('audit.evidence_list', ['node' => $audit->id()]);
  }

}