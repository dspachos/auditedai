<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for audit question response comment.
 */
final class AuditQuestionResponseCommentForm extends FormBase {

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
    return 'audit_question_response_comment_form';
  }

  /**
   * Builds the comment form for audit question responses.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ParagraphInterface $paragraph = NULL, ?int $audit_id = NULL, ?int $question_id = NULL): array {
    // Store entities in form state for later use.
    $form_state->set('paragraph', $paragraph);
    $form_state->set('audit_id', $audit_id);
    $form_state->set('question_id', $question_id);

    // Determine if the user can edit based on permissions
    $can_edit = FALSE;
    $current_user_id = $this->currentUser->id();

    if ($paragraph) {
      // Editing existing paragraph - check permissions
      $paragraph_owner_id = $paragraph->getOwnerId() ?? 0;
      $is_owner = $paragraph_owner_id == $current_user_id;

      if ($this->currentUser->hasPermission('edit audit_question_response_comment')) {
        $can_edit = TRUE;
      } elseif ($this->currentUser->hasPermission('edit own audit_question_response_comment') && $is_owner) {
        $can_edit = TRUE;
      }
    } else {
      // Creating new paragraph - check create permission
      $can_edit = $this->currentUser->hasPermission('create audit_question_response_comment');
    }

    if (!$can_edit) {
      $form['error'] = [
        '#markup' => '<p>' . $this->t('You do not have permission to manage comments for this audit question response.') . '</p>',
      ];
      return $form;
    }

    // Get current response value if editing existing paragraph
    $current_response = '';
    if ($paragraph && $paragraph->hasField('field_response')) {
      $current_response = $paragraph->get('field_response')->value ?? '';
    }

    $form['field_response'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#description' => $this->t('Enter your comment for this audit question response.'),
      '#default_value' => $current_response,
      '#required' => FALSE,
      '#rows' => 5,
    ];

    $form['paragraph_id'] = [
      '#type' => 'hidden',
      '#value' => $paragraph ? $paragraph->id() : NULL,
    ];

    $form['audit_id'] = [
      '#type' => 'hidden',
      '#value' => $audit_id,
    ];

    $form['question_id'] = [
      '#type' => 'hidden',
      '#value' => $question_id,
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Comment'),
      '#ajax' => [
        'callback' => '::submitFormAjax',
        'effect' => 'fade',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['dialog-cancel'],
      ],
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#value']) && $triggering_element['#value'] === $this->t('Cancel')) {
      return;
    }

    $paragraph = $form_state->get('paragraph');
    $response_text = $form_state->getValue('field_response');
    $current_user_id = $this->currentUser->id();

    // Check permissions when saving
    if ($paragraph) {
      // Editing existing paragraph
      $paragraph_owner_id = $paragraph->getOwnerId() ?? 0;
      $is_owner = $paragraph_owner_id == $current_user_id;

      $can_edit = $this->currentUser->hasPermission('edit audit_question_response_comment') ||
                  ($this->currentUser->hasPermission('edit own audit_question_response_comment') && $is_owner);

      if (!$can_edit) {
        $this->messenger()->addError($this->t('You do not have permission to edit this comment.'));
        return;
      }

      // Update existing paragraph
      $paragraph->set('field_response', $response_text);
      $paragraph->save();
    } else {
      // Creating new paragraph
      if (!$this->currentUser->hasPermission('create audit_question_response_comment')) {
        $this->messenger()->addError($this->t('You do not have permission to create comments.'));
        return;
      }

      // Create new paragraph
      $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
      $paragraph = $paragraph_storage->create([
        'type' => 'audit_question_response',
        'field_response' => $response_text,
        'field_audit' => $form_state->getValue('audit_id'),
        'field_audit_question' => $form_state->getValue('question_id'),
        'uid' => $current_user_id, // Set the owner
        'status' => TRUE,
      ]);
      $paragraph->save();
    }

    $this->messenger()->addStatus($this->t('Comment has been saved.'));
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $form_state->getValue('audit_id')]));
  }

  /**
   * AJAX callback for form submission.
   */
  public function submitFormAjax(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Call the regular submit method
    $this->submitForm($form, $form_state);

    // Instead of closing the dialog, refresh the page to show updated comment
    $response->addCommand(new RedirectCommand(\Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $form_state->getValue('audit_id')])->toString()));

    return $response;
  }

  /**
   * Cancel form submission handler.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state): void {
    $audit_id = $form_state->getValue('audit_id');
    if ($audit_id) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit_id]);
    } else {
      $form_state->setRedirect('<front>');
    }
  }
}