<?php

declare(strict_types=1);

namespace Drupal\audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Constructs a new AjaxController.
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
    
    // Create or update the evidence entity
    $evidence_entity = $this->createOrUpdateEvidence($audit_entity, $question_entity, $value);
    
    if (!$evidence_entity) {
      return new JsonResponse(['error' => 'Failed to save evidence'], 500);
    }
    
    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Value saved successfully',
      'value' => $value
    ]);
  }
  
  /**
   * Creates or updates an evidence entity with the yes/no answer.
   */
  protected function createOrUpdateEvidence($audit_entity, $audit_question_entity, $answer_value) {
    $current_evidence = $this->getCurrentEvidence($audit_entity, $audit_question_entity);
    
    if ($current_evidence) {
      // Update existing evidence
      $current_evidence->field_yes_no_answer->value = $answer_value ? 1 : 0;
      $current_evidence->save();
      return $current_evidence;
    } else {
      // Create new evidence
      $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');
      $evidence = $evidence_storage->create([
        'label' => 'Evidence for question: ' . $audit_question_entity->label(),
        'field_audit' => $audit_entity->id(),
        'field_audit_question' => $audit_question_entity->id(),
        'field_yes_no_answer' => $answer_value ? 1 : 0,
        'status' => TRUE,
      ]);
      
      $evidence->save();
      return $evidence;
    }
  }

  /**
   * Gets the current evidence for the given audit and question.
   */
  protected function getCurrentEvidence($audit_entity, $audit_question_entity) {
    $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');
    $query = $evidence_storage->getQuery()
      ->condition('field_audit', $audit_entity->id())
      ->condition('field_audit_question', $audit_question_entity->id())
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(TRUE);

    $evidence_ids = $query->execute();
    
    if (!empty($evidence_ids)) {
      $evidence_id = reset($evidence_ids);
      return $evidence_storage->load($evidence_id);
    }

    return NULL;
  }
}