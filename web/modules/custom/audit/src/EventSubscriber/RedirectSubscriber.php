<?php

namespace Drupal\audit\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a RedirectSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(AccountProxyInterface $current_user, LoggerChannelFactoryInterface $logger_factory) {
    $this->currentUser = $current_user;
    $this->loggerFactory = $logger_factory;
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
      return;
    }

    // Get route and path information.
    $route_name = $event->getRequest()->attributes->get('_route');
    $path = $event->getRequest()->getPathInfo();
    $is_authenticated = $this->currentUser->isAuthenticated();

    // Check if the user is authenticated and the request is for the front page, /, or /user/login.
    if ($is_authenticated && ($route_name === 'system.front' || $route_name === 'user.login' || $path === '/' || $path === '/user/login')) {
      $this->loggerFactory->get('audit')->debug('Redirecting authenticated user to /dashboard');
      $response = new TrustedRedirectResponse('/dashboard');
      $response->getCacheableMetadata()->setCacheContexts(['user.roles:authenticated']);
      $event->setResponse($response);
    } else {
      // Do nothing for now
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
