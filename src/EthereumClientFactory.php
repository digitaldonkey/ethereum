<?php

namespace Drupal\ethereum;

use Ethereum\Ethereum;

/**
 * Helper class to construct a Ethereum JsonRPC client.
 */
class EthereumClientFactory {

  /**
   * The Ethereum manager service.
   *
   * @var \Drupal\ethereum\EthereumManagerInterface
   */
  protected $ethereumManager;

  /**
   * Constructs a EthereumClientFactory object.
   *
   * @param \Drupal\ethereum\EthereumManagerInterface $ethereum_manager
   *   The Ethereum manager service.
   */
  public function __construct(EthereumManagerInterface $ethereum_manager) {
    $this->ethereumManager = $ethereum_manager;
  }

  /**
   * Constructs a new Ethereum client object from a host URL.
   *
   * @param string $host
   *   (optional) The host URL for the client.
   *
   * @return \Ethereum\Ethereum
   *   The Ethereum client.
   *
   * @return \Ethereum\Ethereum
   *
   * @throws \Exception
   */
  public function get($host = NULL) {
    if (!$host) {
      $host = $this->ethereumManager->getCurrentServer()->getUrl();
    }
    return new Ethereum($host);
  }

}
