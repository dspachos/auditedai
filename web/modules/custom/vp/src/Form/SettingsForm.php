<?php

namespace Drupal\vp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure VP settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings for the Virtual Patients module'),
      '#open' => TRUE,
    ];

    $form['container']['player_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Player URL'),
      '#maxlength' => 1024,
      '#default_value' => $this->config('vp.settings')->get('player_url'),
      '#description' => $this->t('Add the absolute mobile player link to be able to preview virtual patients.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vp.settings')
      ->set('player_url', $form_state->getValue('player_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
