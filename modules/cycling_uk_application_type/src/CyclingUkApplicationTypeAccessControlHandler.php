<?php

namespace Drupal\cycling_uk_application_type;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the cycling uk application type entity type.
 */
class CyclingUkApplicationTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view cycling uk application type');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit cycling uk application type', 'administer cycling uk application type'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete cycling uk application type', 'administer cycling uk application type'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create cycling uk application type', 'administer cycling uk application type'], 'OR');
  }

}
