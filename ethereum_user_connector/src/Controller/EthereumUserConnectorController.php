<?php

/**
 * @file
 * Contains \Drupal\ethereum\Controller\EthereumController.
 */

namespace Drupal\ethereum_user_connector\Controller;

use Drupal;
use Ethereum\Ethereum_Message;
use Drupal\ethereum\Controller\EthereumController;
use RuntimeException as Exception;


/**
 * Controller routines for Ethereum routes.
 */
class EthereumUserConnectorController extends EthereumController {

  // The deployed code as you would get it using eth_getCode() varies depending
  // on how you deployed it. But it must contain the following part
  const ContractCode = '60606040523615610081576000357c0100000000000000000000000000000000000000000000000000000000900463ffffffff168063111b72c3146100865780632573ce27146100cf578063345e3416146101305780633af41dc21461013f57806349f0c90d1461014e5780639b6d86d614610181578063f845862f146101a0575b610000565b34610000576100cd600480803573ffffffffffffffffffffffffffffffffffffffff16906020019091908035600019169060200190919080359060200190919050506101c1565b005b34610000576100ee60048080356000191690602001909190505061021a565b604051808273ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200191505060405180910390f35b346100005761013d610260565b005b346100005761014c610336565b005b346100005761017f600480803573ffffffffffffffffffffffffffffffffffffffff169060200190919050506103ca565b005b346100005761019e60048080351515906020019091905050610466565b005b34610000576101bf6004808035600019169060200190919050506104db565b005b81600019168373ffffffffffffffffffffffffffffffffffffffff167fdd4adf6c5ef0c5a78200bcebce519e97649524d2c8dc89cf0f8baa99cc39bb53836040518082815260200191505060405180910390a35b505050565b600060006000836000191660001916815260200190815260200160002060009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1690505b919050565b600160009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff16141561033357600160009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff166108fc3073ffffffffffffffffffffffffffffffffffffffff16319081150290604051809050600060405180830381858888f19350505050151561033257610000565b5b5b565b600160009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff1614156103c757600160009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16ff5b5b565b600160009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff1614156104625780600260006101000a81548173ffffffffffffffffffffffffffffffffffffffff021916908373ffffffffffffffffffffffffffffffffffffffff1602179055505b5b50565b600160009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff1614156104d75780600260146101000a81548160ff0219169083151502179055505b5b50565b3373ffffffffffffffffffffffffffffffffffffffff1660006000836000191660001916815260200190815260200160002060009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16141561055b57610556338260046101c1565b610674565b600060006000836000191660001916815260200190815260200160002060009054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1611156105c6576105c1338260036101c1565b610673565b6020602060ff1611156105e4576105df338260026101c1565b610672565b600260149054906101000a900460ff161561060a57610605338260016101c1565b610671565b3360006000836000191660001916815260200190815260200160002060006101000a81548173ffffffffffffffffffffffffffffffffffffffff021916908373ffffffffffffffffffffffffffffffffffffffff160217905550610670338260006101c1565b5b5b5b5b5b505600a165627a7a72305820f86f3ba197e71611d043bb1f331f5d3da10ddca016a5216290c3312fcea4248d0029';


  /**
   * Get the contract_address.
   *
   * @return string - Address of the currently active contract.
   *    depending on the current network.
   */
  public function get_contract_address() {
    $current = $this::config('ethereum.settings')->get('current_server');
    return $this::config('ethereum_user_connector.settings')->get($current);
  }

  /**
   * Displays the ethereum status report.
   *
   * @param $hash
   * @return array
   *    The current status of the ethereum node.
   * @throws \Exception
   */
  public function verifyUserByHash($hash) {

    try {

      // Verify valid $hash.
      if (strlen($hash) !== 32 || !ctype_xdigit($hash)) {
        throw new \InvalidArgumentException($hash . ' is not a valid ethereum_drupal_hash.');
      }

      //
      // CONTRACT FUNCTION CALL SIGNATURE
      //
      // You may validate signatures by putting the contract solidity code in http://ethereum.github.io/browser-solidity
      //
      // TODO: REPLACE HARDCODED SIGNATURE WITH SIGNATURE FROM SMART-CONTRACT MODULE.
      //
      // Note: You may get the signature by string easily:
      // $signature = $this->client->getMethodSignature('validateUserByHash(bytes32)');
      //
      // Using Signature stored in settings.
      $signature = '0x' . $this->config('ethereum_user_connector.settings')->get('contract_validateUserByHash_call');

      // Prepare call to Smartcontract.
      $message = new Ethereum_Message($this->get_contract_address());
      $message->setArgument(
        $signature,
//        $this->client->strToHex($hash)
//        $hash
        '6464656139653933323464616462633739373539386661633661326663363731'
      );

      // curl https://ropsten.infura.io/drupal -X POST --data '{"jsonrpc":"2.0","method":"eth_call","params":[{"to":"0x4f1116b6e1a6e963efffa30c0a8541075cc51c45", "data":"0x2573ce276464656139653933323464616462633739373539386661633661326663363731"},"latest"],"id":1}'

      // 0xf845862f6464656139653933323464616462633739373539386661633661326663363731
      // 0x2573ce276464656139653933323464616462633739373539386661633661326663363731

      // Call to Smartcontract.
      $user_address = $this->client->eth_call($message, 'latest');
      $user_address = $this->client->hexToAddress($user_address);
      if (!$user_address) {
        throw new \Exception('No Ethereum address found in login smart contract registry for drupal hash: ' . $hash);
      }

      // Check if User Exists
      $query = Drupal::service('entity.query')
        ->get('user')
        ->condition('field_ethereum_drupal_hash', $hash)
        ->condition('field_ethereum_address', $user_address);
      $entity_ids = $query->execute();

      if (empty($entity_ids) || count($entity_ids) !== 1) {
        throw new \Exception('No Drupal user found for Ethereum address and drupal hash. Address: ' . $user_address . ' Hash: ' . $hash);
      }

      // Update User's ethereum_account_status field.
        $uid = reset($entity_ids);
        $user = Drupal::entityTypeManager()->getStorage('user')->load($uid);
        $user->get('field_ethereum_account_status')->setValue('2');
        if ($user->save() !== SAVED_UPDATED) {
          throw new \Exception('Error updating user Ethereum status for UID: '. $uid);
        }
    }
    catch (Exception $e) {
      // Log the exception to watchdog.
      watchdog_exception('ethereum_user_connector', $e);
      return array (
        'success' => FALSE,
      );
    }

    // Jay! User validated and Confirmed.
    return array (
      'success' => TRUE,
      'message' => 'Successfully validated address ' . $user_address,
      'uid' => $uid,
      'ethereum_address' => $user_address,
      'ethereum_drupal_hash' => $hash,
    );
  }

}


