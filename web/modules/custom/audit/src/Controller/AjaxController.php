<?php

declare(strict_types=1);

namespace Drupal\audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for AJAX operations in the audit module.
 */
class AjaxController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new AjaxController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Handles AJAX toggle of yes/no value for a question.
   */
  public function toggleYesNo($audit_id, $question_id, Request $request) {
    // Check if the user is authenticated
    if (!$this->currentUser()->isAuthenticated()) {
      throw new AccessDeniedHttpException();
    }

    $value = $request->request->get('value');

    if ($value !== '0' && $value !== '1') {
      return new JsonResponse(['error' => 'Invalid value'], 400);
    }

    $value = (bool) $value;

    // Load the entities
    $audit_entity = $this->entityTypeManager->getStorage('node')->load($audit_id);
    $question_entity = $this->entityTypeManager->getStorage('audit_question')->load($question_id);

    if (!$audit_entity || !$question_entity) {
      return new JsonResponse(['error' => 'Invalid audit or question entity'], 404);
    }

    // Check if this is a yes/no question
    if (!$question_entity->field_simple_yes_no->value) {
      return new JsonResponse(['error' => 'Question is not a yes/no question'], 400);
    }

    // Create or update the paragraph response
    $paragraph_entity = $this->createOrUpdateParagraphResponse($audit_entity, $question_entity, $value);

    if (!$paragraph_entity) {
      return new JsonResponse(['error' => 'Failed to save paragraph response'], 500);
    }

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Value saved successfully',
      'value' => $value
    ]);
  }
  
  /**
   * Creates or updates a paragraph entity with the yes/no answer.
   */
  protected function createOrUpdateParagraphResponse($audit_entity, $audit_question_entity, $answer_value) {
    $current_paragraph = $this->getCurrentParagraphResponse($audit_entity, $audit_question_entity);

    if ($current_paragraph) {
      // Update existing paragraph
      $current_paragraph->field_yes_no->value = $answer_value ? 1 : 0;
      $current_paragraph->save();
      return $current_paragraph;
    } else {
      // Create new paragraph response
      $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
      $paragraph = $paragraph_storage->create([
        'type' => 'audit_question_response',
        'field_audit' => $audit_entity->id(),
        'field_audit_question' => $audit_question_entity->id(),
        'field_yes_no' => $answer_value ? 1 : 0,
        'field_comments' => '', // Initialize with empty comments field
        'status' => TRUE,
      ]);

      $paragraph->save();
      return $paragraph;
    }
  }

  /**
   * Gets the current paragraph response for the given audit and question.
   */
  protected function getCurrentParagraphResponse($audit_entity, $audit_question_entity) {
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $query = $paragraph_storage->getQuery()
      ->condition('type', 'audit_question_response')
      ->condition('field_audit', $audit_entity->id())
      ->condition('field_audit_question', $audit_question_entity->id())
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(TRUE);

    $paragraph_ids = $query->execute();

    if (!empty($paragraph_ids)) {
      $paragraph_id = reset($paragraph_ids);
      return $paragraph_storage->load($paragraph_id);
    }

    return NULL;
  }

  /**
   * Controller method for the attach evidence modal.
   */
  public function attachEvidence($audit, $question) {
    // Load the entities properly
    $audit_storage = $this->entityTypeManager->getStorage('node');
    $question_storage = $this->entityTypeManager->getStorage('audit_question');
    
    $audit_entity = is_numeric($audit) ? $audit_storage->load($audit) : $audit;
    $question_entity = is_numeric($question) ? $question_storage->load($question) : $question;
    
    if (!$audit_entity || !$question_entity) {
      return [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Invalid audit or question specified.') . '</p>',
      ];
    }
    
    // Build and return the form with the loaded entities
    return $this->formBuilder->getForm('\Drupal\audit\Form\AttachEvidenceForm', $audit_entity, $question_entity);
  }

  /**
   * Controller method for the audit question response comment modal.
   */
  public function auditQuestionResponseComment($audit, $question, $paragraph = NULL) {
    // Load the entities properly
    $audit_storage = $this->entityTypeManager->getStorage('node');
    $question_storage = $this->entityTypeManager->getStorage('audit_question');
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

    $audit_entity = is_numeric($audit) ? $audit_storage->load($audit) : $audit;
    $question_entity = is_numeric($question) ? $question_storage->load($question) : $question;
    $paragraph_entity = NULL;

    // Handle the paragraph parameter - if it's numeric, load it, otherwise it's already loaded
    if ($paragraph) {
      if (is_numeric($paragraph)) {
        $paragraph_entity = $paragraph_storage->load($paragraph);
      } else {
        $paragraph_entity = $paragraph;
      }
    }

    if (!$audit_entity || !$question_entity) {
      return [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Invalid audit or question specified.') . '</p>',
      ];
    }

    // Build and return the form with the loaded entities
    return $this->formBuilder->getForm('\Drupal\audit\Form\AuditQuestionResponseCommentForm', $paragraph_entity, $audit_entity->id(), $question_entity->id());
  }

  /**
   * Controller method for deleting an audit question response comment via AJAX.
   */
  public function deleteAuditQuestionResponseComment($audit, $question, $paragraph) {
    $audit_storage = $this->entityTypeManager->getStorage('node');
    $question_storage = $this->entityTypeManager->getStorage('audit_question');
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

    $audit_entity = is_numeric($audit) ? $audit_storage->load($audit) : $audit;
    $question_entity = is_numeric($question) ? $question_storage->load($question) : $question;
    $paragraph_entity = is_numeric($paragraph) ? $paragraph_storage->load($paragraph) : $paragraph;

    if (!$paragraph_entity) {
      return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Invalid paragraph'], 404);
    }

    // Check permissions for deletion
    $current_user = $this->currentUser();
    $paragraph_owner_id = $paragraph_entity->getOwnerId() ?? 0;
    $current_user_id = $current_user->id();
    $is_owner = $paragraph_owner_id == $current_user_id;

    $can_delete = $current_user->hasPermission('delete audit_question_response_comment') ||
                  ($current_user->hasPermission('delete own audit_question_response_comment') && $is_owner);

    if (!$can_delete) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'error' => $this->t('You do not have permission to delete this comment.')
      ], 403);
    }

    // Delete the paragraph
    $paragraph_entity->delete();

    return new \Symfony\Component\HttpFoundation\JsonResponse([
      'success' => TRUE,
      'message' => $this->t('Comment has been deleted.')
    ]);
  }
}