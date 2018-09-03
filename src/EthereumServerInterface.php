<?php

namespace Drupal\ethereum;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Ethereum Server entity.
 */
interface EthereumServerInterface extends ConfigEntityInterface {

  /**
   * Returns the URL of the server.
   *
   * @return string
   */
  public function getUrl();

  /**
   * Returns the Ethereum network ID of the server.
   *
   * @return string
   */
  public function getNetworkId();

  /**
   * Whether this server is currently configured as the default server.
   *
   * @return bool
   */
  public function isDefaultServer();

  /**
   * Checks whether the server can connect to its designated network.
   *
   * @return array
   *   An array with the following structure:
   *   - 'error' boolean: TRUE if the server can connect to the network, FALSE
   *     otherwise.
   *   - 'message' string: The literal error message if 'error' is TRUE.
   */
  public function validateConnection();


  /**
   * Provide a links to Block explorer.
   *
   * @return array
   */
  public function getNetworkLinks();

  /**
   * @return array
   *   Network data to provide as js setting.
   */
  public function getJsConfig();

}
