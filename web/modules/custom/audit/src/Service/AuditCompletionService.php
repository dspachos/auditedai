<?php

namespace Drupal\audit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\audit\Entity\AuditEvidence;

/**
 * Service to calculate audit completion percentage.
 */
class AuditCompletionService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AuditCompletionService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Calculates the completion percentage of an audit.
   *
   * @param int $audit_id
   *   The ID of the audit entity.
   *
   * @return float
   *   The completion percentage as a value between 0 and 100.
   */
  public function calculateCompletionPercentage($audit_id) {
    // Load all published audit questions
    $question_storage = $this->entityTypeManager->getStorage('audit_question');
    $all_questions = $question_storage->loadByProperties([
      'status' => 1, // Assuming 1 means published
    ]);

    if (empty($all_questions)) {
      return 0.0;
    }

    $total_questions = count($all_questions);
    $answered_questions = 0;

    // For each question, check if there's at least one evidence associated with it for this audit
    foreach ($all_questions as $question) {
      // Check if this is a yes/no question
      if ($question->field_simple_yes_no->value) {
        // For yes/no questions, check if there's a paragraph response
        $paragraph_entities = $this->entityTypeManager->getStorage('paragraph')->loadByProperties([
          'type' => 'audit_question_response',
          'field_audit' => $audit_id,
          'field_audit_question' => $question->id(),
        ]);

        // If there's a paragraph response for this yes/no question, count it as answered
        if (!empty($paragraph_entities)) {
          $answered_questions++;
        }
      } else {
        // For regular questions, check if there's evidence
        $evidence_entities = $this->entityTypeManager->getStorage('audit_evidence')->loadByProperties([
          'field_audit' => $audit_id,
          'field_audit_question' => $question->id(),
        ]);

        // If there's at least one evidence for this question, count it as answered
        if (!empty($evidence_entities)) {
          $answered_questions++;
        }
      }
    }

    // Calculate the percentage
    if ($total_questions === 0) {
      return 0.0;
    }
    
    $completion_percentage = ($answered_questions / $total_questions) * 100;

    return round($completion_percentage, 2);
  }

  /**
   * Determines if an evidence entity is complete.
   *
   * @param \Drupal\audit\Entity\AuditEvidence $evidence
   *   The evidence entity to check.
   *
   * @return bool
   *   TRUE if the evidence is complete, FALSE otherwise.
   */
  protected function isEvidenceComplete(AuditEvidence $evidence) {
    // Check if field_evidence has a value
    $evidence_value = $evidence->get('field_evidence')->value;
    if (!empty($evidence_value) && is_string($evidence_value) && trim($evidence_value) !== '') {
      return TRUE;
    }

    return FALSE;
  }

}