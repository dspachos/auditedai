<?php

namespace Drupal\vp_visual_editor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\vp\Entity\VirtualPatient;

/**
 * Returns responses for VP Visual Editor routes.
 */
class VpVisualEditorController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Builds the response.
   */
  public function build(?VirtualPatient $virtual_patient = NULL) {

    /** @var \Drupal\vp\Entity\VirtualPatient $entity */
    $entity = $virtual_patient;

    // @todo Add DI
    $service = \Drupal::service('vp_visual_editor.visual_editing');
    $data = $service->getVisualRepresentationData($entity);
    $build['#attached']['drupalSettings']['visualData'] = $data;
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $current_path = \Drupal::service('path.current')->getPath();

    $content_language = \Drupal::service('vp.service')->getContentLangcode();

    if ($entity && $entity->getUntranslated()->language()->getId() == $content_language) {
      $build['#attached']['drupalSettings']['vp'] = $entity->id();
      $build['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Add +'),
        '#url' => Url::fromRoute('entity.vp_node.add_form')->setOption('query', [
          'destination' => $current_path,
          'nid' => $entity->id(),
        ]),
        '#ajax' => [
          'dialogType' => 'modal',
          'dialog' => ['width' => 800],
        ],
        '#attributes' => [
          'class' => [
            'use-ajax button button--primary js-form-submit form-submit js-button-add',
          ],
        ],
      ];
    }
    else {
      $build['info'] = [
        '#type' => 'markup',
        '#markup' => Markup::create($this->t('<em>You can create nodes in the original language only (@label)</em> ', [
          '@label' => $entity->getUntranslated()->language()->getName(),
        ])),
      ];
    }

    $build['help'] = [
      '#type' => 'link',
      '#title' => $this->t('<i class="bi bi-question-square"></i>'),
      '#url' => Url::fromRoute('vp_visual_editor.help'),
      '#ajax' => [
        'dialogType' => 'modal',
        'dialog' => ['width' => 800],
      ],
    ];

    $build['save_info'] = [
      '#type' => 'markup',
      '#markup' => Markup::create($this->t('<div class="align-right visual-message"><em id="visual-message">@message</em></div>', [
        '@message' => $this->t('All changes saved <i class="bi bi-check-lg success"></i>'),
      ])),
    ];

    $build['links_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'hidden',
        ],
      ],
    ];
    $nodes = $entity->field_vp_nodes->referencedEntities();

    foreach ($nodes as $node) {
      $build['links_container']["link_{$node->id()}"] = [
        '#type' => 'link',
        '#title' => $this->t('Edit'),
        '#url' => Url::fromRoute('entity.vp_node.edit_form', ['vp_node' => $node->id()])->setOption('query', [
          'destination' => $current_path,
        ]),
        '#ajax' => [
          'dialogType' => 'modal',
          'dialog' => ['width' => 800],
        ],
        '#attributes' => [
          'id' => "edit-node-{$node->id()}",
          'class' => [
            'use-ajax',
          ],
        ],
      ];
    }

    $build['visual-container'] = [
      '#type' => 'container',
      '#prefix' => '<div class="visual-container wrapper"><div id="drawflow" ondrop="drop(event)" ondragover="allowDrop(event)">',
      '#suffix' => '</div></div>',
    ];

    $build['#attached']['library'][] = 'vp_visual_editor/drawflow';
    $build['#attached']['library'][] = 'vp_visual_editor/vp_visual_editor';
    $build['#attached']['library'][] = 'vp/vp';
    return $build;
  }

  /**
   * Route title callback.
   */
  public function getTitle(?VirtualPatient $virtual_patient = NULL) {
    return $virtual_patient ? $virtual_patient->label() : '';
  }

  /**
   * Display help for the visual editor.
   */
  public function help(?VirtualPatient $virtual_patient = NULL) {
    $build['container'] = [
      '#type' => 'container',
    ];

    $build['container']['info_box_1_2'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<kbd>Click</kbd> on the <kbd>Add +</kbd> button to add a node.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['container']['info_box_1_3'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<kbd>Drag</kbd> and <kbd>drop</kbd> from a white output circle to a yellow input circle to connect nodes.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['container']['info_box_2'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<kbd>Double click</kbd> to edit a node.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['container']['info_box_3'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<kbd>Right click</kbd> to delete a node.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['container']['info_box_4'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<kbd>Right click</kbd> on a connector line to delete a connector.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['container']['info_box_4_1'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<kbd>Control</kbd> + <kbd>mouse scroll</kbd> to zoom in/out the visual editor area.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['#attached']['library'][] = 'vp/vp';
    return $build;
  }

  /**
   * Route title callback.
   */
  public function getHelpTitle(?VirtualPatient $virtual_patient = NULL) {
    return $this->t('Visual editor help');
  }

}
