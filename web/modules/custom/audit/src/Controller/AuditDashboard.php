<?php

declare(strict_types=1);

namespace Drupal\audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Returns responses for AuditED AI routes.
 */
final class AuditDashboard extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    AccountInterface $current_user,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('logger.factory'),
      $container->get('config.factory')
    );
  }

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $build = [];

    // Attach our custom libraries
    $build['#attached']['library'][] = 'audit/audit-node-view';
    $build['#attached']['library'][] = 'audit/standards-tags';

    // Add title
    $build['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Audit Dashboard') . '</h2>',
    ];

    // Add button to create a new audit
    $build['new_audit'] = [
      '#type' => 'link',
      '#title' => $this->t('Create New Audit'),
      '#url' => \Drupal\Core\Url::fromRoute('node.add', ['node_type' => 'audit']),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'button--large'],
      ],
    ];

    // Load user's audit entities
    $user_audits = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'audit',
        'uid' => $this->currentUser->id(),
      ]);

    // Sort by creation date (newest first)
    uasort($user_audits, function ($a, $b) {
      return $b->get('created')->value <=> $a->get('created')->value;
    });

    if (!empty($user_audits)) {
      // Create table header
      $header = [
        $this->t('Audit Title'),
        $this->t('Standards'),
        $this->t('Audit Type'),
        $this->t('Created'),
      ];

      $rows = [];
      foreach ($user_audits as $audit) {
        /** @var \Drupal\node\NodeInterface $audit */

        // Build audit type tags
        $standards_tags = [];
        if ($audit->hasField('field_eqavet') && !$audit->get('field_eqavet')->isEmpty() && $audit->get('field_eqavet')->value) {
          $standards_tags[] = '<span class="standard-tag eqavet-tag">EQAVET</span>';
        }

        if ($audit->hasField('field_iso') && !$audit->get('field_iso')->isEmpty() && $audit->get('field_iso')->value) {
          $standards_tags[] = '<span class="standard-tag iso-tag">ISO 21001</span>';
        }

        $standards_markup = '';
        if (!empty($standards_tags)) {
          $standards_markup = '<div class="standards-tags">' . implode(' ', $standards_tags) . '</div>';
        }

        // Get audit type
        $audit_type = '';
        if ($audit->hasField('field_audit_type') && !$audit->get('field_audit_type')->isEmpty()) {
          $audit_type_entity = $audit->get('field_audit_type')->entity;
          if ($audit_type_entity) {
            $audit_type = $audit_type_entity->label();
          }
        }

        // Format creation date
        $created_date = \Drupal::service('date.formatter')->format(
          $audit->get('created')->value,
          'short'
        );

        $rows[] = [
          'data' => [
            [
              'data' => [
                '#type' => 'link',
                '#title' => $audit->label(),
                '#url' => $audit->toUrl(),
              ],
            ],
            [
              'data' => [
                '#type' => 'markup',
                '#markup' => $standards_markup,
              ],
            ],
            $audit_type,
            $created_date,
          ],
        ];
      }

      $build['audit_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => [
          'class' => ['audit-dashboard-table'],
        ],
        '#responsive' => TRUE,
        '#sticky' => TRUE,
      ];
    } else {
      $build['no_audits'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('You have not created any audits yet.') . '</p>',
      ];
    }

    return $build;
  }
}
