<?php

namespace Drupal\vp\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the vp node entity edit forms.
 */
class VirtualPatientNodeForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New vp node %label has been created.', $message_arguments));
        $this->logger('vp')->notice('Created new vp node %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The vp node %label has been updated.', $message_arguments));
        $this->logger('vp')->notice('Updated vp node %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.vp_node.canonical', ['vp_node' => $entity->id()]);

    return $result;
  }

}
