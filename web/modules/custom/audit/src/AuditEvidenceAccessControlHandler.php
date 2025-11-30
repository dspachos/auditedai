<?php

declare(strict_types=1);

namespace Drupal\audit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the audit evidence entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class AuditEvidenceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $has_any_permission = match($operation) {
      'view' => $account->hasPermission('view audit_evidence'),
      'update' => $account->hasPermission('edit audit_evidence'),
      'delete' => $account->hasPermission('delete audit_evidence'),
      default => FALSE,
    };

    if ($has_any_permission) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check for "own" permissions
    $has_own_permission = match($operation) {
      'view' => $account->hasPermission('view own audit_evidence'),
      'update' => $account->hasPermission('edit own audit_evidence'),
      'delete' => $account->hasPermission('delete own audit_evidence'),
      default => FALSE,
    };

    if ($has_own_permission) {
      // Check if the entity belongs to the current user
      $entity_owner = $entity->getOwnerId();
      $current_user_id = $account->id();
      return AccessResult::allowedIf($entity_owner == $current_user_id)
        ->cachePerPermissions()
        ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create audit_evidence', 'administer audit_evidence'], 'OR');
  }

}
