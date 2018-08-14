<?php

namespace Drupal\ethereum_user_connector\Plugin\SmartContract;

use Ethereum\SmartContract;

/**
 * @SmartContract(
 *   id = "register_drupal"
 * )
 */
class RegisterDrupal extends SmartContract {

  public function onAccountCreated($args) {
    // You can implement any EventHandler here.
    // The method name must be "on" + NameOfSolidityEvent
    $X = FALSE;
  }

}
