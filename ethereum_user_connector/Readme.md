# Ethereum User Connector

Drupal Drupal user connector enables Drupal Users to verify an Ethereum account address.

#### Module status

* Smart contract integration of a simple user registry. 
* Drupal user 
  * submits his Ethereum Account address in the User settings form.
  * address will be verified by the user by submitting a Drupal given Hash to the register smart contract
  * Drupal Backend verifies the Blockchain submission on PHP level
  * When a new user submission is found in the Login Contract, the user will be given the role "Ethereum Authenticated User."
* Works currently with Metamask Browser Plug-in, Mist Browser or any web3 provider

# Setup

* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network)
* Check the *User Connector Configuration* (/admin/config/ethereum/user-connector) and save the form to validate settings.
* 
 
