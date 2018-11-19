Drupal Ethereum
===============
 

**Introduction**

Drupal Ethereum Module enhances the Drupal ecosystem with Ethereum SmartContract functionality. 

The basic **Ethereum module** provides a basic framework to interact with the Blockchain via the [Ethereum-PHP](https://github.com/digitaldonkey/ethereum-php) library and is prepared to talk to different Ethereum network nodes (like development, testing, live).

[**Ethereum Smartcontract**](https://github.com/digitaldonkey/ethereum/tree/8.x-1.x/ethereum_smartcontract) submodule allows you to manage smart contracts and use Drupal configuration to connect to the contract depending on the Network the contract has been deployed to. 

<small>This module provides a schema to manage smart contracts (ethereum_smartcontract.schema.yml)</small>

[**Ethereum TXsigner**](to be merged) module is responsible for the frontend part of user interaction. It is currently limited to Web3 enabled Browsers, like [Metamask](https://metamask.io/) browser extension, [Brave Browser](https://brave.com/), [Mist browser](https://github.com/ethereum/mist/releases), but also supports [Metamask Mascara](https://github.com/MetaMask/mascara) (but Mascara is early Alpha by now). 

<small>This module provides a web3 instance so you will have the same Web3 version and not depend what is injected by whichever browser/extension is used.</small>

[**Ethereum User connector**](https://github.com/digitaldonkey/ethereum/blob/8.x-1.x/ethereum_user_connector/Readme.md) is a submodule providing a very simple registry contract (See the [Prove of concept (POC) video](https://www.youtube.com/watch?v=Y5Sa7QtpXSE). This is a early example how the actual applications would be Able to use the infrastructure provided by Drupal Ethereum module.

<small>This module is an example of a Smartcontract. Currently refactoring to match the post POC infrastructure described above.</small>

**Community**

* [Gitter](https://gitter.im/drupal_ethereum)
* [Issues](https://github.com/digitaldonkey/ethereum/issues)
* [Drupal page](https://www.drupal.org/project/ethereum)
* [Drupal Ethereum user group](https://groups.drupal.org/ethereum)
* [PHP Library Issues](https://github.com/digitaldonkey/ethereum-php/issues)

You might watch my Drupal conference talks from [Vienna](https://events.drupal.org/vienna2017/sessions/drupal-and-ethereum-blockchain) or [Baltimore](https://events.drupal.org/baltimore2017/sessions/drupal-and-ethereum-blockchain).
 
## Drupal Ethereum Getting started

**TLDR; Quick set-up**

* Add Web3js to the "repositories" section of your root `composer.json` (see "Add the following to the repositories section..."  below)
* Run `composer install` to get all required dependencies
* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network). **Saving the form settings** will validate the current settings and let you know if something is wrong. 
* You can start testing with Infura networks provided. 

If you used [composer](https://www.lullabot.com/articles/goodbye-drush-make-hello-composer) to install Drupal core and the Ethereum module everything should work out of the box using <a href="infura.io">Infura's free service</a> to connect to the Ethereum network. 
 
### Long version

The quickest way to do this is to follow the steps below. You will need PHP [Composer](https://getcomposer.org/) and [drush](http://www.drush.org/) installed on your system. Alternatively, you can <a href="https://www.drupal.org/docs/8/install">install Drupal manually</a>. 

**Install Drupal**

```
# Download latest Drupal
composer create-project drupal-composer/drupal-project:~8.0 drupal --stability dev --no-interaction

# Create a Database
mysql -uroot  --execute="CREATE DATABASE \`drupalEthereumTest.local\`;"

# Drupal with drush
# Create a configuration export directory. This is not required, but very usefull.
mkdir drupal/config

# Change to web root 
cd drupal/web/
# Scripted drupal installation. Revalidate the database. It will be overwritten.
drush site-install standard --db-url="mysql://root:root@localhost:3306/drupalEthereumTest.local" --account-name="tho" --account-pass="password" --site-name="drupalEthereum.local" --account-mail="email@donkeymedia.eu" --site-mail="email@donkeymedia.eu" --config-dir="../config" --notify="global"
```

_DON'T FORGET TO CHANGE YOUR PASSWORD AFTER FIRST LOGIN._


**Composer install**

[RestUI](https://www.drupal.org/project/restui) is a user interface for Drupal 8's REST module. You'll need it for development.

Add the following to the "repositories" section of your root `composer.json` file:

```
{
    "type": "package",
    "package": {
        "name": "ethereum/web3.js",
        "version": "1.0.0-beta.35",
        "type": "drupal-library",
        "dist": {
            "url": "https://github.com/ethereum/web3.js/archive/v1.0.0-beta.35.zip",
            "type": "zip"
        }
    }
},
```

Composer commands need to be run from the drupal directory

```
# Download using composer
composer require drupal/ethereum drupal/restui drupal/admin_toolbar 
composer install 
 
# Enable the modules
cd web/
drush en ethereum restui admin_toolbar_tools -y
```

Visit /admin/config/ethereum/network, save the configuration settings then check the status page at /admin/reports/ethereum to verify that it's working.

## Networks & Ethereum nodes

**Networks** are Drupal configuration. This module provides a List of networks ([ethereum.ethereum_networks.yml](https://github.com/digitaldonkey/ethereum/blob/8.x-1.x/config/install/ethereum.ethereum_networks.yml)).
Ethereum nodes and Networks are matched by the Ethereum network ID.
Currently there is no UI for editing networks. You can add new network IDs by changing file above and re-enabling the module. 

**Network Settings** are provided at */admin/config/ethereum/network*.

**Ethereum Server** also refered as *Ethereum Nodes* are the connection to the Ethereum Blockchain. We manage the Servers as editable configuration entities. 

**Default Network** setting allows to select a default route Drupal will use to connect to Ethereum Blockchain. 


## Running your own Ethereum node

**Ganache** is a very easy to use Ethereum Blockchain for development and testing. It is [available for many platforms](https://truffleframework.com/ganache) and has a Block explorer build in. 

**Docker Ganache-cli** has no Block explorer, but it allows you to keep a persisten *test chain*. Have a look at [ganache-cli-docker-compose](https://github.com/digitaldonkey/ganache-cli-docker-compose).

**Geth** is the *go-ethereum client*. If you want to use geth (go-ethereum client), <a href="https://github.com/ethereum/go-ethereum/wiki/Building-Ethereum">install it</a> then start the geth server with rpc.
On a Mac this looks like this (be aware that the "*" allows connections from any host):

``` 
 geth --testnet  --rpc --rpccorsdomain="*"
``` 


## Development environment with Truffle

If you want to experiment with developing your own smart contract, it is recommended to use [Truffle](http://truffleframework.com/).

Check out the currently provided *<a href="https://github.com/digitaldonkey/register_drupal_ethereum">Login Smart Contract</a>* by cloning/forking it. The repository contains a very basic App which helps you understand the registration contract from *ethereum_user_connector* module. 

You can now import SmartContracts from Truffle directly into Drupal configuration. 
