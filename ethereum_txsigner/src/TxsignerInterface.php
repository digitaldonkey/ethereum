<?php
namespace Drupal\ethereum_txsigner;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
* Provides an interface defining an Example entity.
*/
interface TxsignerInterface extends ConfigEntityInterface {
// Add get/set methods for your configuration properties here.

  /**
   * @return string
   */
  public function jsFilePath();

}
