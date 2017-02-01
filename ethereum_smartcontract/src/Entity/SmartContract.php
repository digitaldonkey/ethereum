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
*   },
*   links = {
*     "edit-form" = "/admin/config/ethereum/smartcontracts/{smartcontract}",
*     "delete-form" = "/admin/config/ethereum/smartcontract/{smartcontract}/delete",
*   }
* )
*/
class SmartContract extends ConfigEntityBase implements SmartContractInterface {

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

// Your specific configuration property get/set methods go here,
// implementing the interface.
}

