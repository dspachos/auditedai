<?php

declare(strict_types=1);

namespace Drupal\audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for registration success page.
 */
final class RegistrationSuccessController extends ControllerBase {

  /**
   * Displays the registration success message.
   */
  public function registrationSuccess() {
    $build = [];

    $build['#markup'] = '<div class="registration-success">' .
      '<h2>' . $this->t('Welcome! Thanks for your registration') . '</h2>' .
      '<p>' . $this->t('Thank you for registering with our site. Your account has been created successfully.') . '</p>' .
      '<p>' . $this->t('You can now log in to your account and start using our services.') . '</p>' .
      '<div class="registration-success-actions">' .
      '<a href="/user/login" class="button button--primary">' . $this->t('Log in to your account') . '</a> ' .
      '<a href="/" class="button button--secondary">' . $this->t('Go to homepage') . '</a>' .
      '</div>' .
      '</div>';

    return $build;
  }

}