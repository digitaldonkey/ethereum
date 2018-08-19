<?php

namespace Drupal\ethereum;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the ethereum_address entity type.
 */
class EthereumAddressAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL, $return_as_object = FALSE) {
    $access = parent::fieldAccess($operation, $field_definition, $account, $items, $return_as_object);

    // Allow the ID field (address) to be displayed and edited in forms.
    if ($operation === 'edit' && $field_definition->getName() === $this->entityType->getKey('id')) {
      return $return_as_object ? AccessResult::allowed() : TRUE;
    }

    return $access;
  }

}
