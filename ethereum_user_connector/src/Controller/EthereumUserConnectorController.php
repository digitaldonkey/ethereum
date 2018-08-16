<?php

namespace Drupal\ethereum_user_connector\Controller;

use Drupal\ethereum\Controller\EthereumController;
use Drupal\ethereum_smartcontract\SmartContractInterface;
use Ethereum\DataType\EthD;
use Ethereum\EthereumStatic;

/**
 * Controller routines for Ethereum User Connector routes.
 */
class EthereumUserConnectorController extends EthereumController {

  protected $registerDrupal;

  /**
   * Get the contract_address.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Exception
   *
   * @return string
   *   Address of the currently active contract.
   */
  public static function getContractAddress() {
    return self::getContractEntity()->getCurrentNetworkAddress();
  }

  /**
   * Get SmartContract config entity.
   *
   * @return SmartContractInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function getContractEntity() {
    return \Drupal::entityTypeManager()->getStorage('smartcontract')->load('register_drupal');
  }

  /**
   * Verify User by Drupal hash.
   *
   * @param string $hash
   *   Drupal generated hash.
   *
   * @return array
   *    The current status of the ethereum node.
   *
   * @throws \Exception
   */
  public function verifyUserByHash($hash) {
    try {
      // Callable Smart contract of type: \Ethereum\SmartContract.
      $contract = $this->getContractEntity()->getCallable();

      // Call contract function.
      $user_address = $contract->validateUserByHash(new EthD(EthereumStatic::ensureHexPrefix($hash)));

      if (!$user_address->isNotNull()) {
        throw new \Exception('No Ethereum address found in login smart contract registry for drupal hash: ' . $hash);
      }

      // Check if User Exists
      $query = \Drupal::service('entity.query')
        ->get('user')
        ->condition('field_ethereum_drupal_hash', $hash)
        ->condition('field_ethereum_address', $user_address->hexVal());
      $entity_ids = $query->execute();

      if (empty($entity_ids) || count($entity_ids) !== 1) {
        throw new \Exception('No Drupal user found for Ethereum address and drupal hash. Address: ' . $user_address->hexVal() . ' Hash: ' . $hash);
      }

      // Update User's ethereum_account_status field.
      $uid = reset($entity_ids);
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      $user->field_ethereum_account_status->setValue('2');
      if ($user->save() !== SAVED_UPDATED) {
        throw new \Exception('Error updating user Ethereum status for UID: ' . $uid);
      }
    }
    catch (\Exception $e) {
      // Log the exception to watchdog.
      watchdog_exception('ethereum_user_connector', $e);
      return array(
        'success' => FALSE,
      );
    }

    // Jay! User validated and Confirmed.
    return array(
      'success' => TRUE,
      'message' => 'Successfully validated address ' . $user_address->hexVal(),
      'uid' => $uid,
      'ethereum_address' => $user_address->hexVal(),
      'ethereum_drupal_hash' => $hash,
    );
  }

}
