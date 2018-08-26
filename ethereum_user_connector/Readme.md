# Ethereum User Connector

Drupal Drupal user connector is a module providing integrration of Drupal and Ethereum *registry* [Smart Contract](https://github.com/digitaldonkey/register_drupal_ethereum/blob/master/contracts/RegisterDrupal.sol) written in Solidity. 

**TLDR;**

* Smart contract integration of a simple user registry. 
* Drupal user 
  * Submits his Ethereum Account address in the User settings form.
  * Address will be verified by the user by submitting a Drupal given hash to the registry smart contract.
  * Drupal Backend verifies the Blockchain submission on PHP level by evaluating Events.
  * When a new user submission is found in the Login Contract, the user will be given the role "Ethereum Authenticated User."


##Description
 
An Ethereum-based registry system you to timestamp, register and store data on immutable data layer of the Ethereum World Computer.
The registry is basically an a blockchain stored associative array. The contract consists mainly of a function to store a 32bit hash, which easily could be changed to require a payment in Ether in order to store that hash. 

The Drupal module adds fields to the User. After adding a Ethereum address to the user profile you may *verify* it on the the Ethereum Blockchain. Drupal generates a hash and the user must "Submit" this hash to Ethereum using a *transaction signer* (Metamask). The *receipt* (transaction hash) gets submitted to a ajax handler provided ethereum_smartcontract module, which will process the *AccountCreated* event (`RegisterDrupal::onAccountCreated()`) and add a role *Authorized Ethereum User* to the users Drupal account.

* You may check out a [old demo video](https://youtu.be/Y5Sa7QtpXSE), which shows a prove of concept how to set up a "unpayed paywall" using this module. 
* Works currently with Metamask Browser Plug-in, Mist Browser or any web3 provider
* Note: Currently it is not ensures that a Ethereum Address is unique, but the validation fails if it not unique.

## Setup

* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network)
* Check the *User Connector Configuration* (/admin/config/ethereum/user-connector) and save the form to validate settings.
* Give permission for *authenticated users* to access the [Rest Services UI](https://www.drupal.org/project/restui)
  * **Update Ethereum account**	 `/ethereum/update-account/{address}: GET` using JSON
  * **Event processor** `/ethereum/process_tx/{tx_hash}: GET` using JSON
* Setting up UI
  * Add custom Display settings for Register/Login at /people/accounts/form-display
  * Add **Ethereum Address** and **Ethereum Account status** fields to the *user edit form*. Make sure that the fields are visible in user Edit, but not in Register form.
  * Remove the search form at admin/structure/block. It has [the same ID than the user](https://www.drupal.org/project/drupal/issues/2938842)
  * Add the **Ethereum transaction signer Block** at admin/structure/block to your preferred theme location