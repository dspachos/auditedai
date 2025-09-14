<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting an audit evidence entity.
 */
class AuditEvidenceDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    // Redirect back to the audit page.
    $entity = $this->getEntity();
    if ($entity->hasField('field_audit') && !$entity->get('field_audit')->isEmpty()) {
      $audit = $entity->get('field_audit')->entity;
      if ($audit) {
        return $audit->toUrl();
      }
    }
    
    // Fallback to front page.
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    return (string) $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $entity = $this->getEntity();
    
    // Store audit ID for redirect.
    $audit_id = NULL;
    if ($entity->hasField('field_audit') && !$entity->get('field_audit')->isEmpty()) {
      $audit = $entity->get('field_audit')->entity;
      if ($audit) {
        $audit_id = $audit->id();
      }
    }
    
    parent::submitForm($form, $form_state);
    
    // Redirect back to the audit page.
    if ($audit_id) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $audit_id]);
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

}