# Ethereum User Connector

Drupal Drupal user connector enables Drupal Users to verify an Ethereum account address.

You may check out A demo video here: https://youtu.be/Y5Sa7QtpXSE
It shows a prove of concept how to set up a "unpayed paywall" using this module. 

#### Module status

* Smart contract integration of a simple user registry. 
* Drupal user 
  * submits his Ethereum Account address in the User settings form.
  * address will be verified by the user by submitting a Drupal given Hash to the register smart contract
  * Drupal Backend verifies the Blockchain submission on PHP level
  * When a new user submission is found in the Login Contract, the user will be given the role "Ethereum Authenticated User."
* Works currently with Metamask Browser Plug-in, Mist Browser or any web3 provider
* Note: Currently it is not ensures that a Ethereum Address is unique, but the validation fails if it not unique. Will be fixed soon.

# Setup

* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network)
* Check the *User Connector Configuration* (/admin/config/ethereum/user-connector) and save the form to validate settings.
* Give permisssion for authenticated users to access the Rest Services
  * restful get update_ethereum_address
  * restful get validate_ethereum_account
  
UPDATE 
* ~Non annonymus registration?~ We will do this in ethereum_signup module after it's merged
*   Use custom display settings for the following form modes in admin/config/people/accounts/form-display and make sure that the fields **Ethereum Account status** and **Ethereum Address** are visible in user Edit, but not in Register form. 	


*   Only email? https://www.drupal.org/project/email_registration

 
