<?php

namespace Drupal\ethereum_smartcontract\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ethereum_smartcontract\SmartContractInterface;

// use Drupal\ethereum_smartcontract\Controller\SmartContractListBuilder;

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

  const CONTRACT_PATH = 'vendor/contracts';

  /**
  * Contract machine name.
  *
  * @var $id string
  */
  public $id;

  /**
  * Contract human readable name.
  *
  * @var $label string
  */
  public $label;

  /**
   * Contract Solidity source code.
   *
   * @var $contract_src string
   */
  public $contract_src;

 /**
  * Compiled contract JSON as provided by solidity compiler (solc).
  *
  * @var $contract_compiled_json string
  */
  public $contract_compiled_json;


  /**
   * Compiled contract JSON as provided by solidity compiler (solc).
   *
   * @var $contract_compiled_json string
   */
   public $networks;

}

