<?php

namespace Drupal\vp_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;
use Drupal\vp_analytics\VpAnalyticsInterface;

/**
 * Defines the vp analytics entity class.
 *
 * @ContentEntityType(
 *   id = "vp_analytics",
 *   label = @Translation("VP Analytics"),
 *   label_collection = @Translation("VP Analyticss"),
 *   label_singular = @Translation("vp analytics"),
 *   label_plural = @Translation("vp analyticss"),
 *   label_count = @PluralTranslation(
 *     singular = "@count vp analyticss",
 *     plural = "@count vp analyticss",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\vp_analytics\VpAnalyticsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\vp_analytics\VpAnalyticsAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\vp_analytics\Form\VpAnalyticsForm",
 *       "edit" = "Drupal\vp_analytics\Form\VpAnalyticsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "vp_analytics",
 *   admin_permission = "administer vp analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/vp-analytics",
 *     "add-form" = "/vp-analytics/add",
 *     "canonical" = "/vp-analytics/{vp_analytics}",
 *     "edit-form" = "/vp-analytics/{vp_analytics}/edit",
 *     "delete-form" = "/vp-analytics/{vp_analytics}/delete",
 *   },
 *   field_ui_base_route = "entity.vp_analytics.settings",
 * )
 */
class VpAnalytics extends ContentEntityBase implements VpAnalyticsInterface {

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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions(
              'form', [
                'type' => 'boolean_checkbox',
                'settings' => [
                  'display_label' => FALSE,
                ],
                'weight' => 0,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'type' => 'boolean',
                'label' => 'above',
                'weight' => 0,
                'settings' => [
                  'format' => 'enabled-disabled',
                ],
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
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
                'weight' => 15,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'label' => 'above',
                'type' => 'author',
                'weight' => 15,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the vp analytics was created.'))
      ->setDisplayOptions(
              'view', [
                'label' => 'above',
                'type' => 'timestamp',
                'weight' => 20,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'form', [
                'type' => 'datetime_timestamp',
                'weight' => 20,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the vp analytics was last edited.'));

    $fields['field_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
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
                'weight' => 98,
              ]
          )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
              'view', [
                'label' => 'above',
                'type' => 'author',
                'weight' => 98,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_virtual_patient'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Virtual patient'))
      ->setSetting('target_type', 'virtual_patient')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setCardinality(1)
      ->setDisplayOptions(
        'form', [
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
              'view', [
                'label' => 'above',
                'weight' => 99,
              ]
          )
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
