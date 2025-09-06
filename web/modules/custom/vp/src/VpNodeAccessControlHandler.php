<?php

namespace Drupal\vp;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the vp node entity type.
 */
class VpNodeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    $uid = $entity->getOwnerId();

    if ($operation == 'view' && $account->hasPermission('view vp node')) {
      return AccessResult::allowed();
    }

    if ($operation == 'view' && $account->hasPermission('view own vp node') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed();
    }

    if ($operation == 'update' && $account->hasPermission('edit vp node')) {
      return AccessResult::allowed();
    }

    if ($operation == 'update' && $account->hasPermission('edit own vp node') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed();
    }

    if ($operation == 'delete' && $account->hasPermission('delete vp node')) {
      return AccessResult::allowed();
    }

    if ($operation == 'delete' && $account->hasPermission('delete own vp node') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions(
          $account,
          ['create vp node', 'administer vp node'],
          'OR',
      );
  }

}
