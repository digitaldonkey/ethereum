<?php

namespace Drupal\ethereum_smartcontract\Entity;

use Ethereum\Ethereum;
use Ethereum\SmartContract as Web3Contract;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ethereum_smartcontract\SmartContractInterface;
use Drupal\ethereum\Controller\EthereumController;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
* Defines the SmartContract entity.
*
* @ConfigEntityType(
*   id = "smartcontract",
*   label = @Translation("Smart Contract"),
*   handlers = {
*     "list_builder" = "Drupal\ethereum_smartcontract\Controller\SmartContractListBuilder",
*     "form" = {
*       "add" = "Drupal\ethereum_smartcontract\Form\SmartContractForm",
*       "edit" = "Drupal\ethereum_smartcontract\Form\SmartContractForm",
*       "delete" = "Drupal\ethereum_smartcontract\Form\SmartContractDeleteForm",
*     }
*   },
*   config_prefix = "contract",
*   admin_permission = "administer site configuration",
*   entity_keys = {
*     "id" = "id",
*     "label" = "label",
*     "contract_src" = "contract_src",
*     "contract_compiled_json" = "contract_compiled_json",
*     "networks" = "networks",
*   },
*   links = {
*     "edit-form" = "/admin/config/ethereum/smartcontracts/{smartcontract}",
*     "delete-form" = "/admin/config/ethereum/smartcontract/{smartcontract}/delete",
*   }
* )
*/
class SmartContract extends ConfigEntityBase implements SmartContractInterface {

  /**
  * Contract machine name.
  *
  * @var $id string
  */
  protected $id;

  /**
  * Contract human readable name.
  *
  * @var $label string
  */
  protected $label;

  /**
   * Contract Solidity source code.
   *
   * @var $contract_src string
   */
  protected $contract_src;

 /**
  * Compiled contract JSON as provided by solidity compiler (solc).
  *
  * @var $contract_compiled_json string
  */
  protected $contract_compiled_json;


  /**
   * Networks
   *  array( ID => Contract address )
   *
   * @var array
   */
   protected $networks;

  /**
   * @return string
   */
   public function getSrc() {
     return $this->contract_src;
   }

  /**
   * @return array
   */
  public function getNetworks() {
    return $this->networks;
  }

  /**
   * @throws \Exception
   *
   * @return string
   */
  public function getCurrentNetworkAddress() {
    $defaultServer = EthereumController::getDefaultServer();
    if (!isset($this->networks[$defaultServer->getNetworkId()])) {
      throw new \Exception('Contract "' . $this->label()  . '" is not deployed at active default network (' . $defaultServer->id() .')');
    }
    return $this->networks[$defaultServer->getNetworkId()];
  }

  /**
   * @return array
   *   Smart contract ABI.
   */
  public function getAbi() {
    return json_decode(json_decode($this->contract_compiled_json)->interface);
  }

  /**
   * Get a callable SmartContract.
   *
   * @param \Ethereum\Ethereum|NULL $web3
   *
   * @throws \Exception
   *
   * @return \Ethereum\SmartContract
   */
  public function getCallable(Ethereum $web3 = null) {
    if (!$web3) {
      $web3 = \Drupal::service('ethereum.client');
    }
    return new Web3Contract(
      $this->getAbi(),
      $this->getCurrentNetworkAddress(),
      $web3
    );
  }

  /**
   * @param $fktSignature
   *
   * @return mixed
   * @throws \Exception
   */
  public function getFunctionHash($fktSignature) {
    $hashes = (array) json_decode($this->contract_compiled_json)->functionHashes;
    if (!isset($hashes[$fktSignature])) {
      throw new \Exception('can not find function signature (' . $fktSignature . ') in contract_compiled.');
    }
    return $hashes[$fktSignature];
  }


  /**
   * Get all deployed contracts
   *
   * @return array
   *   Deployed contract address and network.
   */
  public function getDeployed() {
    $all = EthereumController::getNetworks();
    $deployed = [];
    foreach ($this->getNetworks() as $netId => $address) {
      $deployed [$netId] = $all[$netId];
      $deployed [$netId]['contract_address'] = $address;
    }
    return $deployed;
  }

  /**
   * Contract Network Info Table.
   *
   * @return array
   *    Table render array.
   */
  public function getDeployedAsTable() {

    $deployed = $this->getDeployed();

    $formElement = array(
      '#type' => 'table',
      '#header' => [
        'Network ID',
        'Network',
        'Contract address',
        'Block Explorer',
      ],
    );
    foreach ($deployed as $id => $net) {
      $formElement[$id]['id'] = array(
        '#markup' => '<b>' . $net['id'] . '</b>',
      );
      $formElement[$id]['net'] = array(
        '#markup' => $net['label'] . '<br/>'.
          '<small>' . $net['description'] . '</small>',
      );
      $formElement[$id]['contract'] = array(
        '#markup' => $net['contract_address'],
      );
      // Provide link to contract.
      if (isset($net['link_to_address'])) {
        $addr = str_replace('@address', $net['contract_address'] ,$net['link_to_address']);
        $url = Url::fromUri($addr);
        $linkText = substr($addr, 0, 47) . '...';
        $link = Link::fromTextAndUrl($linkText, $url);
        $formElement[$id]['explorer'] = array(
          '#markup' => $link->toString(),
        );
      }
    }
    return $formElement;
  }
}

