<?php

declare(strict_types=1);

namespace Drupal\audit\Entity;

use Drupal\audit\AuditQuestionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the audit question entity class.
 *
 * @ContentEntityType(
 *   id = "audit_question",
 *   label = @Translation("Audit Question"),
 *   label_collection = @Translation("Audit Questions"),
 *   label_singular = @Translation("audit question"),
 *   label_plural = @Translation("audit questions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count audit questions",
 *     plural = "@count audit questions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\audit\AuditQuestionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\audit\AuditQuestionAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\audit\Form\AuditQuestionForm",
 *       "edit" = "Drupal\audit\Form\AuditQuestionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "audit_question",
 *   admin_permission = "administer audit_question",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/question",
 *     "add-form" = "/question/add",
 *     "canonical" = "/question/{audit_question}",
 *     "edit-form" = "/question/{audit_question}/edit",
 *     "delete-form" = "/question/{audit_question}/delete",
 *     "delete-multiple-form" = "/admin/content/question/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.audit_question.settings",
 * )
 */
final class AuditQuestion extends ContentEntityBase implements AuditQuestionInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The question text from the "Question" column (e.g., "Has the organization determined its purpose through a mission and a vision?").'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_cluster'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cluster'))
      ->setDescription(t('References a term in the "VET 21001 Clusters" vocabulary (e.g., "1. Leadership & Strategy").'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_iso_section'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ISO 21001 Section'))
      ->setDescription(t('Stores the ISO 21001 section reference (e.g., "4.1").'))
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_iso_requirements'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ISO Requirements Covered'))
      ->setDescription(t('Stores detailed ISO requirements (e.g., "4.1, 5.2 a), e)").'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_iso_doc_info'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('ISO Documented Information'))
      ->setDescription(t('Indicates if documented information is needed for ISO (options: "yes", "no").'))
      ->setSetting('allowed_values', [
        'yes' => 'Yes',
        'no' => 'No'
      ])
      ->setDefaultValue('empty')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_eqavet_criteria'] = BaseFieldDefinition::create('string')
      ->setLabel(t('EQAVET Criteria'))
      ->setDescription(t('Stores EQAVET criteria reference (e.g., "1").'))
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_eqavet_indicators'] = BaseFieldDefinition::create('string')
      ->setLabel(t('EQAVET Criteria and Indicators'))
      ->setDescription(t('Stores detailed EQAVET criteria/indicators (e.g., "1.3, 1.7").'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_eqavet_doc_info'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('EQAVET Documented Information'))
      ->setDescription(t('Indicates if documented information is needed for EQAVET (options: "yes", "no", "empty").'))
      ->setSetting('allowed_values', [
        'yes' => 'Yes',
        'no' => 'No',
        'empty' => 'Empty',
      ])
      ->setDefaultValue('empty')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_help'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Help'))
      ->setDescription(t('Help text to be displayed to the user on hover.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -2,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the audit question was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the audit question was last edited.'));

    return $fields;
  }
}
