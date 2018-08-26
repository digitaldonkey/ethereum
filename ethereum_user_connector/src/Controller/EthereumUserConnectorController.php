<?php

namespace Drupal\ethereum_user_connector\Controller;

use Drupal\ethereum\Controller\EthereumController;
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
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
    $return = array(
      'error' => TRUE,
      'message' => null,
      'uid' => null,
      'ethereum_address' => null,
      'ethereum_drupal_hash' => $hash,
    );
    try {
      /* @var $contract \Ethereum\SmartContract */
      $contract = $this->getContractEntity()->getCallable();
      /* @var $user_address \Ethereum\DataType\EthD20 */
      $user_address = $contract->validateUserByHash(new EthD(EthereumStatic::ensureHexPrefix($hash)));
    }
    catch (\Exception $e) {
      // Log the exception to watchdog.
      watchdog_exception('ethereum_user_connector', $e);
      $return['message'] = $this->t('Could not call Ethereum Contract. Please try again later.');
      return $return;
    }

    if (!isset ($user_address) || !$user_address->isNotNull()) {
      $return['message'] = 'No Ethereum address found in login smart contract registry for drupal hash: ' . $hash;
      return $return;
    }
    else {
      $return['ethereum_address'] = $user_address->hexVal();
    }

    // Check if User Exists
    $query = \Drupal::service('entity.query')
      ->get('user')
      ->condition('field_ethereum_drupal_hash', EthereumStatic::removeHexPrefix($hash))
      ->condition('field_ethereum_address', $user_address->hexVal());
    $entity_ids = $query->execute();

    if (empty($entity_ids) || count($entity_ids) !== 1) {
      $return['message'] = $this->t(
        'No user found for Ethereum address and hash. Address @address Hash @hash',
        ['@address' => $user_address->hexVal(), '@hash' => $hash]
      );
      return $return;
    }

    // Update User's ethereum_account_status field.
    $uid = reset($entity_ids);
    /* @var $user \Drupal\user\UserInterface */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    $user->set('field_ethereum_account_status', '2');
    if ($user->save() !== SAVED_UPDATED) {
      $return['message'] = $this->t(
        'Error updating user Ethereum status for user @user',
        ['@user' => $user->label()]
      );
      return $return;
    }

    // Jay.
    \Drupal::logger('ethereum_user_connector')->info(
      'Account "@address" was added to the registry contract at "@contract".', [
      '@address' => $user_address->hexVal(),
      '@contract' => $contract->getAddress(),
    ]);

    $return['error'] = FALSE;
    $return['message'] = $this->t(
      'Successfully validated address @address', ['@address' => $return['ethereum_address']]
    );
    return $return;
  }

}
