<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting Audit Question Response entities.
 */
class AuditQuestionResponseCommentDeleteForm extends ConfirmFormBase {

  /**
   * The paragraph entity to delete.
   *
   * @var \Drupal\paragraphs\ParagraphInterface
   */
  protected $paragraph;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AuditQuestionResponseCommentDeleteForm.
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
   * Sets the entity to be deleted.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to delete.
   *
   * @return $this
   */
  public function setEntity(ParagraphInterface $paragraph) {
    $this->paragraph = $paragraph;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audit_question_response_comment_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this comment?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Extract audit ID from the paragraph
    $audit_ref = NULL;
    if ($this->paragraph) {
      $audit_ref = $this->paragraph->get('field_audit')->target_id ?? NULL;
    }
    if ($audit_ref) {
      return new Url('entity.node.canonical', ['node' => $audit_ref]);
    }
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
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
  public function buildForm(array $form, FormStateInterface $form_state, ParagraphInterface $paragraph = NULL) {
    $this->paragraph = $paragraph;

    // Call parent to handle the confirmation form elements
    $form = parent::buildForm($form, $form_state);

    // Add AJAX functionality to the submit button - always add it to handle AJAX requests properly
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmitCallback',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Capture the audit reference before deleting the entity
    $audit_ref = $this->paragraph->get('field_audit')->target_id ?? NULL;

    $this->paragraph->delete();
    $this->messenger()->addStatus($this->t('Comment has been deleted.'));

    if ($audit_ref) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit_ref]);
    } else {
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

  /**
   * AJAX callback for submit action.
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    // Capture the audit reference before deleting the entity
    $audit_ref = $this->paragraph->get('field_audit')->target_id ?? NULL;

    // Call the original submit logic (this will delete the paragraph)
    $this->submitForm($form, $form_state);

    // Create AJAX response
    $response = new AjaxResponse();

    // Close the modal dialog
    $response->addCommand(new CloseModalDialogCommand());

    // Redirect to refresh the page
    if ($audit_ref) {
      $response->addCommand(new RedirectCommand(Url::fromRoute('entity.node.canonical', ['node' => $audit_ref])->toString()));
    } else {
      $response->addCommand(new RedirectCommand($this->getCancelUrl()->toString()));
    }

    return $response;
  }

}