<?php

namespace Drupal\vp_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the vp analytics entity edit forms.
 */
class VpAnalyticsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New vp analytics %label has been created.', $message_arguments));
        $this->logger('vp_analytics')->notice('Created new vp analytics %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The vp analytics %label has been updated.', $message_arguments));
        $this->logger('vp_analytics')->notice('Updated vp analytics %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.vp_analytics.canonical', ['vp_analytics' => $entity->id()]);

    return $result;
  }

}
