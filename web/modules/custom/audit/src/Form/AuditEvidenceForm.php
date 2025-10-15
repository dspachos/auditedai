<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the audit evidence entity edit forms.
 */
final class AuditEvidenceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    
    // Update the label to include the evidence number when applicable
    if ($entity->hasField('field_evidence_number') && !$entity->get('field_evidence_number')->isEmpty()) {
      $evidence_number = $entity->get('field_evidence_number')->value;
      
      // Get the audit question if available
      $audit_question_label = 'Audit Evidence';
      if ($entity->hasField('field_audit_question') && !$entity->get('field_audit_question')->isEmpty()) {
        $audit_question = $entity->get('field_audit_question')->entity;
        if ($audit_question) {
          $audit_question_label = $audit_question->label();
        }
      }
      
      $entity->set('label', $evidence_number . ' - ' . $this->t('Evidence for @question', ['@question' => $audit_question_label]));
    }

    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New audit evidence %label has been created.', $message_args));
        $this->logger('audit')->notice('New audit evidence %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The audit evidence %label has been updated.', $message_args));
        $this->logger('audit')->notice('The audit evidence %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
