<?php

declare(strict_types=1);

namespace Drupal\vp_analytics;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\vp_analytics\Entity\VpAnalytics;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Service to post VP analytics data to an external endpoint.
 */
final class VpAnalyticsPostData {

  /**
   * The logger channel.
   */
  private LoggerInterface $logger;

  /**
   * API keys mapping for different pilot codes.
   */
  private array $apiKeys;

  /**
   * The endpoint URL to post data to.
   */
  private string $endpointUrl;

  /**
   * Constructs a VpAnalyticsPostData object.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly AccountProxyInterface $currentUser,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->logger = $loggerFactory->get('vp_analytics');
    $this->apiKeys = [
      'BEL' => getenv("BEL"),
      'CYP' => getenv("CYP"),
      'GRC' => getenv("GRC"),
      'ITA' => getenv("ITA"),
      'ROU' => getenv("ROU"),
      'SVN' => getenv("SVN"),
      'CHE' => getenv("CHE"),
      'ES-VC' => getenv("ES-VC"),
      'ES-PV' => getenv("ES-PV"),
    ];
    $this->endpointUrl = getenv("ENDPOINT_URL");
  }

  /**
   * Posts analytics data to the external endpoint.
   *
   * @param \Drupal\vp_analytics\Entity\VpAnalytics $entity
   *   The analytics entity.
   */
  public function postData(VpAnalytics $entity): void {

    $pilotCode = $entity->get('field_pilot_code')->value;
    if (empty($pilotCode)) {
      $this->logger->error('Missing pilot code for VpAnalytics entity ID: @id.', ['@id' => $entity->id()]);
      return;
    }

    if (!isset($this->apiKeys[$pilotCode])) {
      $this->logger->error('Invalid pilot code "@pilot_code" for VpAnalytics entity ID: @id.', [
        '@pilot_code' => $pilotCode,
        '@id' => $entity->id(),
      ]);
      return;
    }

    $apiKey = $this->apiKeys[$pilotCode];

    // Validate required fields.
    $vpId = $entity->get('field_virtual_patient')->target_id;
    $profileId = $entity->get('field_profile_id')->value;
    $playtime = $entity->get('field_playtime')->value;
    $score = $entity->get('field_score')->value;
    $success = $entity->get('field_success')->value;

    if (empty($vpId) || empty($profileId) || !isset($playtime) || !isset($score) || !isset($success)) {
      $this->logger->error('One or more required fields are missing for VpAnalytics entity ID: @id.', ['@id' => $entity->id()]);
      return;
    }

    $payload = [
      [
        'vpId' => (string) $vpId,
        'profileId' => (int) $profileId,
        'time' => (int) $playtime,
        'score' => (int) $score,
        'succeeded' => (int) $success,
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', $this->endpointUrl, [
        'headers' => [
          'X-Api-Key' => $apiKey,
          'X-Pilot-Code' => $pilotCode,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'json' => $payload,
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode >= 200 && $statusCode < 300) {
        $this->logger->info('Successfully posted analytics data for entity ID: @id, Pilot: @pilot.', [
          '@id' => $entity->id(),
          '@pilot' => $pilotCode,
        ]);
      } else {
        $this->logger->error('Failed to post analytics data for entity ID: @id. Status: @status, Response: @response', [
          '@id' => $entity->id(),
          '@status' => $statusCode,
          '@response' => $response->getBody()->getContents(),
        ]);
      }
    } catch (RequestException $e) {
      $this->logger->error('Error posting analytics data for entity ID: @id. Message: @message', [
        '@id' => $entity->id(),
        '@message' => $e->getMessage(),
      ]);
      if ($e->hasResponse()) {
        $this->logger->error('Response body: @response', [
          '@response' => $e->getResponse()->getBody()->getContents(),
        ]);
      }
    }
  }
}
