<?php

namespace Drupal\vp;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the virtual patient entity type.
 */
class VirtualPatientAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    $uid = $entity->getOwnerId();

    if ($operation == 'view' && $account->hasPermission('view virtual patient')) {
      return AccessResult::allowed();
    }

    if ($operation == 'view' && $account->hasPermission('view own virtual patient') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed();
    }

    if ($operation == 'update' && $account->hasPermission('edit virtual patient')) {
      return AccessResult::allowed();
    }

    if ($operation == 'update' && $account->hasPermission('edit own virtual patient') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed();
    }

    if ($operation == 'delete' && $account->hasPermission('delete virtual patient')) {
      return AccessResult::allowed();
    }

    if ($operation == 'delete' && $account->hasPermission('delete own virtual patient') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions(
          $account,
          ['create virtual patient', 'administer virtual patient'],
          'OR',
      );
  }

}
