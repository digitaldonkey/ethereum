# Ethereum Signup

### Purpose

This module enables user registration with Drupal using Ethereum's "[personal_sign](https://github.com/ethereum/go-ethereum/wiki/Management-APIs#personal_sign)" function.

The *personal\_sign* function is used to sign Drupal generated messages with the Ethereum private key in the browser. With the corresponding *personal\_ecRecover* Drupal can verify that the message was signed with the private key. 
Two types of messages are signed

* Registration message (Sign-up terms) is used to create an account.
* Log-in Message will be generated on every login request. The message must have uniqueness: The placeholder "#date" will be replaced with the formatted ('Default short date') current server time.

The Module hooks in user_login_form and user_register_form to provide an option to sign up using Ethereum.
The form user_pass_reset is changed if the user is registered with Ethereum in order to provide email verification without setting a password. 

### Ethereum Signup vs Ethereum User connector

*Ethereum Signup* module only uses Ethereum cryptographic functions. No transactions are made during the signup. Sign-up can be archived with zero cost.

*Ethereum User connector* uses a Ethereum *smart contract* to verify user addresses. Drupal will generate a hash which is submitted to a registry (consider it an array) in the smart contract on the blockchain. A transaction signed to the blockchain requires the user to have Ether on the signing account to pay for the transaction. 
You might want to evaluate this module when you intend to create a paywall by modifying the [smart contract](https://github.com/digitaldonkey/register_drupal_ethereum).

There is no connection between this two modules currently and they are not tested together.

### Configurable registration options

**Visitors can directly log in.**
This would provide a Ethereum account-only Drupal account. No passwords or emails are required. 

**Require Administrator to approve accounts.**
Requires email to send out approval.

**Require email confirmation before activating accounts**
Requires email to send out email conformation mail.


**Require email** Deactivate to have Ethereum-only accounts in combination with *Visitors can directly log in*.
Make sure *Require email verification when a visitor creates an account* and *Notify user when account is activated* **are not checked in Drupal account settings (/admin/config/people/accounts)**.
