<?php

namespace Drupal\audit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for promoting audit status.
 */
class AuditPromoteStatusForm extends ConfirmFormBase {

  /**
   * The node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new AuditPromoteStatusForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_update_status';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Promote Audit Status');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.canonical', ['node' => $this->node->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Update Status');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\node\NodeInterface $audit */
    $audit = $this->node;
    $current_status = $audit->get('field_status')->value;

    return $this->t('Current status: <strong><em>@status<em></strong>. <br />Select the new status for this audit.', [
      '@status' => $this->getStatusLabel($current_status),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    // Set the node from the route parameter
    if ($node) {
      $this->node = $node;
    }

    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\node\NodeInterface $audit */
    $audit = $this->node;
    $current_status = $audit->get('field_status')->value;

    // Define allowed status transitions
    $allowed_transitions = $this->getAllowedTransitions($current_status);

    if (empty($allowed_transitions)) {
      $form['message'] = [
        '#markup' => '<p>' . $this->t('No status transitions are allowed from the current status (@status).', ['@status' => $this->getStatusLabel($current_status)]) . '</p>',
      ];
      return $form;
    }

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('New Status'),
      '#required' => TRUE,
      '#options' => $allowed_transitions,
      '#default_value' => $current_status,
      '#empty_option' => $this->t('- Select a status -'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $selected_status = $form_state->getValue('status');
    $current_status = $this->node->get('field_status')->value;

    // Validate that the selected status is allowed
    $allowed_transitions = $this->getAllowedTransitions($current_status);
    if (!isset($allowed_transitions[$selected_status])) {
      $form_state->setErrorByName('status', $this->t('The selected status is not allowed from the current status.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\node\NodeInterface $audit */
    $audit = $this->node;
    $new_status = $form_state->getValue('status');

    // Update the status field
    $audit->set('field_status', $new_status);

    // Save the audit node
    $audit->save();

    $this->messenger->addMessage($this->t('The audit status has been updated to @status.', [
      '@status' => $this->getStatusLabel($new_status),
    ]));

    // Redirect to the audit page
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Gets the allowed transitions from the current status.
   *
   * @param string $current_status
   *   The current status.
   *
   * @return array
   *   Array of allowed transitions.
   */
  protected function getAllowedTransitions($current_status) {
    $allowed_transitions = [];

    switch ($current_status) {
      case 'draft':
        // From draft, can go to in_progress or complete
        $allowed_transitions['in_progress'] = $this->t('In progress');
        $allowed_transitions['complete'] = $this->t('Complete');
        break;

      case 'in_progress':
        // From in_progress, can go to complete
        $allowed_transitions['complete'] = $this->t('Complete');
        break;

      case 'complete':
        // From complete, no further transitions (or could allow back to draft)
        // In this case, we return empty array meaning no transitions allowed
        break;

      default:
        // For any other status, allow transitions to in_progress and complete
        $allowed_transitions['in_progress'] = $this->t('In progress');
        $allowed_transitions['complete'] = $this->t('Complete');
        break;
    }

    return $allowed_transitions;
  }

  /**
   * Gets the display label for a status value.
   *
   * @param string $status
   *   The status value.
   *
   * @return string
   *   The status label.
   */
  protected function getStatusLabel($status) {
    $labels = [
      'draft' => $this->t('Draft'),
      'in_progress' => $this->t('In progress'),
      'complete' => $this->t('Complete'),
    ];

    return $labels[$status] ?? $status;
  }
}
