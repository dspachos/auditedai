<?php

namespace Drupal\vp_visual_editor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure VP Visual Editor settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vp_visual_editor_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vp_visual_editor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Debug mode"),
      '#return_value' => TRUE,
      '#default_value' => $this->config('vp_visual_editor.settings')->get('debug'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vp_visual_editor.settings')
      ->set('debug', $form_state->getValue('debug'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
