<?php

namespace Drupal\vp_visual_editor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\vp\Entity\VirtualPatient;
use Drupal\vp\Entity\VirtualPatientNode;

/**
 * Service description.
 */
class VisualEditing {

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
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a VisualEditing object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(RouteMatchInterface $route_match, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger) {
    $this->routeMatch = $route_match;
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Get the visual representation data for a VirtualPatient entity.
   *
   * @param \Drupal\vp\Entity\VirtualPatient $entity
   *   The VirtualPatient entity for which visual representation data is retrieved.
   */
  public function getVisualRepresentationData(VirtualPatient $entity) {
    $data = [];

    $nodes = $entity->field_vp_nodes->referencedEntities();

    $saved_data = $entity->field_visual_metadata->value ? json_decode($entity->field_visual_metadata->value, TRUE) : [];

    foreach ($nodes as $index => $node) {
      $nid = $node->id();
      $options = $node->field_options->referencedEntities();
      $connections = [];
      foreach ($options as $option) {
        $connections[] = [
          'node' => (int) $option->id(),
          'output' => "input_1",
        ];
      }
      $outputs['output_1']['connections'] = $connections;

      $vp_nodes = $entity->field_vp_nodes->referencedEntities();

      $input_ids = array_filter(array_map(function ($item) use ($node) {
        $input_ids = array_column($item->field_options->getValue(), 'target_id');
        if (in_array($node->id(), $input_ids)) {
          return (int) $item->id();
        }
      }, $vp_nodes));

      $connections = [];
      foreach ($input_ids as $id) {
        $connections[] = [
          'node' => (int) $id,
          'input' => "output_1",
        ];
      }
      $inputs['input_1']['connections'] = $connections;

      $saved_node = NULL;
      if (!empty($saved_data)) {
        $saved_node = array_filter($saved_data['drawflow']['Home']['data'], function ($item) use ($nid) {
          return $nid == $item['id'];
        });
      }

      $pos_x = $index * 250 + 50;
      $pos_y = $index * 50 + 50;
      if (!empty($saved_node)) {
        $saved_node = reset($saved_node);
        $pos_x = $saved_node['pos_x'] ?? $pos_x;
        $pos_y = $saved_node['pos_y'] ?? $pos_y;
      }

      $data[$node->id()] = [
        "id" => (int) $node->id(),
        "name" => "{$node->id()}",
        "data" => [],
        "class" => "visual-node visual-node-{$node->id()}",
        "html" => "{$node->label()}",
        "typenode" => FALSE,
        "inputs" => $inputs,
        "outputs" => $outputs,
        "pos_x" => $pos_x,
        "pos_y" => $pos_y,
      ];
    }

    return [
      'drawflow' => [
        'Home' => [
          'data' => $data,
        ],
      ],
    ];

  }

  /**
   * Save data for a VirtualPatient entity.
   *
   * @param string $data
   *   The data to be saved.
   * @param \Drupal\vp\Entity\VirtualPatient $entity
   *   The VirtualPatient entity to save the data for.
   *
   * @return void
   */
  public function saveData(string $data, VirtualPatient $entity) {
    if (empty($data)) {
      return;
      // $data = '{"drawflow":{"Home":{"data":{"1":{"id":1,"name":"1","data":[],"class":"visual-node visual-node-1","html":"Node 1","typenode":false,"inputs":{"input_1":{"connections":[{"node":3,"input":"output_1"}]}},"outputs":{"output_1":{"connections":[{"node":2,"output":"input_1"},{"node":3,"output":"input_1"}]}},"pos_x":50,"pos_y":50},"2":{"id":2,"name":"2","data":[],"class":"visual-node visual-node-2","html":"Node 2","typenode":false,"inputs":{"input_1":{"connections":[{"node":1,"input":"output_1"}]}},"outputs":{"output_1":{"connections":[{"node":3,"output":"input_1"}]}},"pos_x":329,"pos_y":375},"3":{"id":3,"name":"3","data":[],"class":"visual-node visual-node-3","html":"Node 3","typenode":false,"inputs":{"input_1":{"connections":[{"node":1,"input":"output_1"},{"node":2,"input":"output_1"}]}},"outputs":{"output_1":{"connections":[{"node":1,"output":"input_1"}]}},"pos_x":735,"pos_y":33},"5":{"id":5,"name":"5","data":[],"class":"visual-node visual-node-5","html":"Node 5§","typenode":false,"inputs":{"input_1":{"connections":[]}},"outputs":{"output_1":{"connections":[]}},"pos_x":1037,"pos_y":64},"6":{"id":6,"name":"6","data":[],"class":"visual-node visual-node-6","html":"Node without ID","typenode":false,"inputs":{"input_1":{"connections":[]}},"outputs":{"output_1":{"connections":[]}},"pos_x":1115,"pos_y":259}}}}}';
    }
    $postData = json_decode($data, TRUE);

    // @todo Split this into method
    $vp_nodes = array_column($entity->field_vp_nodes->getValue(), 'target_id');
    $vp_nodes = array_map(function ($item) {
      return (int) $item;
    }, $vp_nodes);
    $visual_vp_nodes = $this->getVpNodeIds($postData);
    if (array_values($visual_vp_nodes) !== array_values($vp_nodes)) {
      $entity->set('field_vp_nodes', NULL);
      foreach ($visual_vp_nodes as $vp_node) {
        $entity->field_vp_nodes[] = ['target_id' => $vp_node];
      }
      $entity->save();
    }

    foreach ($postData['drawflow']['Home']['data'] as $item) {
      $visual_connections = $this->getNodeConnectionIds($item, $postData);
      /** @var \Drupal\vp\Entity\VirtualPatientNode $node */
      $node = VirtualPatientNode::load($item['id']);
      if ($node) {
        $node_connections = array_column($node->field_options->getValue(), 'target_id');
        $node_connections = array_map(function ($item) {
          return (int) $item;

        }, $node_connections);
        if ($visual_connections !== $node_connections) {
          $node->set('field_options', NULL);
          foreach ($visual_connections as $connection) {
            $node->field_options[] = ['target_id' => $connection];
          }
          $node->save();
        }
      }
    }
  }

  /**
   * Get the ids of the nodes in the 'Home' section of the drawflow data.
   *
   * @param array $data
   *   The data containing drawflow information.
   *
   * @return array The array of node ids.
   */
  private function getVpNodeIds(array $data) {
    $ids = [];
    foreach ($data['drawflow']['Home']['data'] as $node) {
      $ids[] = $node['id'];
    }
    return $ids;
  }

  /**
   * Get the ids of nodes connected to a specific node in the 'Home' section of the drawflow data.
   *
   * @param array $node
   *   The node for which connections are being searched.
   * @param array $data
   *   The data containing drawflow information.
   *
   * @return array The array of connected node ids.
   */
  private function getNodeConnectionIds(array $node, array $data) {
    $target = $node['id'];
    $ids = [];
    foreach ($data['drawflow']['Home']['data'] as $node) {
      $connections = $node['inputs']['input_1']['connections'];
      foreach ($connections as $connection) {
        if ($connection['node'] == $target) {
          $ids[] = $node['id'];
        }
      }
    }
    return $ids;
  }

}
