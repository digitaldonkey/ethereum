<?php

/**
 * @file
 * Contains \Drupal\ethereum\Controller\EthereumController.
 */

namespace Drupal\ethereum\Controller;

use Drupal\Core\Controller\ControllerBase;
use Ethereum\Client;

/**
 * Controller routines for Ethereum routes.
 */
class EthereumController extends ControllerBase {

  /**
   * Displays the ethereum status report.
   *
   * @return string
   *   The current status of the ethereum node.
   */
  public function status() {
    $config = \Drupal::config('ethereum.settings');
    $url = $config->get('scheme') . '://' . $config->get('hostname') . ':' . $config->get('port');
    $client = new Client($url);

    $commands = [
      'web3_clientVersion',
      'net_version',
      'net_peerCount',
      'net_listening',
      'eth_protocolVersion',
      'eth_mining',
      'eth_hashrate',
      'shh_version',
    ];

    $results = [];
    foreach ($commands as $command) {
      $results[$command] = $client->request($command);
    }

    $block = $client->request('eth_getBlockByNumber', ['latest', FALSE]);

    $rows[] = [t("Client version"), $results['web3_clientVersion']];
    $rows[] = [t("Network version"), $results['net_version']];
    $rows[] = [t("Protocol version"), $results['eth_protocolVersion']];
    $rows[] = [t("Whisper version"), isset($results['shh_version']) ? $results['shh_version'] : t("n/a")];
    $rows[] = [t("Peers"), hexdec($results['net_peerCount'])];
    $rows[] = [t("Listening"), $results['net_listening'] ? t("Yes") : t("No")];

    $mining = $results['eth_mining'] ? ((int) (hexdec($results['eth_hashrate']) / 1000) . ' KH/s') : t("No");

    $rows[] = [t("Mining"), $mining];
    $rows[] = [t("Latest block"), hexdec($block['number'])];
    $rows[] = [t("Latest block age"), \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - hexdec($block['timestamp']), 3)];

    return [
      '#theme' => 'table',
      '#rows' => $rows,
    ];
  }

}
