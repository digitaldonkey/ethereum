<?php

namespace Drupal\ethereum\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents a configurable ethereum_address field.
 */
class EthereumAddressFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureComputedValue() {
    /** @var \Drupal\ethereum\EthereumManagerInterface $ethereum_manager */
    $ethereum_manager = \Drupal::service('ethereum.manager');

    // Filter the items of the ethereum_address field to those that belong to
    // the contextual Ethereum network ID.
    if ($contextual_network_id = $ethereum_manager->getContextualNetworkId()) {
      $this->filter(function ($item) use ($contextual_network_id) {
        return $item->network === $contextual_network_id;
      });
    }
  }

}
