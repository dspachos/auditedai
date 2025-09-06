<?php

namespace Drupal\vp\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents VPList records as resources.
 *
 * @RestResource (
 *   id = "vp_rest_list",
 *   label = @Translation("VP List"),
 *   uri_paths = {
 *     "canonical" = "/api/vp/list/{langcode}",
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
class VPResourceList extends ResourceBase {

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $config
   *   A configuration array which contains the information about the plugin instance.
   * @param string $module_id
   *   The module_id for the plugin instance.
   * @param mixed $module_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A currently logged user instance.
   */
  public function __construct(
    array $config,
    $module_id,
    $module_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $config, $module_id, $module_definition) {
    return new static(
      $config,
      $module_id,
      $module_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('sample_rest_resource'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param int $langcode
   *   The langcode id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   */
  public function get($langcode) {
    $nids = \Drupal::entityQuery('virtual_patient')
      ->condition('langcode', $langcode)
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->execute();
    // Delete old revisions.
    $storage_controller = \Drupal::entityTypeManager()->getStorage('virtual_patient');
    $vps = $storage_controller->loadMultiple($nids);

    $list = [];
    foreach ($vps as $vp) {
      $translated = $vp->hasTranslation($langcode) ? $vp->getTranslation($langcode) : NULL;
      if (!$translated) {
        continue;
      }

      $categories = [];
      if ($translated->field_katigoria->entity) {
        foreach ($translated->field_katigoria->referencedEntities() as $category) {
          $categories[] = $category->label();
        }
      }

      $inteded_for = [];
      if ($translated->field_apeythynetai_se->entity) {
        foreach ($translated->field_apeythynetai_se->referencedEntities() as $term) {
          $inteded_for[] = $term->label();
        }
      }

      global $base_url;
      $image_source = $translated->field_vp_image->entity ? $translated->field_vp_image->entity->createFileUrl(FALSE) : NULL;
      $list[] = [
        'uuid' => $translated->uuid->value,
        'title' => $translated->label(),
        'created' => $translated->created->value,
        'vp_age' => \Drupal::service('date.formatter')->formatTimeDiffSince($translated->created->value),
        'langcode' => $translated->langcode->value,
        'description' => $translated->field_description->value,
        'image' => $image_source,
        'categories' => $categories,
        'intended_for' => $inteded_for,
        'canonical' => "{$base_url}/api/vp/node/{$translated->langcode->value}/{$translated->uuid->value}",
      ];
    }

    $response = [
      'total' => count($list),
      'items' => $list,
    ];

    $headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST, GET',
      'Access-Control-Allow-Headers' => 'Authorization',
    ];

    $response = new ResourceResponse($response, 200, $headers);
    $response->addCacheableDependency(
      CacheableMetadata::createFromRenderArray(
        [
          '#cache' => [
            'tags' => [
              'virtual_patient_list',
            ],
          ],
        ]
      )
    );

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method != 'POST') {
      $route->setRequirement('id', '\d+');
    }
    return $route;
  }

}
