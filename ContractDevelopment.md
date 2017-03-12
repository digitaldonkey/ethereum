# Getting started with Contract development 

There are many ways to get started with smart contract development.
Exemplary you will learn how to get the _login smart contract_ used in the Ethereum_user_connector module running in testrpc and how use browser solidity.

There are [plenty of other](http://solidity.readthedocs.io/en/latest/#available-solidity-integrations) Solidity integrations available.
 
## What is involved

**Solidity**

Is the programming language to write Smart Contracts.
Solidity is a high-level language whose syntax is similar to that of Javascript. It reminds to TypeScript, because it is statically typed to target the Ethereum Virtual Machine.

http://solidity.readthedocs.io/en/latest/

**Testrpc**

testrpc is a Node.js based Ethereum client for testing and development. It uses ethereumjs to simulate full client behavior and make developing Ethereum applications much faster.

https://github.com/ethereumjs/testrpc

**Truffle**

Truffle is a popular open source development framework for Ethereum.
It allows you to develop contracts, write tests and deploy them on different Ethereum networks.  

http://truffleframework.com/
https://github.com/consensys/truffle

**Browser solidity**

Browser Solidity browser based development- and runtime environment for Solidity smart contracts. 

http://ethereum.github.io/browser-solidity

**Transaction signer**

Ethereum is based on a public/private key system. In order to verify transactions you will need a Transaction signer.
Currently this is tested with [Metamask browser plug-in](https://metamask.io/) for Chromium based browsers. Metamask injects a [web3js](https://github.com/ethereum/web3.js) object into the browser which enables signing transactions on any Ethereum network. 

The GNU licensed [Mist browser](https://github.com/ethereum/mist/releases) developed by the Ethereum Foundation should work as well.
  
Drupal Ethereum Module intends to implement more TX signers in future releases.

**Infura**

[Infura](https://infura.io/) provides Ethereum as a service. In the background Metamask uses Infura infrastructure and we can use it to connect to Ethereum test- or main network.   

Drupal community has been given a special access token for easy getting started. For use beyond testing, please register your own free access token at [infura.io](https://infura.io/register.html).

## Development workflow for testrpc 


When using installing testrpc your _Ethereum network node_ is reset every time you start.

You will need [node.js and npm](https://docs.npmjs.com/getting-started/installing-node) and [git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) running. 

###Install

Install Truffle and Testrpc and get the [Register Drupal](https://github.com/digitaldonkey/register_drupal_ethereum) smart contract. 

```
# Install testrpc
npm install -g ethereumjs-testrpc

# Install truffle
npm install -g truffle

# Clone Login Smart contract 
git clone https://github.com/digitaldonkey/register_drupal_ethereum.git
```

###testrpc

The challenge is to connect a Ethereum Account with the local testrpc environment and make sure you have some Test-Ether available in the account.


####Option 1 

If you start restrpc without any parameters it will create a number off accounts which own some test-Ether.

You may use truffle console to transfer Ether to the the account in Metamask:

```
# Start testrpc
testrpc
```

In an new terminal window: 

You need to Connect Metamask to a _private network_ localhost:8545, which in our case is provided by testrpc.
 
Send one Ether to the address _0xaEC98826319EF42aAB9530A23306d5a9b113E23D_. The address you copy from your Metamask wallet.  
 
```
# Start truffle console
truffle console

# Replace t
web3.eth.sendTransaction({from: web3.eth.accounts[0], to: "0xaEC98826319EF42aAB9530A23306d5a9b113E23D", value: web3.toWei(5, "ether")});
```
Now your Account in Metamask should have a balance of 5 Ether.

####Option 2
 
Start testrpc with a defined account using it's private key is a more easily repeatable.   

First export the private key from Metamask wallet by clicking on the key. 
 
Start testapc and add 10 Ether to the given account (10 Ether = 10000000000000000000 Wei)

```
testrpc --account="0x<YOUR PRIVATE KEY>,10000000000000000000"

```
Now your Account in Metamask should have a balance of 10 Ether.


###Deploy the contract 

Truffle connects to localhost:8545 by default. If you have testrpc running it will connect there. 

Alternatively you may run [Geth](https://geth.ethereum.org/downloads/) or other [Ethereum clients](http://ethereum.stackexchange.com/a/279/852) locally or whatever client locally to connect to Ethereum test- or main networks. The process of migration with truffle is the same.

```
# Change into the login smart contract directory
cd register_drupal_ethereum

# run migration scipt
truffle migrate

Running migration: 1_initial_migration.js
  Replacing Migrations...
  Migrations: 0x6dc525e5f10feac8aad2e2cb54bb1bc87d224c9e
Saving successful migration to network...
Saving artifacts...
Running migration: 2_deploy_contracts.js
  Replacing RegisterDrupal...
  RegisterDrupal: 0xaaaafb8dbb9f5c9d82f085e770f4ed65f3b3107c
Saving successful migration to network...
Saving artifacts...
```

Contract is migrate to the address mentioned in _RegisterDrupal_ (in this example: 0xaaaafb8dbb9f5c9d82f085e770f4ed65f3b3107c)

You will need to ensure drupal is set to localhost:8545 in /admin/config/ethereum/network.
And that the migrated address is set in /admin/config/ethereum/user-connector.  

Note:

The [ABI](https://github.com/ethereum/wiki/wiki/Ethereum-Contract-ABI) of a smart contract is defined by type and name of a _contract function call_. If you change it, you will need to change these values in drupal_user_connector settings too. The other way: if you modify the login contract only a little, for example let the user pay some Ether for sign-up, you can keep the rest of the user signup module as it is.   

####Developing with Truffle
 
```
# Change into the login smart contract directory
cd register_drupal_ethereum

# run migration scipt
truffle migrate

# run truffle's test server
truffle serve

Running migration: 1_initial_migration.js
  Replacing Migrations...
  Migrations: 0x6dc525e5f10feac8aad2e2cb54bb1bc87d224c9e
Saving successful migration to network...
Saving artifacts...
Running migration: 2_deploy_contracts.js
  Replacing RegisterDrupal...
  RegisterDrupal: 0xaaaafb8dbb9f5c9d82f085e770f4ed65f3b3107c
Saving successful migration to network...
Saving artifacts...
``` 


##Under the surface
Drupal Backend uses [ethereum-php](https://github.com/digitaldonkey/ethereum-php) which implements implementing the 
[JSON-RPC API](https://github.com/ethereum/wiki/wiki/JSON-RPC).

Drupal Frontend depends on [web3js](https://github.com/ethereum/web3.js) implementing Ethereum [JavaScript-API](https://github.com/ethereum/wiki/wiki/JavaScript-API).


##Future reading

More detailed getting started readings are [A 101 Noob Intro to Programming Smart Contracts](https://medium.com/@ConsenSys/a-101-noob-intro-to-programming-smart-contracts-on-ethereum-695d15c1dab4) or the [Hitchhiker’s Guide to Smart Contracts](https://medium.com/zeppelin-blog/the-hitchhikers-guide-to-smart-contracts-in-ethereum-848f08001f05). 
