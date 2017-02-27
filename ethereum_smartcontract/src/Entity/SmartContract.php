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
*     "contract_js" = "contract_js",
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
  * The Example ID.
  *
  * @var string
  */
  public $id;

  /**
  * The Example label.
  *
  * @var string
  */
  public $label;

  public $contract_src;
  public $contract_js;

//  public static function get_contract_src_file($id) {
//
//    return $this->contractRootPath() . $this->contract_src;
//  }
//
//  public function get_contract_js_file() {
//    return $this->contractRootPath() . $this->contract_js;
//  }
//
//  private function contractRootPath() {
//    global $base_url;
//    return $base_url . '/' . drupal_get_path('module', 'ethereum_smartcontract') . '/' . self::CONTRACT_PATH . '/';
//  }
}

