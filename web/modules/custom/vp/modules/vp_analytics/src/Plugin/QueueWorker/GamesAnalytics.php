<?php

declare(strict_types=1);

namespace Drupal\vp_analytics\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\vp_analytics\VpAnalyticsPostData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'vp_analytics_games' queue worker.
 *
 * @QueueWorker(
 *   id = "vp_analytics_games",
 *   title = @Translation("GamesAnalytics"),
 *   cron = {"time" = 60},
 * )
 */
final class GamesAnalytics extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new GamesAnalytics instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly VpAnalyticsPostData $vpAnalyticsPostData,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('vp_analytics.post'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    /** @var \Drupal\vp_analytics\Entity\VpAnalytics $entity */
    $entity = $this->entityTypeManager->getStorage('vp_analytics')->load($data);
    if ($entity) {
      $this->vpAnalyticsPostData->postData($entity);
    } else {
      $this->loggerFactory->get('vp_analytics')->error('Failed to load VpAnalytics entity with ID: @id.', ['@id' => $data]);
    }
  }
}
