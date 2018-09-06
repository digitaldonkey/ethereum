<?php

namespace Drupal\ethereum;

/**
 * Provides an interface for the Ethereum Manager service.
 */
interface EthereumManagerInterface {

  /**
   * Returns the current backend server.
   *
   * @return \Drupal\ethereum\EthereumServerInterface
   *   An ethereum_server object.
   */
  public function getCurrentServer();

  /**
   * Returns the requested backend server.
   *
   * @param string $server_id
   *  The id of the server.
   *
   * @return \Drupal\ethereum\EthereumServerInterface
   *   An ethereum_server object.
   */
  public function getServer($server_id);

  /**
   * Returns the frontend server.
   *
   * @return \Drupal\ethereum\EthereumServerInterface
   *   An ethereum_server object.
   */
  public function getFrontendServer();

  /**
   * Returns an array ethereum_server entities suitable for an options element.
   *
   * @param bool $filter_enabled
   *   (optional) Restrict to enabled servers. Defaults to FALSE.
   * @param string|null $filter_network_id
   *   (optional) Restrict to servers on a specific network. Defaults to NULL.
   *
   * @return array
   *   An array of ethereum_server labels, keyed by their IDs.
   */
  public function getServersAsOptions($filter_enabled = FALSE, $filter_network_id = NULL);

  /**
   * Returns the network ID of the current server.
   *
   * @return string
   */
  public function getCurrentNetworkId();

  /**
   * Returns the network IDs for the currently enabled servers.
   *
   * @return string[]
   */
  public function getAvailableNetworkIds();

  /**
   * Returns the contextually-active network ID, if it exists.
   *
   * @return string|null
   *   An Ethereum network ID or NULL;
   */
  public function getContextualNetworkId();

  /**
   * Executes the given callback function in the context of a network ID.
   *
   * @param string $network_id
   *   The network ID.
   * @param callable $function
   *   The callback to be executed.
   *
   * @return mixed
   *   The callable's return value.
   */
  public function executeInNetworkContext($network_id, callable $function);

  /**
   * Returns the currently configured Ethereum networks.
   *
   * @return array
   *   Associative array of Ethereum networks, keyed by their network ID. Each
   *   network array contains the following keys:
   *   - id: The network ID
   *   - label: The network label
   *   - description: A short description of the network
   *   - link_to_address: URI where the details of an Ethereum address can be
   *     displayed.
   */
  public function getAllNetworks();

  /**
   * Returns the Ethereum networks as an array suitable for an options element.
   *
   * @param bool $include_id
   *   (optional) Whether to include the network ID in the label. Defaults to
   *   TRUE.
   * @param bool $include_description
   *   (optional) Whether to include the network description in the label.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of Ethereum Networks containing the network name, ID, and
   *   description, keyed by the network ID.
   */
  public function getNetworksAsOptions($include_id = TRUE, $include_description = FALSE);

}
