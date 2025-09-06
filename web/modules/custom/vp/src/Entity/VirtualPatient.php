<?php

namespace Drupal\vp\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\vp\VirtualPatientInterface;

/**
 * Defines the virtual patient entity class.
 *
 * @ContentEntityType(
 *   id = "virtual_patient",
 *   label = @Translation("Virtual Patient"),
 *   label_collection = @Translation("Virtual Patients"),
 *   label_singular = @Translation("virtual patient"),
 *   label_plural = @Translation("virtual patients"),
 *   label_count = @PluralTranslation(
 *     singular = "@count virtual patients",
 *     plural = "@count virtual patients",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\vp\VirtualPatientListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\vp\VirtualPatientAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\vp\Form\VirtualPatientForm",
 *       "edit" = "Drupal\vp\Form\VirtualPatientForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "virtual_patient",
 *   data_table = "virtual_patient_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer virtual patient",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/virtual-patient",
 *     "add-form" = "/virtual-patient/add",
 *     "canonical" = "/virtual-patient/{virtual_patient}",
 *     "edit-form" = "/virtual-patient/{virtual_patient}/edit",
 *     "delete-form" = "/virtual-patient/{virtual_patient}/delete",
 *   },
 *   field_ui_base_route = "entity.virtual_patient.settings",
 * )
 */
class VirtualPatient extends ContentEntityBase implements VirtualPatientInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      $this->setOwnerId(1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions(
              'form', [
                'type' => 'string_textfield',
                'weight' => -5,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -5,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the virtual patient was last edited.'));

    $fields['field_vp_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Preview image'))
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => -2,
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => -2,
      ])
      ->setSettings([
        'file_directory' => 'vp-images',
        'alt_field_required' => TRUE,
        'file_extensions' => 'png jpg jpeg',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setReadOnly(TRUE);

    $fields['field_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -1,
      ])
      ->setSetting('allowed_formats', [0 => 'vp_html'])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_vp_nodes'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('VP Nodes'))
      ->setSetting('target_type', 'vp_node')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'label' => 'above',
                'weight' => 15,
              ]
          )
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions(
              'form', [
                'type' => 'boolean_checkbox',
                'settings' => [
                  'display_label' => FALSE,
                ],
                'weight' => 16,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'type' => 'boolean',
                'label' => 'above',
                'weight' => 16,
                'settings' => [
                  'format' => 'enabled-disabled',
                ],
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions(
              'form', [
                'type' => 'entity_reference_autocomplete',
                'settings' => [
                  'match_operator' => 'CONTAINS',
                  'size' => 60,
                  'placeholder' => '',
                ],
                'weight' => 20,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'label' => 'above',
                'type' => 'author',
                'weight' => 20,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the virtual patient was created.'))
      ->setDisplayOptions(
              'view', [
                'label' => 'above',
                'type' => 'timestamp',
                'weight' => 21,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'form', [
                'type' => 'datetime_timestamp',
                'weight' => 21,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_visual_metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Visual metadata'))
      ->setTranslatable(FALSE);

    return $fields;
  }

}
