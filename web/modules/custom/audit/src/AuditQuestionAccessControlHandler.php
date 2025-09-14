<?php

declare(strict_types=1);

namespace Drupal\audit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the audit question entity.
 */
class AuditQuestionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $is_owner = $entity->getOwnerId() === $account->id();

    // The 'administer audit_question' permission is checked by the parent.
    $access = parent::checkAccess($entity, $operation, $account);
    if ($access->isAllowed()) {
      return $access;
    }

    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view audit_question entities');
    }

    if ($operation === 'update') {
      if ($account->hasPermission('edit any audit_question entities')) {
        return AccessResult::allowed();
      }
      return AccessResult::allowedIf($is_owner && $account->hasPermission('edit own audit_question entities'));
    }

    if ($operation === 'delete') {
      if ($account->hasPermission('delete any audit_question entities')) {
        return AccessResult::allowed();
      }
      return AccessResult::allowedIf($is_owner && $account->hasPermission('delete own audit_question entities'));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create audit_question entities');
  }

}
