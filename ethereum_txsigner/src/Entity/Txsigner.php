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
*     "status" = "status",
*     "jsFilePath" = "jsFilePath",
*     "cssFilePath" = "cssFilePath",
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
  protected $id;

  /**
  * The label.
  *
  * @var string
  */
  protected $label;

  /**
   * Is active.
   *
   * @var boolean
   */
  protected $status;

  /**
   * The javascript library file path.
   *
   * @var string
   */
  protected $jsFilePath;

  /**
   * The javascript library file path.
   *
   * @var string
   */
  protected $cssFilePath;

  /**
   * @return string
   */
  public function jsFilePath(){
    return $this->jsFilePath;
  }

  /**
   * @return string
   */
  public function cssFilePath(){
    return $this->cssFilePath;
  }

  /**
   * @param string|null $path
   *
   * @return bool
   */
  public static function isValidFilePath($path) {

    // Min length.
    if (!strlen(trim($path)) > 2) {
      return false;
    }
    // Relative path.
    if (substr($path, 0, 1) === '/') {
      return false;
    }
    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'ethereum_txsigner') . '/js/'. $path;
    return file_exists($path);
  }

}
