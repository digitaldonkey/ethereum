<?php

namespace Drupal\ethereum\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ethereum\EthereumServerInterface;
use Ethereum\Ethereum;

/**
 * Defines the EthereumServer entity.
 *
 * @ConfigEntityType(
 *   id = "ethereum_server",
 *   label = @Translation("Ethereum Server"),
 *   handlers = {
 *     "list_builder" = "Drupal\ethereum\Controller\EthereumServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ethereum\Form\EthereumServerForm",
 *       "edit" = "Drupal\ethereum\Form\EthereumServerForm",
 *       "delete" = "Drupal\ethereum\Form\EthereumServerDeleteForm",
 *     }
 *   },
 *   config_prefix = "ethereum_server",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "url",
 *     "network_id",
 *     "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/ethereum/network/server/{ethereum_server}/edit",
 *     "delete-form" = "/admin/config/ethereum/network/server/{ethereum_server}/delete",
 *   }
 * )
 */
class EthereumServer extends ConfigEntityBase implements EthereumServerInterface {

  /**
   * The machine name of this server.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the server.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this server.
   *
   * @var string
   */
  protected $description;

  /**
   * The server address (including the port).
   *
   * @var string
   */
  protected $url;

  /**
   * The Ethereum network ID of the server.
   *
   * @see \Drupal\ethereum\Controller\EthereumController::getNetworks()
   *
   * @var integer
   */
  protected $network_id;

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetworkId() {
    return $this->network_id;
  }

  /**
   * Get Block explorer links.
   *
   * @return array
   */
  public function getNetworkLinks() {
    $ret = [];
    $allNetworks =  \Drupal::config('ethereum.ethereum_networks')->get();
    $currentNetwork = array_filter($allNetworks, function($net) {
      return isset($net['id']) && $net['id'] === $this->network_id;
    });
    foreach (array_pop($currentNetwork) as $key => $val) {
      if (substr($key, 0, 7) === 'link_to') {
        $ret[substr($key, 8)] = $val;
      }
    }
    return $ret;
  }

  /**
   * @return array
   */
  public function getJsConfig() {
    return [
      'id' => $this->getNetworkId(),
      'name' => $this->label(),
      'blockExplorerLinks' => $this->getNetworkLinks()
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultServer() {
    return $this->id() === \Drupal::config('ethereum.settings')->get('current_server');
  }

  /**
   * {@inheritdoc}
   */
  public function validateConnection() {
    $return = [
      'error' => FALSE,
      'message' => 'Server ' . $this->id . ' (' . $this->url .') is up and on listening on Ethereum network id ' . $this->network_id
    ];
    try {
      /** @var \Ethereum\Ethereum $web3 */
       $web3 = \Drupal::service('ethereum.client_factory')->get($this->getUrl());

      // Try to connect.
      $networkVersion = $web3->net_version()->val();
      if (!is_string($networkVersion)) {
        throw new \Exception('eth_protocolVersion return is not valid.');
      }

      if ($this->getNetworkId() !== '*' && $networkVersion !== $this->getNetworkId()) {
        throw new \Exception('Network ID does not match.');
      }
    }
    catch (\Exception $exception) {
      $return = [
        'error' => TRUE,
        'message' => new TranslatableMarkup('Unable to connect to server %label. @error_message', [
          '%label' => $this->label(),
          '@error_message' => $exception->getMessage()
        ]),
      ];
    }
    return $return;
  }

}
