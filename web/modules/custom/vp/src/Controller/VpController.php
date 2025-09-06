<?php

namespace Drupal\vp\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for VP routes.
 */
class VpController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
