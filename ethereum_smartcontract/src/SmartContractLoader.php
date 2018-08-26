<?php
/**
 * @file
 * Contains SmartContractLoader.
 */

namespace Drupal\ethereum_smartcontract;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * SmartContract plugin manager.
 *
 * Allows to define discoverable SmartContract
 * YOUR_MODULE/src/Plugin/SmartContract/ContractName.php
 *
 * For an example
 *
 * @see ethereum_user_connector/src/Plugin/SmartContract/RegisterDrupal.php
 *
 */
class SmartContractLoader extends DefaultPluginManager {

  /**
   * Constructs an SmartContractLoader object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SmartContract', $namespaces, $module_handler, NULL, 'Drupal\ethereum_smartcontract\Annotation\SmartContract');
    $this->setCacheBackend($cache_backend, 'ethereum_smartcontract');
  }

  /**
   * @param $plugin_id
   * @param array $configuration
   *
   * @return \Ethereum\SmartContract
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (!is_array($this->definitions[$plugin_id])) {
      throw new PluginNotFoundException($plugin_id);
    }
    $class = $this->definitions[$plugin_id]['class'];
    return new $class(...$configuration);
  }
}
