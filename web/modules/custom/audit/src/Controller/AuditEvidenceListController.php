<?php

namespace Drupal\audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the audit evidence list page.
 */
class AuditEvidenceListController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AuditEvidenceListController.
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
   * Displays the list of audit evidences for a specific audit.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The audit node.
   *
   * @return array
   *   A render array containing the evidence list.
   */
  public function view(NodeInterface $node) {
    // Ensure this is an audit node.
    if ($node->getType() !== 'audit') {
      // Return an access denied page if not an audit node.
      return $this->redirect('system.404');
    }

    // Get all evidence entities related to this audit.
    $evidence_storage = $this->entityTypeManager->getStorage('audit_evidence');

    // Query for evidence related to this audit node.
    $query = $evidence_storage->getQuery()
      ->condition('field_audit', $node->id())
      ->accessCheck(TRUE)
      ->sort('created', 'DESC'); // Sort by creation date, newest first

    $evidence_ids = $query->execute();
    $evidence_entities = $evidence_storage->loadMultiple($evidence_ids);

    // Create a table to display the evidence.
    $header = [
      $this->t('Label'),
      $this->t('Description'),
      $this->t('Audit Question'),
      $this->t('Created'),
      $this->t('Actions'),
    ];

    $rows = [];

    foreach ($evidence_entities as $evidence) {

      // Get label (title)
      $label = $evidence->label();

      // Get description if available
      $description = '';
      if ($evidence->hasField('field_evidence') && !$evidence->get('field_evidence')->isEmpty()) {
        $description = $evidence->get('field_evidence')->value;
        // Limit description length for display
        $description = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
      }

      // Get audit question if linked - for multi-reference, get the first one
      $question_label = $this->t('Not attached');
      $question_entity = NULL;
      if ($evidence->hasField('field_audit_question') && !$evidence->get('field_audit_question')->isEmpty()) {
        // Get the first referenced entity
        $question_references = $evidence->get('field_audit_question')->referencedEntities();
        if (!empty($question_references)) {
          $question_entity = reset($question_references);
          $question_label = $question_entity->label();
        } else {
          // If no referenced entities but field has values, get the target ID and load it
          $question_ref_data = $evidence->get('field_audit_question')->getValue();
          if (!empty($question_ref_data)) {
            $first_ref = reset($question_ref_data);
            $question_entity = $this->entityTypeManager->getStorage('audit_question')->load($first_ref['target_id']);
            if ($question_entity) {
              $question_label = $question_entity->label();
            }
          }
        }
      }

      // Format creation date
      $created_date = \Drupal::service('date.formatter')->format(
        $evidence->get('created')->value,
        'custom',
        'Y-m-d H:i'
      );

      // Create operations dropdown for actions
      $actions = [
        '#type' => 'dropbutton',
        '#attributes' => [
          'class' => ['audit-operations', 'dropbutton', 'dropbutton--extrasmall', 'dropbutton--multiple', 'dropbutton--gin'],
        ],
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('audit.evidence.edit_form', [
              'audit' => $node->id(),  // audit parameter (the parent audit node)
              'audit_evidence' => $evidence->id()  // audit_evidence parameter
            ])->setOption('query', ['destination' => '/node/' . $node->id() . '/evidences'])
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('entity.audit_evidence.delete_form', [
              'audit_evidence' => $evidence->id()
            ])->setOption('query', ['destination' => '/node/' . $node->id() . '/evidences'])
          ]
        ]
      ];

      $rows[] = [
        'data' => [
          [
            'data' => $label,
          ],
          [
            'data' => $description,
          ],
          [
            'data' => $question_label,
          ],
          [
            'data' => $created_date,
          ],
          [
            'data' => $actions,
            'class' => ['operations'],
          ],
        ],
      ];
    }

    $build = [];

    // Add a button to add new evidence
    $build['add_evidence'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Evidence'),
      '#url' => Url::fromRoute('audit.add_audit_evidence', [
        'node' => $node->id(),
      ]),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
      '#weight' => -10,
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No evidence found for this audit.'),
    ];

    // Add a back link to the audit.
    $build['back_link'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to Audit'),
      '#url' => $node->toUrl(),
      '#attributes' => ['class' => ['button', 'button--secondary']],
      '#weight' => 10,
    ];

    return $build;
  }
}
