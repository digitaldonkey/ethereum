<?php

namespace Drupal\ethereum_user_connector\Plugin\SmartContract;

use Ethereum\SmartContract;
use Ethereum\EmittedEvent;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;

/**
 * @SmartContract(
 *   id = "register_drupal"
 * )
 */
class RegisterDrupal extends SmartContract {

  public static function onAccountCreated(EmittedEvent $event) {

    // You can implement any EventHandler here.
    // The method name must be "on" + NameOfSolidityEvent
    // you MUST take care that your event values are only processed once,
    // this handler might get called multiple times with the same Event.

    /* @var $controller \Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController */
    $controller = \Drupal::service('class_resolver')->getInstanceFromDefinition(EthereumUserConnectorController::class);

    /* @var $hash \Ethereum\DataType\EthD32 */
    $hash = $event->getData()['hash'];
    $event->setResponse($controller->verifyUserByHash($hash->hexVal()));
  }

}
