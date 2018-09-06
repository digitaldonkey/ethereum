<?php

// TxSignerBlock

/**
 * @file
 * Contains \Drupal\ethereum_txsigner\Plugin\Block.
 */

namespace Drupal\ethereum_txsigner\Plugin\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "txsigner_block",
 *   admin_label = @Translation("Ethereum transaction signer Block"),
 *   category = @Translation("Ethereum")
 * )
 */
class TxSignerBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

    $activeServerId = \Drupal::config('ethereum.settings')->get('current_server');
    /* @var $activeServer \Drupal\ethereum\Entity\EthereumServer */
    $activeServer = \Drupal::entityTypeManager()->getStorage('ethereum_server')->load($activeServerId);

    if (\Drupal::service('module_handler')->moduleExists('ethereum_smartcontract')){
      $activeLibs = [
        'ethereum_txsigner/txsigners',
        // Required ot ethereum_smartcontract_library_info_build will not fire.
        'ethereum_smartcontract/contracts',
      ];
    }
    else {
      $activeLibs = [
        'ethereum_txsigner/txsigners',
      ];
    }

    return [
      '#type' => 'markup',
      '#markup' => '<div id="web3status"></div>',
      '#cache' => [
        'tags' => [
          'config:ethereum.settings',
        ],
      ],
      '#attached' => [
        'library' => $activeLibs,
        'drupalSettings' => [
          'ethereum_txsigner' => $activeServer->getJsConfig(),
        ]
      ]
    ];
  }
}
