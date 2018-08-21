<?php
/**
 * @file
 * Contains \Drupal\ethereum_smartcontract\Annotation\SmartContract.
 */

namespace Drupal\ethereum_smartcontract\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flavor item annotation object.
 *
 * Plugin Namespace: Plugin\ethereum_smartcontract\SmartContract
 *
 * @see \Drupal\ethereum_smartcontract\SmartContractLoader
 * @see plugin_api
 *
 * @Annotation
 */
class SmartContract extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
}
