<?php

namespace Drupal\ethereum_user_connector\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class EthereumUserConnector.
 *
 * @package Drupal\ethereum_user_connector\Controller
 */
class EthereumUserConnector extends ControllerBase {

  /**
   * Adminconfig.
   *
   * @return string
   *   Return Hello string.
   */
  public function AdminConfig() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: AdminConfig')
    ];
  }

}
