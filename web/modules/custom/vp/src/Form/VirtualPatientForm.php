<?php

namespace Drupal\vp\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the virtual patient entity edit forms.
 */
class VirtualPatientForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New virtual patient %label has been created.', $message_arguments));
        $this->logger('vp')->notice('Created new virtual patient %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The virtual patient %label has been updated.', $message_arguments));
        $this->logger('vp')->notice('Updated virtual patient %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.virtual_patient.canonical', ['virtual_patient' => $entity->id()]);

    return $result;
  }

}
