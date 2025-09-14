<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\audit\Entity\AuditQuestion;

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
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $audit = NULL, AuditQuestion $audit_question = NULL): array {
    // Store the audit and audit_question entities in the form state for later use.
    $form_state->set('audit', $audit);
    $form_state->set('audit_question', $audit_question);

    // Add information about the Audit, Cluster and Question at the top of the form.
    if ($audit) {
      $form['audit_info'] = [
        '#type' => 'markup',
        '#markup' => '<h3>' . '</strong> ' . $audit->label() . '</h3>',
      ];
    }

    if ($audit_question) {
      // Get cluster information if available
      $cluster_info = '';
      if ($audit_question->hasField('field_cluster') && !$audit_question->get('field_cluster')->isEmpty()) {
        $cluster_term = $audit_question->get('field_cluster')->entity;
        $form['cluster_info'] = [
          '#type' => 'markup',
          '#markup' => '<h4>' . ($cluster_term ? $cluster_term->label() : $this->t('Cluster term is missing')) . '</h4>',
        ];
      }

      $form['question_info'] = [
        '#type' => 'markup',
        '#markup' => '<h5>' . $audit_question->label() . '</h5>',
      ];
    }

    $form['description'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Provide evidence for this audit question.'),
      '#required' => FALSE,
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
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#value']) && $triggering_element['#value'] === $this->t('Cancel')) {
      return;
    }

    // We're not requiring any fields, but we still check for entities to provide better error messages.
    $audit = $form_state->get('audit');
    $audit_question = $form_state->get('audit_question');

    if (!$audit) {
      $this->messenger()->addWarning($this->t('Audit information is missing.'));
    }

    if (!$audit_question) {
      $this->messenger()->addWarning($this->t('Audit question information is missing.'));
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
    $description = $form_state->getValue('description');

    // Check if we have the required entities
    if (!$audit || !$audit_question) {
      $this->messenger()->addError($this->t('Unable to save evidence: missing audit or question information.'));
      return;
    }

    // Create a new audit evidence entity.
    $evidence = $this->entityTypeManager->getStorage('audit_evidence')->create([
      'label' => $this->t('Evidence for @question', ['@question' => $audit_question->label()]),
      'field_audit' => $audit->id(),
      'field_audit_question' => $audit_question->id(),
      'description' => $description,
      'uid' => $this->currentUser->id(),
    ]);

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
