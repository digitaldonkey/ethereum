<?php

/**
 * @file
 * Contains \Drupal\ethereum\Controller\EthereumController.
 */

namespace Drupal\ethereum\Controller;

//use Drupal;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Controller\ControllerBase;
use Ethereum\Ethereum;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\EthB;
use Ethereum\DataType\EthS;
use Drupal\Core\Render\Markup;
use Drupal\ethereum\Entity\EthereumServer;

/**
 * Controller routines for Ethereum routes.
 */
class EthereumController extends ControllerBase {

  private $config;
  public $client;
  private $debug = TRUE;

  public function __construct($host = FALSE) {

    // @todo Use url or ethereum_server entity to construct?

    if (!$host) {
      $current_server_id = \Drupal::config('ethereum.settings')->get('current_server');
      $current_server = \Drupal::config('ethereum.ethereum_server.' . $current_server_id);
      $host = $current_server->get('url');
    }
    $this->client = new Ethereum($host);
  }

  /**
   * Returns Ethereum Networks.
   *
   * @param $detailed bool
   *  By default we will return a Array keyed by Network ID (short version).
   *   ID => Name - Description.
   *  If $detailed = TRUE
   *   Long version is [] = [id, label, description]
   *
   * @return array
   *   Array of Ethereum Networks containing ID, Name, Description.
   */
  public static function getNetworksAsOptions($detailed = FALSE) {
    $networks = [];
      foreach (self::getNetworks() as $k => $item) {
        $networks[$item['id']] = $item['label'] . ' - ' . $item['description'];
      }
      return $networks;
  }

  /**
   * Returns Ethereum Networks.
   *
   * @param $detailed bool
   *  By default we will return a Array keyed by Network ID (short version).
   *   ID => Name - Description.
   *  If $detailed = TRUE
   *   Long version is [] = [id, label, description]
   *
   * @return array
   *   Array of Ethereum Networks containing ID, Name, Description.
   */
  public static function getNetworks() {
    $networks = \Drupal::config('ethereum.ethereum_networks')->getOriginal();
    // Filter out non-numeric keys.
    $networks = array_filter($networks, function ($k){ return is_int($k);}, ARRAY_FILTER_USE_KEY);
    return $networks;
  }

  /**
   * Ethereum Servers as options.
   *
   * @param $filter_enabled bool
   *   return only servers with is_enabled = TRUE.
   *
   * @return array
   *   Array [] = machine_name => Label
  */
  public static function getServerOptionsArray($filter_enabled = FALSE){
    $servers = self::getServers($filter_enabled);
    return array_map(function($k) { return $k->label; }, $servers);
  }

  /**
   * Returns Ethereum Servers.
   * @param $filter_enabled bool
   *   Restrict to is_enabled=TRUE.
   *
   * @return  array [EthereumServer]
   */
  public static function getServers($filter_enabled = FALSE) {
    $storage = \Drupal::entityTypeManager()->getStorage('ethereum_server');
    if ($filter_enabled) {
      $servers = $storage->loadByProperties(['is_enabled' => TRUE]);
    }
    else {
      $servers = $storage->loadMultiple();
    }
    return $servers;
  }

  /**
   * getServerById().
   *
   *   ID => Name
   * @param $id string
   *   Server config id.
   *
   * @return EthereumServer
   */
  public static function getServerById($id) {
    $storage = \Drupal::entityTypeManager()->getStorage('ethereum_server');
    return array_shift($storage->loadByProperties(['id' => $id]));
  }


  /**
   * validateServerConnection().
   *
   *   ID => Name
   * @param $server EthereumServer
   *   Server config id.
   *
   * @return array
   *    array[
   *      error => bool,
   *      message => TranslatableMarkup
   *    ]
   */
    public static function validateServerConnection($server) {
      $return = ['error' => FALSE, 'message'=>''];
      try {
        $eth = new EthereumController($server->url);

        // Try to connect.
        $networkVersion = $eth->client->net_version()->val();
        if (!is_string($networkVersion)) {
          throw new \Exception('eth_protocolVersion return is not valid.');
        }

        if ($server->network_id !== '*' && $networkVersion !== $server->network_id) {
          throw new \Exception('Network ID does not match.');
        }
      }
      catch (\Exception $exception) {
        $return = [
          'message' => t(
            "Unable to connect to Server <b>"
            . $server->label  . "</b><br />"
            . $exception->getMessage() )
        ];
        $return['error'] = TRUE;
      }
      return $return;
    }

  /**
   * Outputs function call logging as drupal message.
   *
   * This will output logs of all calls if $this->debug = TRUE.
   * Debug log will be emptied after call.
   *
   * @param bool $clear
   *   If empty is set we will empty the debug log.
   *   You may use to debug a single call.
   */
  public function debug($clear = FALSE) {
    $html = $this->client->debugHtml;
    $this->client->debugHtml = '';
    if (!$clear && $this->debug) {
      // Remove last HR Tag.
      $html = strrev(implode('', explode(strrev('<hr />'), strrev($html), 2)));
      drupal_set_message(Markup::create($html), 'warning');
    }
  }

