<?php

declare(strict_types=1);

namespace Drupal\audit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Defines the access control handler for the audit question response paragraph type.
 */
final class AuditQuestionResponseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Only handle access for audit_question_response paragraphs
    if ($entity->getEntityTypeId() !== 'paragraph' || $entity->bundle() !== 'audit_question_response') {
      return AccessResult::neutral();
    }

    if ($account->hasPermission('administer paragraphs')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check for general permissions first
    $has_any_permission = match($operation) {
      'view' => $account->hasPermission('view paragraph'),
      'update' => $account->hasPermission('edit paragraph'),
      'delete' => $account->hasPermission('delete paragraph'),
      default => FALSE,
    };

    if ($has_any_permission) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check specific permissions for audit question response comments
    $has_specific_permission = match($operation) {
      'create' => $account->hasPermission('create audit_question_response_comment'),
      'view' => $account->hasPermission('view paragraph'),
      'update' => $account->hasPermission('edit audit_question_response_comment'),
      'delete' => $account->hasPermission('delete audit_question_response_comment'),
      default => FALSE,
    };

    if ($has_specific_permission) {
      // For update and delete, check if it's the user's own comment
      if ($operation === 'update' || $operation === 'delete') {
        // Check if the paragraph belongs to the current user via its parent relationship or other field
        // Since paragraphs don't have a direct owner, we need to check permissions differently
        $entity_owner = $entity->getOwnerId();
        $current_user_id = $account->id();
        
        // If the user owns the paragraph
        if ($entity_owner == $current_user_id) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        
        // Check if the user has permission to edit their own comments
        $has_own_permission = match($operation) {
          'update' => $account->hasPermission('edit own audit_question_response_comment'),
          'delete' => $account->hasPermission('delete own audit_question_response_comment'),
          default => FALSE,
        };
        
        if ($has_own_permission) {
          return AccessResult::allowedIf($entity_owner == $current_user_id)
            ->cachePerPermissions()
            ->cachePerUser();
        }
      } else {
        // For operations other than update/delete, allow if has specific permission
        return AccessResult::allowed()->cachePerPermissions();
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    if ($entity_bundle === 'audit_question_response') {
      return AccessResult::allowedIfHasPermissions($account, ['create audit_question_response_comment', 'administer paragraphs'], 'OR');
    }
    return AccessResult::neutral();
  }

}