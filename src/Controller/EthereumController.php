<?php

/**
 * @file
 * Contains \Drupal\ethereum\Controller\EthereumController.
 */

namespace Drupal\ethereum\Controller;

use Drupal\Core\Controller\ControllerBase;
use Ethereum\Ethereum;

/**
 * Controller routines for Ethereum routes.
 */
class EthereumController extends ControllerBase {

  private $config;
  public $client;

  public function __construct($host = FALSE) {
    if (!$host) {
      $this->config = \Drupal::config('ethereum.settings');
      $host = $this->config->get($this->config->get('current_server'));
    }
    $this->client = new Ethereum($host);
  }

  /**
   * Displays the ethereum status report.
   *
   * @return string
   *   The current status of the ethereum node.
   */
  public function status() {

    $rows[] = [$this->t('<b>Network status</b><br /><a href="https://github.com/digitaldonkey/ethereum">JsonRPC-API</a> Methods.'), ''];

    $rows[] = [$this->t("Client version (web3_clientVersion)"), $this->client->web3_clientVersion()];

    $rows[] = [$this->t("Listening (net_listening)"), $this->client->net_listening() ? '✔' : '✘'];
    $rows[] = [$this->t("Peers (net_peerCount)"), $this->client->net_peerCount()];
    $rows[] = [$this->t("Protocol version (eth_protocolVersion)"), $this->client->eth_protocolVersion()];
    $rows[] = [$this->t("Network version (net_version)"), $this->client->net_version()];
    $rows[] = [$this->t("Syncing (eth_syncing)"), $this->client->eth_syncing() ? '✔' : '✘'];

    // TODO Wait/test Infura fix.
    // $rows[] = [$this->t("Coinbase"), $this->client->eth_coinbase()];

    $rows[] = [$this->t("Mining (eth_mining)"), $this->client->eth_mining() ? '✔' : '✘'];
    $rows[] = [$this->t("Mining hashrate (eth_hashrate)"), $this->client->eth_hashrate()];

    $price = $this->client->eth_gasPrice();
    $rows[] = [$this->t("Current price per gas in wei (eth_gasPrice)"), $price . ' wei ( ≡ ' . number_format(($price / 1000000000000000000), 8, '.', '') . ' Ether)' ];

    // TODO Wait/test Infura fix.
    // $rows[] = [$this->t("Accounts (eth_accounts)"), $this->client->eth_accounts() ? '✔' : '✘'];

    $rows[] = [$this->t("Client latest block number (eth_blockNumber)"), $this->client->eth_blockNumber()];

    // $rows[] = [$this->t("Latest block age"), \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - hexdec($block['timestamp']), 3)];


    // TODO
    // $rows[] = [$this->t("Whisper version"), isset($results['shh_version']) ? $results['shh_version'] : t("n/a")];


    // API testing with example values below.
    $rows[] = [$this->t('<br /><b>API testing</b><br />Methods to request specific information from <a href="https://github.com/ethereum/wiki/wiki/JSON-RPC">JsonRPC-API</a> using example Data.'), ''];

    // RANDOM ADDRESS FOR TESTING
    // curl -X POST --data '{"jsonrpc":"2.0","method":"eth_getBalance","params":["0xEA674fdDe714fd979de3EdF0F56AA9716B898ec8", "latest"],"id":1}' https://mainnet.infura.io/
    // -->  {"jsonrpc":"2.0","result":"0x3a20f4e2737dbf4898","id":1}

    $balance = $this->client->eth_getBalance("0xEA674fdDe714fd979de3EdF0F56AA9716B898ec8");
    $rows[] = [$this->t("Balance example Block (eth_getBalance(0xEA674...,latest))"), $balance . 'wei ( ≡ ' . number_format(($balance / 1000000000000000000), 8, '.', '') . ' Ether)'];

    // eth_getStorageAt()
    // TODO How to Test?
    $storage_address = '0x26dd6b7a2fff271aa7c5fe8cfb5ba0ab33f47408';
    $storage = $this->client->eth_getStorageAt($storage_address,'0x0', 'latest');
    $rows[] = [$this->t('Get Storage at Address (eth_getStorageAt(' . substr($storage_address, 0, 20) . '..., 0x0, latest))'), $storage ];


    // eth_getBlockByHash
    $address = '0x26dd6B7A2ffF271AA7c5FE8Cfb5bA0aB33F47408';
    $hash = $this->client->eth_getStorageAt($address, "0x0", "latest");
    $rows[] = [$this->t('eth_getBlockByHash(' . substr($storage_address, 0, 20) . ', 0x0, earliest))'), $hash ];


    // eth_getTransactionCount
    $address = '0x26dd6b7a2fff271aa7c5fe8cfb5ba0ab33f47408';
    $qTAG= 'latest';
    $integer = TRUE;
    $hash = $this->client->eth_getTransactionCount($address, $qTAG, $integer);
    $rows[] = [$this->t('eth_getTransactionCount (' . substr($address, 0, 20) . ', TRUE))'), $hash ];


    // web3_sha3
    // Keccac SHA-256 (NOT SHA3-256!)
    // Get the SHA3 (keccak-256) of the method id:
    // web3.sha3('multiply(uint256)')
    $rows[] = [$this->t('web3_sha3 (eth_getStorageAt(' . substr($storage_address, 0, 20) . '..., 0x0, latest))'), $storage ];


//    $mining = $results['eth_mining'] ? ((int) (hexdec($results['eth_hashrate']) / 1000) . ' KH/s') : t("No");
//    $rows[] = [$this->t("Mining"), $mining];

    // NON standard JsonRPC-API Methods below.

    $rows[] = [$this->t('<br /><b>PHP Ethereum controller API</b><br />provides additional methods. They are part of the <a href="https://github.com/digitaldonkey/ethereum-php-lib">Ethereum PHP library</a>, but not part of JsonRPC-API standard.'), ''];

    // Get Method signature.
    $rows[] = [$this->t("getMethodSignature('validateUserByHash(bytes32)"), $this->client->getMethodSignature('validateUserByHash(bytes32)')];


    $from = '0xaEC98826319EF42aAB9530A23306d5a9b113E23D';
    $to = '0x87fa200c97bc28573fa44bfa5f25f7270017bb08'; // Reposten Net.
    $gas = 0;
    $gasPrice = 0;
    $value = '1234567890123456'; //
    $data = '';

//
//    $message = new Ethereum_Message($to);
//    $message->setArgument(
//      $this->client->getMethodSignature('validateUserByHash(bytes32)'),
//      $this->client->strToHex('31080C38452FCF447999965502348333')
//    );
//    $rows[] = [$this->t('CALL'), $this->client->hexToStr($this->client->eth_call($message, 'latest'))];

    return [
      '#theme' => 'table',
      '#rows' => $rows,
    ];
  }

}


