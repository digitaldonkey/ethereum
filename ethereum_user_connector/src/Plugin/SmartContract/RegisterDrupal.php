<?php

namespace Drupal\ethereum_user_connector\Plugin\SmartContract;

use Ethereum\SmartContract;
use Ethereum\EmittedEvent;

/**
 * @SmartContract(
 *   id = "register_drupal"
 * )
 */
class RegisterDrupal extends SmartContract {

  public function onAccountCreated(EmittedEvent $event) {

    // You can implement any EventHandler here.
    // The method name must be "on" + NameOfSolidityEvent
    // you MUST take care that your event values are only processed once,
    // this handler might get called multiple times with the same Event.

    \Drupal::logger('ethereum_user_connector')->info(
      'Account "@emitter" was added to the registry contract at "@contract".', [
        '@emitter' => $event->getEmitter(),
        '@contract' => $event->getContract(),
      ]);
  }

}
