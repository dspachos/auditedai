<?php

declare(strict_types=1);

namespace Drupal\audit\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'AuditTopMenuBlock' block.
 *
 * @Block(
 *   id = "audit_top_menu_block",
 *   admin_label = @Translation("Audit top menu block"),
 * )
 */
class AuditTopMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Only show menu to authenticated users
    if (!$this->currentUser->isAnonymous()) {
      $build['content'] = [
        '#type' => 'markup',
        '#markup' => '
          <nav class="audit-top-menu">
            <ul>
              <li><a href="/dashboard">Dashboard</a></li>
              <li><a href="/user">My Account</a></li>
            </ul>
          </nav>',
        '#prefix' => '<div class="audit-top-menu-wrapper">',
        '#suffix' => '</div>',
      ];

      // Add CSS for styling the menu
      $build['#attached']['library'][] = 'audit/audit-top-menu';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    // Only show block to authenticated users
    return AccessResult::allowedIf(!$account->isAnonymous());
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Only allow access to authenticated users
    $access = !$account->isAnonymous();
    return $return_as_object ? AccessResult::allowedIf($access) : $access;
  }
}
