<?php

namespace Drupal\vp\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vp\Entity\VirtualPatientNode;

/**
 * Plugin description.
 *
 * @EntityReferenceSelection(
 *   id = "vp_advanced_vp_node_selection",
 *   label = @Translation("Advanced VPnode selection"),
 *   group = "vp_advanced_vp_node_selection",
 *   entity_types = {"vp_node"},
 *   weight = 0
 * )
 */
class VpNodeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = [];
    return $default_configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $id = \Drupal::request()->query->get('entity_id');
    if ($id) {
      $vp_node = VirtualPatientNode::load($id);
      $parent = $vp_node->field_parent->entity;
      if ($parent) {
        $query->condition('field_parent', $parent->id());
      }
    }
    return $query;
  }

}
