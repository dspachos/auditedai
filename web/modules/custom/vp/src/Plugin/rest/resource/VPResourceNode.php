<?php

namespace Drupal\vp\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents VPNode records as resources.
 *
 * @RestResource (
 *   id = "vp_rest_node",
 *   label = @Translation("VP Node"),
 *   uri_paths = {
 *     "canonical" = "/api/vp/node/{langcode}/{uuid}",
 *     "create" = "/api/vp/node/{langcode}/{uuid}"
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
class VPResourceNode extends ResourceBase {

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
   * @param string $langcode
   *   The langcode id.
   * @param string $uuid
   *   The uuid of the virtual patient.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   */
  public function get($langcode, $uuid, $preview = NULL) {
    $headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST, GET',
      'Access-Control-Allow-Headers' => 'Authorization',
    ];

    $vp = \Drupal::entityTypeManager()->getStorage('virtual_patient')->loadByProperties(['uuid' => $uuid]);
    $vp = reset($vp);
    if ($vp == FALSE) {
      return new ResourceResponse([], 404);
    }

    if (!$langcode) {
      $language = \Drupal::languageManager()->getCurrentLanguage();
      $langcode = $language->getId();
    }

    // @todo Add published check
    // @todo Fallback to default language
    $translated = $vp->hasTranslation($langcode) ? $vp->getTranslation($langcode) : NULL;
    $response = [];

    if (!$translated) {
      $message = $this->t("The translation either does not exist or it's unpublished");
      $response = new ResourceResponse(['message' => $message], 404, $headers);
      $response->addCacheableDependency(
        CacheableMetadata::createFromRenderArray(
          [
            '#cache' => [
              'tags' => [
                'virtual_patient:' . $vp->id(),
              ],
            ],
          ]
        )
      );
      return $response;
    }

    if ($translated) {

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

      $image_source = $translated->field_vp_image->entity ? $translated->field_vp_image->entity->createFileUrl(FALSE) : NULL;
      $response = [
        'uuid' => $translated->uuid->value,
        'title' => $translated->label(),
        'created' => $translated->created->value,
        'vp_age' => \Drupal::service('date.formatter')->formatTimeDiffSince($translated->created->value),
        'langcode' => $translated->langcode->value,
        'description' => $translated->field_description->value,
        'image' => $image_source,
        'categories' => $categories,
        'intended_for' => $inteded_for,
      ];
      $nodes = $vp->field_vp_nodes->referencedEntities();
      $vp_nodes = [];
      foreach ($nodes as $node) {
        $node_image_url = NULL;
        $image_source = $node->field_image->entity ? $node->field_image->entity->createFileUrl(FALSE) : NULL;
        $video_url = $node->field_mp4_video->entity ? $node->field_mp4_video->entity->createFileUrl(FALSE) : NULL;

        $file = $node->field_image->entity;
        if ($file) {
          $image_uri = $file->getFileUri();
          $style = ImageStyle::load('vp_node_image');
          $node_image_url = $style->buildUrl($image_uri);
        }

        $translated = $node->hasTranslation($langcode) ? $node->getTranslation($langcode) : $node;
        $options = $translated->field_options->referencedEntities();
        $vp_nodes[] = [
          'uuid' => $translated->uuid->value,
          'title' => $this->removeNodeOccurrences($translated->label()),
          'subtitle' => $translated->field_subtitle->value,
          'created' => $translated->created->value,
          'content' => $translated->field_content->value,
          // @todo use style for image URI
          'image' => $node_image_url,
          'videoUrl' => $video_url,
          'root_node' => (bool) $translated->field_root_node->value,
          'terminal_node' => (bool) $translated->field_terminal_node->value,
          'score' => (int) $translated->field_score->value,
          'options' => array_map(
            function ($item) use ($langcode) {
              $translated_item = $item->hasTranslation($langcode) ? $item->getTranslation($langcode) : $item;
              return ['uuid' => $translated_item->uuid->value, 'label' => $this->removeNodeOccurrences($translated_item->label())];
            },
            $options
          ),
        ];
      }
      $response['vp_nodes'] = $vp_nodes;
    }

    $response = new ResourceResponse($response, 200, $headers);
    $response->addCacheableDependency(
      CacheableMetadata::createFromRenderArray(
        [
          '#cache' => [
            'tags' => [
              'virtual_patient:' . $vp->id(),
            ],
          ],
        ]
      )
    );
    return $response;
  }

  /**
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response objects
   */
  public function post(array $data = []) {
    $headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST',
      'Access-Control-Allow-Headers' => 'Content-Type',
    ];
    // @todo Update data
    $response = new ResourceResponse(['ok' => TRUE], 200, $headers);
    return $response;
  }

  /**
   * Replace occurrences of [Node X] with an empty string.
   * Matches [Node X] where X is any number.
   *
   * @param string $inputString
   *   The input string to process.
   *
   * @return string The input string with [Node X] occurrences removed
   */
  private function removeNodeOccurrences($inputString) {
    // Replace occurrences of [Node X] with an empty string.
    // Matches [Node X] where X is any number.
    // Matches anything within square brackets.
    $pattern = '/\[.*?\]/';
    $cleanedString = preg_replace($pattern, '', $inputString);
    return $cleanedString;
  }

}
