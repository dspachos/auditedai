<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Audit Question Response entities.
 */
class AuditQuestionResponseCommentDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this comment?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $paragraph = $this->entity;
    // Extract audit and question IDs from the paragraph if possible
    $audit_ref = $paragraph->get('field_audit')->target_id ?? NULL;
    if ($audit_ref) {
      return $this->entity->toUrl('canonical')->setRouteParameter('node', $audit_ref);
    }
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $paragraph = $this->entity;
    $paragraph->delete();
    $this->messenger()->addStatus($this->t('Comment has been deleted.'));
    $audit_ref = $paragraph->get('field_audit')->target_id ?? NULL;
    if ($audit_ref) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit_ref]);
    } else {
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}