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
    $activeServer = \Drupal::entityTypeManager()->getStorage('ethereum_server')->load($activeServerId);

    return [
      '#attached' => [
        'library' => [
          'ethereum_txsigner/txsigners',
        ],
        'drupalSettings' => [
          'ethereumNetwork' => [
            'networkId' => $activeServer->getNetworkId(),
            'label' => $activeServer->label()
          ]
        ]
      ],
      '#type' => 'markup',
      '#markup' => '<div id="web3status"></div>',
    ];
  }
}
