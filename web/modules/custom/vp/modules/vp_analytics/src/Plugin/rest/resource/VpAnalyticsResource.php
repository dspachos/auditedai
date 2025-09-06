<?php

namespace Drupal\vp_analytics\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Drupal\vp_analytics\Entity\VpAnalytics;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents analytics records as resources.
 *
 * @RestResource (
 *   id = "vp_analytics",
 *   label = @Translation("VpAnalytics"),
 *   uri_paths = {
 *     "create" = "/api/vp/analytics",
 *     "canonical" = "/api/vp/analytics"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class VpAnalyticsResource extends ResourceBase {

  /**
   * The key-value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    KeyValueFactoryInterface $keyValueFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $keyValueFactory);
    $this->storage = $keyValueFactory->get('vp_analytics');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('keyvalue')
    );
  }

  /**
   * Responds to GET requests and retrieves the record.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function get() {

    $data = \Drupal::request()->query->all();
    \Drupal::logger('vp')->debug("<pre>" . print_r($data, TRUE) . "</pre>");

    $uid = $data['uid'] ?? NULL;
    $uuid = $data['uuid'] ?? NULL;

    if (!$uuid) {
      return new ModifiedResourceResponse('Virtual patient parameter is missing', 404);
    }

    $user = NULL;
    if ($uid == 0 || $uid == NULL) {
      // ..
    } else {
      $user = User::load($uid);
      if (!$user) {
        return new ModifiedResourceResponse('User not found', 404);
      }
    }

    $storage = \Drupal::entityTypeManager()->getStorage('virtual_patient');
    $virtual_patient = $storage->loadByProperties(['uuid' => $uuid]);
    $virtual_patient = reset($virtual_patient);
    if (!$virtual_patient) {
      return new ModifiedResourceResponse('Virtual patient not found', 404);
    }

    $email = $data['email'] ?? NULL;
    $mail = $user ? $user->getEmail() : $email;

    if (empty($mail)) {
      return new ModifiedResourceResponse('Either a valid user id or email is required', 400);
    }

    $result = VpAnalytics::create(
      [
        'uid' => $user ? $user->id() : 0,
        'label' => $virtual_patient->label(),
        'field_user' => $user ?: NULL,
        'field_virtual_patient' => $virtual_patient,
        'field_email' => $mail,
        'field_score' => (string) $data['score'] ?: "0",
        'field_playtime' => (int) $data['playtime'] ?: 0,
      ]
    );
    $result->save();

    if (!$result) {
      return new ModifiedResourceResponse('A server error occured while creating virtual patient analytics entry.', 500);
    }

    $response_data = [
      'id' => $result->id(),
      'label' => $result->get('label')->value,
      'email' => $result->get('field_email')->value,
      'score' => $result->get('field_score')->value,
      'playtime' => $result->get('field_playtime')->value,
    ];

    return new ModifiedResourceResponse($response_data, 201);
  }

  /**
   * Responds to OPTIONS requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function options() {

    return new ModifiedResourceResponse(NULL, 200);
  }

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post(array $data = []) {

    $uid = $data['uid'] ?? NULL;
    $uuid = $data['uuid'] ?? NULL;

    if (!$uuid) {
      return new ModifiedResourceResponse('Virtual patient parameter is missing', 404);
    }

    if (!$uid) {
      // Return new ModifiedResourceResponse('User parameter is missing', 404);.
    }

    $user = NULL;
    if ($uid == 0 || $uid == NULL) {
      // ..
    } else {
      $user = User::load($uid);
      if (!$user) {
        return new ModifiedResourceResponse('User not found', 404);
      }
    }

    $storage = \Drupal::entityTypeManager()->getStorage('virtual_patient');
    $virtual_patient = $storage->loadByProperties(['uuid' => $uuid]);
    $virtual_patient = reset($virtual_patient);
    if (!$virtual_patient) {
      return new ModifiedResourceResponse('Virtual patient not found', 404);
    }

    $email = $data['email'] ?? NULL;
    $mail = $user ? $user->getEmail() : $email;

    // @todo Check if mail exists,l otherwise return a proper code.
    if (empty($mail)) {
      return new ModifiedResourceResponse('Either a valid user id or email is required', 400);
    }

    $result = VpAnalytics::create(
      [
        'uid' => $user ?: 0,
        'label' => $virtual_patient->label(),
        'field_user' => $user ?: NULL,
        'field_virtual_patient' => $virtual_patient,
        'field_email' => $mail,
        'field_score' => (string) $data['score'] ?: "0",
        'field_playtime' => (int) $data['playtime'] ?: 0,
        'field_pilot_code' => (string) $data['field_pilot_code'] ?: NULL,
        'field_success' => (bool) $data['field_success'] ?: FALSE,
        'field_external_user_id' => (string) $data['field_external_user_id'] ?: NULL,
        'field_profile_id' => (string) $data['field_profile_id'] ?: NULL,
      ]
    );
    $result->save();

    $response_data = [
      'id' => $result->id(),
      'label' => $result->get('label')->value,
      'email' => $result->get('field_email')->value,
      'score' => $result->get('field_score')->value,
      'playtime' => $result->get('field_playtime')->value,
      'success' => $result->get('field_success')->value,
    ];

    return new ModifiedResourceResponse($response_data, 201);
  }

  // POST Example
  // POST /api/vp/analytics Content-Type: application/json
  // {
  //    "uid": 123,
  //    "uuid":
  //    "550e8400-e29b-41d4-a716-446655440000",
  //    "email": "user@example.com",
  //    "score": 85,
  //    "playtime": 3600
  // }.

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    return $route;
  }
}
