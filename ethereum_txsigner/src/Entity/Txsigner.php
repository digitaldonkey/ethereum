<?php

namespace Drupal\ethereum_txsigner\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ethereum_txsigner\TxsignerInterface;

use Drupal\ethereum_txsigner\Controller\TxsignerListBuilder;

/**
* Defines the Txsigner entity.
*
* @ConfigEntityType(
*   id = "txsigner",
*   label = @Translation("Ethereum Transaction Signing library"),
*   handlers = {
*     "list_builder" = "Drupal\ethereum_txsigner\Controller\TxsignerListBuilder",
*     "form" = {
*       "edit" = "Drupal\ethereum_txsigner\Form\TxsignerForm",
*     }
*   },
*   config_prefix = "txsigner",
*   admin_permission = "administer site configuration",
*   entity_keys = {
*     "id" = "id",
*     "label" = "label",
*     "is_enabled" = "is_enabled",
*   },
*   links = {
*     "edit-form" = "/admin/config/ethereum/txsigner/{txsigner}",
*   }
* )
*/
class Txsigner extends ConfigEntityBase implements TxsignerInterface {

  /**
  * The ID.
  *
  * @var string
  */
  public $id;

  /**
  * The label.
  *
  * @var string
  */
  public $label;

  /**
   * Is active.
   *
   * @var boolean
   */
  public $is_enabled;

}

