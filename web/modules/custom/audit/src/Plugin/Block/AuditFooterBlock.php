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
 * Provides a 'AuditFooterBlock' block.
 *
 * @Block(
 *   id = "audit_footer_block",
 *   admin_label = @Translation("Audit footer block"),
 * )
 */
class AuditFooterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    // Only show footer to authenticated users
    if (!$this->currentUser->isAnonymous()) {
      $build['content'] = [
        '#type' => 'markup',
        '#markup' => '
          <footer class="audit-footer">
            <div class="audit-footer-container">
              <div class="audit-footer-column audit-footer-column-1">
                <h4>About</h4>
                <ul>
                  <li><a href="/about">About</a></li>
                  <li><a href="/feedback">Feedback</a></li>
                </ul>
              </div>
              
              <div class="audit-footer-column audit-footer-column-2">
                <h4>Project</h4>
                <ul>
                  <li><a href="/project">Project</a></li>
                  <li><a href="/contribute">Contribute</a></li>
                  <li><a href="/contact">Contact</a></li>
                </ul>
              </div>
              
              <div class="audit-footer-column audit-footer-column-3">
                <div class="audit-footer-logo-placeholder">
                  <!-- Logo and text will be added here manually -->
                  <p>Logo and additional information here</p>
                </div>
              </div>
            </div>
          </footer>',
        '#prefix' => '<div class="audit-footer-wrapper">',
        '#suffix' => '</div>',
      ];

      // Add CSS for styling the footer
      $build['#attached']['library'][] = 'audit/audit-footer';
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

}