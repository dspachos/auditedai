<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\audit\Entity\AuditQuestion;
use Drupal\audit\Entity\AuditEvidence;

/**
 * Form controller for detaching audit evidence from a question with modal confirmation.
 */
final class AuditEvidenceDeleteModalForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_evidence_delete_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AuditEvidence $audit_evidence = NULL): array {
    // Store the audit evidence entity in the form state for later use.
    $form_state->set('audit_evidence', $audit_evidence);

    // Add evidence details.
    if ($audit_evidence) {
      $form['evidence_details'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Evidence Details'),
      ];

      // Evidence content.
      $form['evidence_details']['content'] = [
        '#type' => 'markup',
        '#markup' => '<p><strong>' . $this->t('Evidence:') . '</strong> ' . 
          ($audit_evidence->hasField('field_evidence') ? $audit_evidence->get('field_evidence')->value : '') . '</p>',
      ];

      // Audit information.
      if ($audit_evidence->hasField('field_audit') && !$audit_evidence->get('field_audit')->isEmpty()) {
        $audit = $audit_evidence->get('field_audit')->entity;
        if ($audit) {
          $form['evidence_details']['audit'] = [
            '#type' => 'markup',
            '#markup' => '<p><strong>' . $this->t('Audit:') . '</strong> ' . $audit->label() . '</p>',
          ];
        }
      }

      // Audit question information.
      if ($audit_evidence->hasField('field_audit_question') && !$audit_evidence->get('field_audit_question')->isEmpty()) {
        $audit_question = $audit_evidence->get('field_audit_question')->entity;
        if ($audit_question) {
          $form['evidence_details']['audit_question'] = [
            '#type' => 'markup',
            '#markup' => '<p><strong>' . $this->t('Question:') . '</strong> ' . $audit_question->label() . '</p>',
          ];
        }
      }
    }

    $form['confirmation'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Are you sure you want to detach this evidence from the question? The evidence will be kept but removed from this audit question.') . '</p>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Detach'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate that we have the required entity.
    $audit_evidence = $form_state->get('audit_evidence');
    if (!$audit_evidence) {
      $form_state->setError($form, $this->t('Audit evidence is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // This method will not be called for AJAX submissions.
  }

  /**
   * AJAX callback for form submission.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    
    // Check for validation errors.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#modal-form-wrapper', $form));
      return $response;
    }

    $audit_evidence = $form_state->get('audit_evidence');
    
    // Store audit ID for redirect.
    $audit_id = NULL;
    if ($audit_evidence->hasField('field_audit') && !$audit_evidence->get('field_audit')->isEmpty()) {
      $audit = $audit_evidence->get('field_audit')->entity;
      if ($audit) {
        $audit_id = $audit->id();
      }
    }
    
    // Remove the reference to the audit question instead of deleting the evidence.
    $audit_evidence->set('field_audit_question', NULL);
    $audit_evidence->save();
    
    // Close the modal.
    $response->addCommand(new CloseModalDialogCommand());
    
    // Show success message.
    $this->messenger()->addStatus($this->t('Evidence has been detached from the question.'));
    
    // Redirect back to the audit page.
    if ($audit_id) {
      $response->addCommand(new RedirectCommand('/node/' . $audit_id));
    }
    else {
      $response->addCommand(new RedirectCommand('/'));
    }
    
    return $response;
  }

}