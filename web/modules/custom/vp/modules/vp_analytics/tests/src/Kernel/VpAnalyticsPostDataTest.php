<?php

declare(strict_types=1);

namespace Drupal\Tests\vp_analytics\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\vp_analytics\Entity\VpAnalytics;
use Drupal\vp_analytics\VpAnalyticsPostData;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\vp_analytics\VpAnalyticsPostData
 * @group vp_analytics
 */
class VpAnalyticsPostDataTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'rest',
    'serialization',
    'vp',
    'vp_analytics',
  ];

  /**
   * The service under test.
   */
  private VpAnalyticsPostData $vpAnalyticsPostData;

  /**
   * The mocked HTTP client.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\GuzzleHttp\ClientInterface>
   */
  private $httpClient;

  /**
   * The mocked logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  private $logger;

  /**
   * The entity type manager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('vp_analytics');
    $this->installConfig(['field', 'vp_analytics']);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->httpClient = $this->prophesize(ClientInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    $loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $loggerFactory->get('vp_analytics')->willReturn($this->logger->reveal());

    $configFactory = $this->container->get('config.factory');
    $currentUser = $this->container->get('current_user');

    // Set environment variables for testing.
    putenv('ENDPOINT_URL=https://test.endpoint/api');
    putenv('BEL=bel_api_key');
    putenv('CYP=cyp_api_key');
    putenv('GRC=grc_api_key');

    $this->vpAnalyticsPostData = new VpAnalyticsPostData(
      $this->httpClient->reveal(),
      $configFactory,
      $loggerFactory->reveal(),
      $currentUser,
      $this->entityTypeManager
    );
  }

  /**
   * Creates a VpAnalytics entity for testing.
   */
  private function createVpAnalyticsEntity(array $values): VpAnalytics {
    $default_values = [
      'field_pilot_code' => 'GRC',
      'field_virtual_patient' => 1,
      'field_profile_id' => 'profile-123',
      'field_playtime' => 3600,
      'field_score' => 85.5,
      'field_success' => TRUE,
    ];
    $values = array_merge($default_values, $values);
    $entity = VpAnalytics::create($values);
    $entity->save();
    return $entity;
  }

  /**
   * @covers ::postData
   */
  public function testPostDataSuccess(): void {
    $entity = $this->createVpAnalyticsEntity([]);

    $this->httpClient->request('POST', 'https://test.endpoint/api', [
      'headers' => [
        'X-Api-Key' => 'grc_api_key',
        'X-Pilot-Code' => 'GRC',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'json' => [
        [
          'vpId' => 1,
          'profileId' => 'profile-123',
          'time' => 3600,
          'score' => 85.5,
          'succeeded' => TRUE,
        ],
      ],
    ])->willReturn(new Response(200));

    $this->logger->info('Successfully posted analytics data for entity ID: @id, Pilot: @pilot.', [
      '@id' => $entity->id(),
      '@pilot' => 'GRC',
    ])->shouldBeCalled();

    $this->vpAnalyticsPostData->postData($entity);
  }

  /**
   * @covers ::postData
   */
  public function testPostDataMissingPilotCode(): void {
    $entity = $this->createVpAnalyticsEntity(['field_pilot_code' => NULL]);

    $this->logger->error('Missing pilot code for VpAnalytics entity ID: @id.', ['@id' => $entity->id()])
      ->shouldBeCalled();

    $this->httpClient->request(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();

    $this->vpAnalyticsPostData->postData($entity);
  }

  /**
   * @covers ::postData
   */
  public function testPostDataInvalidPilotCode(): void {
    $entity = $this->createVpAnalyticsEntity(['field_pilot_code' => 'INVALID']);

    $this->logger->error('Invalid pilot code "@pilot_code" for VpAnalytics entity ID: @id.', [
      '@pilot_code' => 'INVALID',
      '@id' => $entity->id(),
    ])->shouldBeCalled();

    $this->httpClient->request(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();

    $this->vpAnalyticsPostData->postData($entity);
  }

  /**
   * @covers ::postData
   */
  public function testPostDataMissingRequiredField(): void {
    $entity = $this->createVpAnalyticsEntity(['field_playtime' => NULL]);

    $this->logger->error('One or more required fields are missing for VpAnalytics entity ID: @id.', ['@id' => $entity->id()])
      ->shouldBeCalled();

    $this->httpClient->request(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();

    $this->vpAnalyticsPostData->postData($entity);
  }

  /**
   * @covers ::postData
   */
  public function testPostDataRequestException(): void {
    $entity = $this->createVpAnalyticsEntity([]);
    $request = new Request('POST', 'https://test.endpoint/api');
    $exception = new RequestException('Error Communicating with Server', $request);

    $this->httpClient->request(Argument::any(), Argument::any(), Argument::any())
      ->willThrow($exception);

    $this->logger->error('Error posting analytics data for entity ID: @id. Message: @message', [
      '@id' => $entity->id(),
      '@message' => 'Error Communicating with Server',
    ])->shouldBeCalled();

    $this->vpAnalyticsPostData->postData($entity);
  }

  /**
   * @covers ::postData
   */
  public function testPostDataApiError(): void {
    $entity = $this->createVpAnalyticsEntity([]);

    $this->httpClient->request('POST', 'https://test.endpoint/api', Argument::any())
      ->willReturn(new Response(400, [], 'Bad Request'));

    $this->logger->error('Failed to post analytics data for entity ID: @id. Status: @status, Response: @response', [
      '@id' => $entity->id(),
      '@status' => 400,
      '@response' => 'Bad Request',
    ])->shouldBeCalled();

    $this->vpAnalyticsPostData->postData($entity);
  }

}