  /**
   * Displays the ethereum status report page.
   *
   * This page provides a overview about Ethereum functions and usage.
   *
   * @return array
   *   Render array. Table with current status of the ethereum node.
   */
  public function status() {

    $rows[] = [$this->t('<b>JsonRPC standard Methods</b>'), $this->t('Read more about <a href="https://github.com/ethereum/wiki/wiki/JSON-RPC">Ethereum JsonRPC-API</a> implementation.')];
    $rows[] = [$this->t("Client version (web3_clientVersion)"), $this->client->web3_clientVersion()->val()];
    $rows[] = [$this->t("Listening (net_listening)"), $this->client->net_listening()->val() ? '✔' : '✘'];
    $rows[] = [$this->t("Peers (net_peerCount)"), $this->client->net_peerCount()->val()];
    $rows[] = [$this->t("Protocol version (eth_protocolVersion)"), $this->client->eth_protocolVersion()->val()];
    $rows[] = [$this->t("Network version (net_version)"), $this->client->net_version()->val()];
    $rows[] = [$this->t("Syncing (eth_syncing)"), $this->client->eth_syncing()->val() ? '✔' : '✘'];

    // Mining and Hashrate.
    $rows[] = [$this->t("Mining (eth_mining)"), $this->client->eth_mining()->val() ? '✔' : '✘'];

    $hash_rate = $this->client->eth_hashrate();
    $mining = is_a($hash_rate, 'EthQ') ? ((int) ($hash_rate->val() / 1000) . ' KH/s') : '✘';
    $rows[] = [$this->t("Mining hashrate (eth_hashrate)"), $mining];

    // Gas price is returned in WEI. See: http://ether.fund/tool/converter.
    $price = $this->client->eth_gasPrice()->val();
    $price = $price . 'wei ( ≡ ' . number_format(($price / 1000000000000000000), 8, '.', '') . ' Ether)';
    $rows[] = [$this->t("Current price per gas in wei (eth_gasPrice)"), $price];

    // Blocks.
    $rows[] = [$this->t("<b>Block info</b>"), ''];
    $block_latest = $this->client->eth_getBlockByNumber(new EthBlockParam('latest'), new EthB(FALSE));
    $rows[] = [
      $this->t("Latest block age"),
      \Drupal::service('date.formatter')->format($block_latest->getProperty('timestamp'), 'html_datetime'),
    ];

    // Testing_only.

    $block_earliest = $this->client->eth_getBlockByNumber(new EthBlockParam(0), new EthB(FALSE));
    $rows[] = [
      $this->t("Age of block number '1' <br/><small>The 'earliest' block has no timestamp on many networks.</small>"),
      \Drupal::service('date.formatter')->format($block_earliest->getProperty('timestamp'), 'html_datetime'),
    ];
    $rows[] = [
      $this->t("Client first (eth_getBlockByNumber('earliest'))"),
      Markup::create('<div style="max-width: 800px; max-height: 120px; overflow: scroll">' . $this->client->debug('', $block_earliest) . '</div>'),
    ];

    // Second param will return TX hashes instead of full TX.
    $block_latest = $this->client->eth_getBlockByNumber(new EthBlockParam('latest'), new EthB(FALSE));
    $rows[] = [
      $this->t("Client first (eth_getBlockByNumber('latest'))"),
      Markup::create('<div style="max-width: 800px; max-height: 120px; overflow: scroll">' . $this->client->debug('', $block_latest) . '</div>'),
    ];
    $rows[] = [
      $this->t("Uncles of latest block"),
      Markup::create('<div style="max-width: 800px; max-height: 120px; overflow: scroll">' . $this->client->debug('', $block_latest->getProperty('uncles')) . '</div>'),
    ];
    $high_block = $this->client->eth_getBlockByNumber(new EthBlockParam(999999999), new EthB(FALSE));
    $rows[] = [
      $this->t("Get hash of a high block number<br /><small>Might be empty</small>"),
      $high_block->getProperty('hash'),
    ];


    // Accounts.
    $rows[] = [$this->t("<b>Accounts info</b>"), ''];
    $coin_base = $this->client->eth_coinbase()->hexVal();
    if ($coin_base === '0x0000000000000000000000000000000000000000') {
      $coin_base = 'No coinbase available at this network node.';
    }
    $rows[] = [$this->t("Coinbase (eth_coinbase)"), $coin_base];
    $address = array();
    foreach ($this->client->eth_accounts() as $addr) {
      $address[] = $addr->hexVal();
    }
    $rows[] = [$this->t("Accounts (eth_accounts)"), implode(', ', $address)];

    // More.
    $rows[] = [
      $this->t("web3_sha3('Hello World')"),
      $this->client->web3_sha3(new EthS('Hello World'))->hexVal(),
    ];

    // NON standard JsonRPC-API Methods below.
    $rows[] = [$this->t('<b>Non standard methods</b>'), $this->t('PHP Ethereum controller API provides additional methods. They are part of the <a href="https://github.com/digitaldonkey/ethereum-php">Ethereum PHP library</a>, but not part of JsonRPC-API standard.')];
    $rows[] = [$this->t("getMethodSignature('validateUserByHash(bytes32)')"), $this->client->getMethodSignature('validateUserByHash(bytes32)')];

    // Debug output for all calls since last call of
    // $this->debug() or $this->debug(TRUE).
    // $this->debug();
    //
    return [
      '#theme' => 'table',
      '#rows' => $rows,
    ];
  }

}
