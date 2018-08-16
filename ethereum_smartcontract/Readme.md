# Ethereum Smart Contract Entity


Prototype of Drupal Smart Contract ABI interface.

* Provide a common configuration/PHP interface for smart contracts. 
* Is a *Drupal Config Entity* based config wrapper to smart contracts deployed on different networks.
* `getCallable()` will give you a [callable contract object](http://ethereum-php.org/dev/dc/dc6/class_ethereum_1_1_smart_contract.html)

```php
  /**
   * Get a callable SmartContract.
   *
   * @param \Ethereum\Ethereum|NULL $web3
   * @throws \Exception
   * @return \Ethereum\SmartContract
   */
  public function getCallable(Ethereum $web3 = null) {
    if (!$web3) {
      $web3 = \Drupal::service('ethereum.client');
    }
    return new Web3Contract(
      $this->getAbi(),
      $this->getCurrentNetworkAddress(),
      $web3
    );
  }

```

so you can use a contract like 

```php 
try {
  $contractConfigEntity = \Drupal::entityTypeManager()->getStorage('smartcontract')->load('register_drupal');

  // Callable Smart contract of type: \Ethereum\SmartContract.
  $contract = $$contractConfigEntity->getCallable();

  // Call contract function.
  $user_address = $contract->validateUserByHash(new EthS($hash));
}
catch {
  
}

```

This will give you the return of the `validateUserByHash()` of the [Solidity smart contract](https://github.com/digitaldonkey/register_drupal_ethereum/blob/master/contracts/RegisterDrupal.sol) deployed on the current Drupal Ethereum Default network (admin/config/ethereum/network) or throw an exception if you Default server is not running. 

Read about the <a href="https://solidity.readthedocs.io/en/develop/abi-spec.html">Ethereum Contract ABI</a>. 
