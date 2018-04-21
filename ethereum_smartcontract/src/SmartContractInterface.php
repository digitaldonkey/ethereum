<?php
namespace Drupal\ethereum_smartcontract;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Ethereum\Ethereum;

/**
* Provides an interface defining an Example entity.
*/
interface SmartContractInterface extends ConfigEntityInterface {


  /**
   * @inheritdoc
   */
  public function status();

  /**
   * Get the Contract address
   * using current Ethereum default Server.
   *
   * @throws \Exception
   *   If contract is not available on current network.
   *
   * @return string
   *   Contract address.
   */
  public function getCurrentNetworkAddress();

  /**
   * Get a callable instance of a SmartContract
   *
   * @see:http://ethereum-php.org/dev/dc/dc6/class_ethereum_1_1_smart_contract.html
   *
   * @param \Ethereum\Ethereum
   *
   * @return \Ethereum\SmartContract
   */
  public function getCallable(Ethereum $web3 = NULL);

  /**
   * Get all deployed contracts
   *
   * @return array
   *   Deployed contract address and network.
   */
  public function getDeployed();

}
