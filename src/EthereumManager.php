<?php

namespace Drupal\ethereum;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines the Ethereum manager service.
 */
class EthereumManager implements EthereumManagerInterface {

  /**
   * The Ethereum server storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $serverStorage;

  /**
   * The Ethereum settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $ethereumSettings;

  /**
   * The Ethereum networks config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $ethereumNetworks;

  /**
   * The current contextual network ID.
   *
   * This can only be set by ::getContextualNetworkId()
   *
   * @var string
   */
  protected $contextualNetworkId;

  /**
   * EthereumManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->serverStorage = $entity_type_manager->getStorage('ethereum_server');
    $this->ethereumSettings = $config_factory->get('ethereum.settings');
    $this->ethereumNetworks = $config_factory->get('ethereum.ethereum_networks');
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentServer() {
    /** @var \Drupal\ethereum\EthereumServerInterface $server */
    $current_server_id = $this->ethereumSettings->get('current_server');
    $server = $this->serverStorage->load($current_server_id);

    if (!$server) {
      throw new \RuntimeException('Current default server (' . $current_server_id . ') does not exist.');
    }
    if (!$server->status()) {
      throw new \RuntimeException('Current default server is not enabled.');
    }

    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function getFrontendServer() {
    /** @var \Drupal\ethereum\EthereumServerInterface $server */
    $frontend_server_id = $this->ethereumSettings->get('frontend_server') ?: $this->ethereumSettings->get('current_server');
    $server = $this->serverStorage->load($frontend_server_id);

    if (!$server) {
      throw new \RuntimeException('Current frontend server (' . $frontend_server_id . ') does not exist.');
    }
    if (!$server->status()) {
      throw new \RuntimeException('Current frontend server is not enabled.');
    }

    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function getServersAsOptions($filter_enabled = FALSE, $filter_network_id = FALSE) {
    $properties = [];
    if ($filter_enabled) {
      $properties['status'] = TRUE;
    }
    if ($filter_network_id) {
      $properties['network_id'] = $filter_network_id;
    }
    $servers = $this->serverStorage->loadByProperties($properties);

    return array_map(function (EthereumServerInterface $server) { return Html::decodeEntities(strip_tags($server->label())); }, $servers);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentNetworkId() {
    return $this->getCurrentServer()->getNetworkId();
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableNetworkIds() {
    /** @var \Drupal\ethereum\EthereumServerInterface[] $enabled_servers */
    $enabled_servers = $this->serverStorage->loadByProperties(['status' => TRUE]);

    $network_ids = [];
    foreach ($enabled_servers as $server) {
      $network_ids[] = $server->getNetworkId();
    }

    sort($network_ids);
    return array_unique($network_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextualNetworkId() {
    return $this->contextualNetworkId;
  }

  /**
   * {@inheritdoc}
   */
  public function executeInNetworkContext($network_id, callable $function) {
    $networks = $this->getAllNetworks();

    if (!isset($networks[$network_id])) {
      throw new \InvalidArgumentException('The ' . $network_id . ' network ID does not exist.');
    }

    $this->contextualNetworkId = $network_id;
    $result = $function();
    $this->contextualNetworkId = NULL;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllNetworks() {
    $configured_networks = $this->ethereumNetworks->getRawData();
    // Filter out non-numeric keys like '_core'.
    $configured_networks = array_filter($configured_networks, function ($key) { return is_numeric($key); },ARRAY_FILTER_USE_KEY);

    $networks = [];
    foreach ($configured_networks as $network) {
      $networks[$network['id']] = $network;
    }
    return $networks;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetworksAsOptions($include_id = TRUE, $include_description = FALSE) {
    $networks = [];
    foreach ($this->getAllNetworks() as $k => $item) {
      $label = $item['label'];
      $label .= $include_id ? ' (' . $item['id'] . ')' : '';
      $label .= $include_description ? ' - ' . $item['description'] : '';

      $networks[$item['id']] = Html::decodeEntities(strip_tags($label));
    }
    return $networks;
  }

}
