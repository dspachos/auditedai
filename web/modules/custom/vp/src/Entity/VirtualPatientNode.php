<?php

namespace Drupal\vp\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;
use Drupal\vp\VpNodeInterface;

/**
 * Defines the vp node entity class.
 *
 * @ContentEntityType(
 *   id = "vp_node",
 *   label = @Translation("Virtual Patient Node"),
 *   label_collection = @Translation("Virtual Patient Nodes"),
 *   label_singular = @Translation("virtual patient node"),
 *   label_plural = @Translation("virtual patient nodes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count virtual patient nodes",
 *     plural = "@count virtual patient nodes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\vp\VpNodeListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\vp\VpNodeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\vp\Form\VirtualPatientNodeForm",
 *       "add" = "Drupal\vp\Form\VirtualPatientNodeForm",
 *       "edit" = "Drupal\vp\Form\VirtualPatientNodeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "vp_node",
 *   data_table = "vp_node_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer vp node",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/vp-node",
 *     "add-form" = "/vp-node/add",
 *     "canonical" = "/vp-node/{vp_node}",
 *     "edit-form" = "/vp-node/{vp_node}/edit",
 *     "delete-form" = "/vp-node/{vp_node}/delete",
 *   },
 *   field_ui_base_route = "entity.vp_node.settings",
 * )
 */
class VirtualPatientNode extends ContentEntityBase implements VpNodeInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => -5,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -5,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
          ],
          'weight' => 15,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'author',
          'weight' => 15,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the vp node was created.'))
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'timestamp',
          'weight' => 20,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'datetime_timestamp',
          'weight' => 20,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the vp node was last edited.'));

    $fields['field_subtitle'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Subtitle'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => -4,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -4,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => -1,
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => -1,
      ])
      ->setSettings([
        'file_directory' => 'vp-images/nodes',
        'alt_field_required' => TRUE,
        'file_extensions' => 'png jpg jpeg',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setReadOnly(TRUE);

    $fields['field_content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 6,
      ])
      ->setSetting('allowed_formats', [0 => 'vp_html'])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_options'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Options'))
      ->setSetting('target_type', 'vp_node')
      ->setCardinality(5)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
          ],
          'weight' => 8,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'weight' => 8,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_root_node'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(FALSE)
      ->setLabel(t('Root node'))
      ->setSetting('on_label', 'On')
      ->setSetting('off_label', 'Off')
      ->setDisplayOptions(
        'form',
        [
          'type' => 'boolean_checkbox',
          'settings' => [
            'display_label' => TRUE,
          ],
          'weight' => 10,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'type' => 'boolean',
          'label' => 'above',
          'weight' => 10,
          'settings' => [
            'format' => 'enabled-disabled',
          ],
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_terminal_node'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(FALSE)
      ->setLabel(t('Terminal node'))
      ->setSetting('on_label', 'On')
      ->setSetting('off_label', 'Off')
      ->setDisplayOptions(
        'form',
        [
          'type' => 'boolean_checkbox',
          'settings' => [
            'display_label' => TRUE,
          ],
          'weight' => 12,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'type' => 'boolean',
          'label' => 'above',
          'weight' => 12,
          'settings' => [
            'format' => 'enabled-disabled',
          ],
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_score'] = BaseFieldDefinition::create('integer')
      ->setTranslatable(FALSE)
      ->setLabel(t('Score'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 14,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_parent'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(FALSE)
      ->setLabel(t('Parent'))
      ->setSetting('target_type', 'virtual_patient')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setCardinality(1)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
          ],
          'weight' => 99,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'weight' => 99,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions(
        'form',
        [
          'type' => 'boolean_checkbox',
          'settings' => [
            'display_label' => FALSE,
          ],
          'weight' => 101,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'type' => 'boolean',
          'label' => 'above',
          'weight' => 101,
          'settings' => [
            'format' => 'enabled-disabled',
          ],
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
