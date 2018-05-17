<?php

namespace Drupal\ethereum;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ethereum\Controller\EthereumController;
use Drupal\ethereum\Entity\EthereumServer;
use Ethereum\Ethereum;

/**
 * Helper class to construct a Ethereum JsonRPC client.
 */
class EthereumClientFactory {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new EthereumClientFactory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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
      $host = EthereumController::getDefaultServer()->getUrl();
    }
    return new Ethereum($host);
  }

}
