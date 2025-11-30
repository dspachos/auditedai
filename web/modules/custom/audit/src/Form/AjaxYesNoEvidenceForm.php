<?php

declare(strict_types=1);

namespace Drupal\audit\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an AJAX-enabled form for yes/no question evidence.
 */
class AjaxYesNoEvidenceForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AjaxYesNoEvidenceForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_yes_no_evidence_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $audit_entity = NULL, $audit_question_entity = NULL) {
    $form['#attributes']['class'][] = 'ajax-yes-no-form';
    
    // Retrieve the current paragraph response if it exists
    $current_paragraph = $this->getCurrentParagraphResponse($audit_entity, $audit_question_entity);

    $form['audit_entity_id'] = [
      '#type' => 'hidden',
      '#value' => $audit_entity ? $audit_entity->id() : NULL,
    ];

    $form['audit_question_entity_id'] = [
      '#type' => 'hidden',
      '#value' => $audit_question_entity ? $audit_question_entity->id() : NULL,
    ];

    $form['yes_no_answer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes'),
      '#default_value' => $current_paragraph ? (bool) $current_paragraph->field_yes_no->value : FALSE,
      '#ajax' => [
        'callback' => '::ajaxYesNoCallback',
        'wrapper' => 'yes-no-wrapper-' . ($audit_question_entity ? $audit_question_entity->id() : 'default'),
        'effect' => 'fade',
      ],
      '#attributes' => [
        'class' => ['yes-no-checkbox'],
        'data-question-id' => $audit_question_entity ? $audit_question_entity->id() : NULL,
      ],
    ];

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'yes-no-wrapper-' . ($audit_question_entity ? $audit_question_entity->id() : 'default'),
        'class' => ['yes-no-wrapper'],
      ],
      'content' => &$form,
    ];

    return $form;
  }

  /**
   * AJAX callback for handling yes/no checkbox changes.
   */
  public function ajaxYesNoCallback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    // Get the checkbox value
    $new_value = $form_state->getValue('yes_no_answer');
    
    // Get the entity IDs
    $audit_entity_id = $form_state->getValue('audit_entity_id');
    $audit_question_entity_id = $form_state->getValue('audit_question_entity_id');
    
    if ($audit_entity_id && $audit_question_entity_id) {
      // Get the entities
      $audit_entity = $this->entityTypeManager->getStorage('node')->load($audit_entity_id);
      $audit_question_entity = $this->entityTypeManager->getStorage('audit_question')->load($audit_question_entity_id);
      
      if ($audit_entity && $audit_question_entity) {
        // Update or create the paragraph response
        $paragraph_entity = $this->createOrUpdateParagraphResponse($audit_entity, $audit_question_entity, $new_value);

        if ($paragraph_entity) {
          // Add CSS class to indicate success
          $response->addCommand(new InvokeCommand('.yes-no-checkbox[data-question-id="' . $audit_question_entity_id . '"]', 'addClass', ['ajax-success']));

          // Remove the success class after a delay to reset visual indicator
          $js_code = '
            setTimeout(function() {
              $(".yes-no-checkbox[data-question-id=' . $audit_question_entity_id . ']").removeClass("ajax-success");
            }, 1000);
          ';
          $response->addCommand(new InvokeCommand(NULL, 'script', [$js_code]));
        }
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form is AJAX-only, so submitForm is not called in the normal flow
  }

  /**
   * Gets the current paragraph response for the given audit and question.
   */
  protected function getCurrentParagraphResponse($audit_entity, $audit_question_entity) {
    if (!$audit_entity || !$audit_question_entity) {
      return NULL;
    }

    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $query = $paragraph_storage->getQuery()
      ->condition('type', 'audit_question_response')
      ->condition('field_audit', $audit_entity->id())
      ->condition('field_audit_question', $audit_question_entity->id())
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(TRUE);

    $paragraph_ids = $query->execute();

    if (!empty($paragraph_ids)) {
      $paragraph_id = reset($paragraph_ids);
      return $paragraph_storage->load($paragraph_id);
    }

    return NULL;
  }

  /**
   * Creates or updates a paragraph entity with the yes/no answer.
   */
  protected function createOrUpdateParagraphResponse($audit_entity, $audit_question_entity, $answer_value) {
    $current_paragraph = $this->getCurrentParagraphResponse($audit_entity, $audit_question_entity);

    if ($current_paragraph) {
      // Update existing paragraph
      $current_paragraph->field_yes_no->value = $answer_value ? 1 : 0;
      $current_paragraph->save();
      return $current_paragraph;
    } else {
      // Create new paragraph response
      $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
      $paragraph = $paragraph_storage->create([
        'type' => 'audit_question_response',
        'field_audit' => $audit_entity->id(),
        'field_audit_question' => $audit_question_entity->id(),
        'field_yes_no' => $answer_value ? 1 : 0,
        'field_response' => '', // Initialize with empty response field
        'status' => TRUE,
      ]);

      $paragraph->save();
      return $paragraph;
    }
  }

}