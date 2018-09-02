<?php

namespace Drupal\ethereum;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an ethereum_address entity.
 */
interface EthereumAddressInterface extends ContentEntityInterface {

  /**
   * Gets the Ethereum address.
   *
   * @return string
   *   The Ethereum address.
   */
  public function getAddress();

  /**
   * Sets the address.
   *
   * @param string $address
   *   The address.
   *
   * @return $this
   */
  public function setAddress($address);

  /**
   * Gets the address' network ID.
   *
   * @return string
   *   The Ethereum network ID where this address exists.
   */
  public function getNetworkId();

  /**
   * Sets the address' Ethereum network ID.
   *
   * @param string $network_id
   *   The address' network ID.
   *
   * @return $this
   */
  public function setNetworkID($network_id);

  /**
   * Returns whether the address is a contract.
   *
   * @return bool
   *   TRUE if the address is a contract.
   */
  public function isContract();

  /**
   * Sets whether the address is a contract.
   *
   * @param bool $is_contract
   *   TRUE to mark this address as a contract, FALSE otherwise.
   *
   * @return $this
   */
  public function setContract($is_contract);

}
