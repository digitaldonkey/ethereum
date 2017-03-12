<?php

/**
 * @file
 * Contains \Drupal\ethereum\Controller\EthereumController.
 */

namespace Drupal\ethereum_user_connector\Controller;

use Drupal;
use Drupal\ethereum\Controller\EthereumController;
use Ethereum\CallTransaction;
use Ethereum\EthD;
use Ethereum\EthD20;
use Ethereum\EthS;

/**
 * Controller routines for Ethereum routes.
 */
class EthereumUserConnectorController extends EthereumController {

  /**
   * Get the contract_address.
   *
   * @return string
   *   Address of the currently active contract.
   */
  public static function getContractAddress() {
    $current = Drupal::config('ethereum.settings')->get('current_server');
    return Drupal::config('ethereum_user_connector.settings')->get($current);
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
      //
      // CONTRACT FUNCTION CALL SIGNATURE
      //
      // You may validate signatures by putting the contract solidity code in
      // http://ethereum.github.io/browser-solidity.
      //
      // TODO:
      // REPLACE HARDCODED SIGNATURE WITH SIGNATURE FROM SMART-CONTRACT MODULE.
      //
      // Note: You may generate a function signature usung Ethereum. E.g:
      // $s = $this->client->getMethodSignature('validateUserByHash(bytes32)');
      // Using Signature stored in settings.

      $hash_string = new EthS($hash);
      $hash_val = $this->client->removeHexPrefix($hash_string->hexVal());

      $call = new EthD($this->client->ensureHexPrefix($this->config('ethereum_user_connector.settings')->get('contract_validateUserByHash_call')) . $hash_val);

      $contract_address = new EthD20($this->client->ensureHexPrefix($this->getContractAddress()));
      /*
       * curl https://ropsten.infura.io/drupal -X POST --data '{"jsonrpc":"2.0","method":"eth_call","params":[{"to":"0x4f1116b6e1a6e963efffa30c0a8541075cc51c45", "data":"0x2573ce276464656139653933323464616462633739373539386661633661326663363731"},"latest"],"id":1}'
       * curl -X POST --data '{"jsonrpc":"2.0","method":"eth_call","params":[{"to":"0x688a4ec356d41fc1f7ecdb1e2725e5ceb7c59e9b", "data":"0x2573ce273935633061643731653665666534623335393762323832636636653334646266"},"latest"],"id":1}' localhost:8545
       */
      $user_address = $this->client->eth_call(new CallTransaction($contract_address, NULL, NULL, NULL, NULL, $call));

      $user_address = $user_address->convertTo('D20');

      if (!$user_address->val() === 0) {
        throw new \Exception('No Ethereum address found in login smart contract registry for drupal hash: ' . $hash);
      }

      // Check if User Exists
      $query = Drupal::service('entity.query')
        ->get('user')
        ->condition('field_ethereum_drupal_hash', $hash)
        ->condition('field_ethereum_address', $user_address->hexVal());
      $entity_ids = $query->execute();

      if (empty($entity_ids) || count($entity_ids) !== 1) {
        throw new \Exception('No Drupal user found for Ethereum address and drupal hash. Address: ' . $user_address . ' Hash: ' . $hash);
      }

      // Update User's ethereum_account_status field.
      $uid = reset($entity_ids);
      $user = Drupal::entityTypeManager()->getStorage('user')->load($uid);
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
