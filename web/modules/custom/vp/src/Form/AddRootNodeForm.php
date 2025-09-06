<?php

namespace Drupal\vp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\vp\Entity\VirtualPatient;
use Drupal\vp\Entity\VirtualPatientNode;
use Drupal\vp\VpService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a VP form.
 */
class AddRootNodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vp_add_root_node';
  }

  /**
   * @var \Drupal\vp\VpService
   */
  protected $service;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * @param \Drupal\vp\VpService $service
   */
  public function __construct(VpService $service, CurrentRouteMatch $current_route_match) {
    $this->service = $service;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('vp.service'),
          $container->get('current_route_match')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VirtualPatient $virtual_patient = NULL) {
    $form[]['title'] = [
      '#type' => 'markup',
      '#markup' => Markup::create("<strong><em>{$virtual_patient->label()}</em></strong>"),
    ];
    $root_node = $this->service->getRootNode($virtual_patient);

    $options = [];
    $nodes = $virtual_patient->field_vp_nodes->referencedEntities();
    foreach ($nodes as $node) {
      $options[$node->id()] = $node->label();
    }

    if (!empty($options)) {
      $form['root_node'] = [
        '#type' => 'select',
        '#default_value' => $root_node ? $root_node->id() : NULL,
        '#title' => $this->t('Set root node'),
        '#options' => $options,
      ];
    }
    else {
      $tip = $this->t("You don't have any nodes yet.");
      $form[]['info'] = [
        '#type' => 'markup',
        '#markup' => Markup::create("<p><em>{$tip}</em></p>"),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $virtual_patient = $this->currentRouteMatch->getParameter('virtual_patient');
    $key = $form_state->getValue('root_node');
    $vp_node = VirtualPatientNode::load($key);
    // $val = $form['root_node']['#options'][$key];
    if ($vp_node) {
      $this->service->clearRootNodes($virtual_patient);
      $this->service->setRootNode($vp_node);
    }
    $url = $virtual_patient->toUrl('edit-form');
    $form_state->setRedirectUrl($url);
  }

}
