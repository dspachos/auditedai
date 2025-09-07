<?php

namespace Drupal\audit\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber to redirect authenticated users from the front page to the dashboard.
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a RedirectSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Redirects authenticated users from the front page or /user/login to the dashboard.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function checkForRedirect(RequestEvent $event) {
    // Skip if not the main request.
    if (!$event->isMainRequest()) {
      \Drupal::logger('audit')->debug('Skipping sub-request');
      return;
    }

    // Get route and path information.
    $route_name = $event->getRequest()->attributes->get('_route');
    $path = $event->getRequest()->getPathInfo();
    $is_authenticated = $this->currentUser->isAuthenticated();

    // Log debugging information.
    \Drupal::logger('audit')->debug('Request details: route=@route, path=@path, authenticated=@auth', [
      '@route' => $route_name ?: 'none',
      '@path' => $path,
      '@auth' => $is_authenticated ? 'Yes' : 'No',
    ]);

    // Check if the user is authenticated and the request is for the front page, /, or /user/login.
    if ($is_authenticated && ($route_name === 'system.front' || $route_name === 'user.login' || $path === '/' || $path === '/user/login')) {
      \Drupal::logger('audit')->debug('Redirecting authenticated user to /dashboard');
      $response = new TrustedRedirectResponse('/dashboard');
      $response->getCacheableMetadata()->setCacheContexts(['user.roles:authenticated']);
      $event->setResponse($response);
    } else {
      \Drupal::logger('audit')->debug('No redirect: route or path does not match, or user not authenticated');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['checkForRedirect', 300],
    ];
  }
}
