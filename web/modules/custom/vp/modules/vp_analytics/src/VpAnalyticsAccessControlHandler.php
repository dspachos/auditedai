<?php

namespace Drupal\vp_analytics;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the vp analytics entity type.
 */
class VpAnalyticsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view vp analytics');

      case 'update':
        return AccessResult::allowedIfHasPermissions(
            $account,
            ['edit vp analytics', 'administer vp analytics'],
            'OR',
        );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
            $account,
            ['delete vp analytics', 'administer vp analytics'],
            'OR',
        );

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions(
          $account,
          ['create vp analytics', 'administer vp analytics'],
          'OR',
      );
  }

}
