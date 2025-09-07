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
 *   permission_granularity = "entity_type",
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
      ->setLabel(t('VET 21001 Cluster'))
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

    $fields['field_iso_21001'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('ISO 21001'))
      ->setDescription(t('A boolean field to indicate if the question is related to ISO 21001.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_eqavet'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('EQAVET'))
      ->setDescription(t('A boolean field to indicate if the question is related to EQAVET.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_iso_21001_criteria'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('ISO 21001 Criteria'))
      ->setDescription(t('References terms in the "ISO Criteria" vocabulary.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['iso_criteria' => 'iso_criteria']])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -6,
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
        'weight' => -6,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_eqavet_criteria'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('EQAVET Criteria'))
      ->setDescription(t('References terms in the "EQAVET Criteria" vocabulary.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['eqavet_criteria' => 'eqavet_criteria']])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
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
        'weight' => -5,
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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_eqavet_doc_info'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('EQAVET Documented Information'))
      ->setDescription(t('Indicates if documented information is needed for EQAVET.'))
      ->setSetting('allowed_values', [
        'yes' => 'Yes',
        'no' => 'No'
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
