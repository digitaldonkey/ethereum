<?php

namespace Drupal\ethereum\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Supports validating Ethereum addresses on a network.
 *
 * @Constraint(
 *   id = "EthereumAddressNetwork",
 *   label = @Translation("Ethereum address network", context = "Validation"),
 *   type = "entity:ethereum_address"
 * )
 */
class EthereumAddressNetworkConstraint extends CompositeConstraintBase {

  /**
   * Message shown when the address already exists on the given network.
   *
   * @var string
   */
  public $message = 'This address already exists on the %network_label network.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['address', 'network'];
  }

}
