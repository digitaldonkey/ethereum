<?php

namespace Drupal\ethereum_smartcontract\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ethereum_smartcontract\SmartContractInterface;
use Ethereum\Ethereum;
use Ethereum\SmartContract as Web3Contract;


/**
 * Defines the SmartContract entity.
 *
 * @ConfigEntityType(
 *   id = "smartcontract",
 *   label = @Translation("Ethereum Smart Contract"),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\ethereum_smartcontract\Controller\SmartContractListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ethereum_smartcontract\Form\SmartContractForm",
 *       "import_form_one" =
 *   "Drupal\ethereum_smartcontract\Form\SmartContractImportOne",
 *       "import_form_two" =
 *   "Drupal\ethereum_smartcontract\Form\SmartContractImportTwo",
 *       "import_form_three" =
 *   "Drupal\ethereum_smartcontract\Form\SmartContractImportThree",
 *       "import_form_update" =
 *   "Drupal\ethereum_smartcontract\Form\SmartContractImportUpdate",
 *       "edit" = "Drupal\ethereum_smartcontract\Form\SmartContractForm",
 *       "delete" =
 *   "Drupal\ethereum_smartcontract\Form\SmartContractDeleteForm",
 *     }
 *   },
 *   config_prefix = "contract",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "contract_src" = "contract_src",
 *     "abi" = "abi",
 *     "networks" = "networks",
 *     "status" = "status",
 *     "is_imported" = "is_imported",
 *     "imported_file" = "imported_file"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/ethereum/smartcontract/{smartcontract}",
 *     "import-form" = "/admin/config/ethereum/smartcontract/import",
 *     "delete-form" =
 *   "/admin/config/ethereum/smartcontract/{smartcontract}/delete",
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
   * Contract ABI.
   *
   * @see https://github.com/ethereum/wiki/wiki/Ethereum-Contract-ABI.
   *
   * @var string Json Encoded contract ABI.
   */
  protected $abi;


  /**
   * Imported means
   * - The contract and network data from Truffle json file is used.
   * - No in browser compilation
   * - You can not edit the imported fields
   * - You may update form file system.
   *
   * @var $isImported bool
   */
  protected $is_imported;

  /**
   * Path to truffle.json file if imported.
   *
   * @var $imported_file string
   */
  protected $imported_file;


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
   * @return bool
   */
  public function isImported() {
    return $this->is_imported;
  }


  /**
   * @return bool
   */
  public function getImportedFile() {
    return ($this->imported_file) ? $this->imported_file : '';
  }


  /**
   * @throws \Exception
   *
   * @return string
   */
  public function getCurrentNetworkAddress() {
    $defaultServer = \Drupal::service('ethereum.manager')->getCurrentServer();
    //    if (!isset($this->networks[$defaultServer->getNetworkId()])) {
    //      throw new \Exception('Contract "' . $this->label() . '" is not deployed at active default network (' . $defaultServer->id() . ')');
    //    }
    return $this->networks[$defaultServer->getNetworkId()];
  }

  /**
   * @return object[]
   *   Smart contract ABI.
   */
  public function getAbi() {
    return json_decode($this->abi);
  }

  /**
   * @param string $abi
   *   JSON Encoded ABI.
   */
  public function setAbi(string $abi) {
    $this->abi = $abi;
  }

  /**
   * @return object
   * @throws \Exception
   */
  public function getJsObject() {
    return (object) [
      'address' => $this->getCurrentNetworkAddress(),
      'jsonInterface' => $this->getAbi(),
    ];
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
  public function getCallable(Ethereum $web3 = NULL) {
    if (!$web3) {
      $web3 = \Drupal::service('ethereum.client');
    }

    /* @var $manager \Drupal\ethereum_smartcontract\SmartContractLoader */
    $manager = \Drupal::service('plugin.manager.smartcontract');

    if ($manager->getDefinition($this->id, FALSE)) {
      return $manager->createInstance($this->id, [
        $this->getAbi(),
        $this->getCurrentNetworkAddress(),
        $web3,
      ]);
    }
    else {
      // Fall back on default contract class if no plugin defined.
      return new Web3Contract(
        $this->getAbi(),
        $this->getCurrentNetworkAddress(),
        $web3
      );
    }
  }

  /**
   * getSolidityClassName().
   *
   * @see https://regex101.com/r/pjfNb9/2
   *
   * @return string
   *   Solidity Contract class name.
   * @throws \Exception
   */
  public function getSolidityClassName() {
    $matches = [];
    preg_match_all("/contract\s*(?'classname'\w*)\s*\{/", $this->contract_src, $matches, PREG_SET_ORDER, 0);
    if (!isset($matches[0]['classname']) || strlen($matches[0]['classname']) <= 1) {
      throw new \Exception('Can not determine contract name from Solidity source.');
    }
    return $matches[0]['classname'];
  }

  /**
   * Get all deployed contracts
   *
   * @return array
   *   Deployed contract address and network.
   */
  public function getDeployed() {
    $all = \Drupal::service('ethereum.manager')->getAllNetworks();
    $deployed = [];
    foreach ($this->getNetworks() as $netId => $address) {
      $deployed [$netId] = $all[$netId];
      $deployed [$netId]['contract_address'] = $address;
    }
    return $deployed;
  }

}

