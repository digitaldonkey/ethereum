<?php

namespace Drupal\ethereum\Controller;

use Drupal\Core\Controller\ControllerBase;
use Ethereum\Ethereum;
use Drupal\ethereum\EthereumServerInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Controller routines for Ethereum routes.
 */
class EthereumController extends ControllerBase {

  /**
   * The Ethereum JsonRPC client.
   *
   * @var \Ethereum\Ethereum
   */
  protected $web3;

  // @todo Doesn't seem to be propagated to the library anymore.
  private $debug = TRUE;

  /**
   * Constructs a new EthereumController.
   *
   * @param string|NULL $host
   *    Ethereum node url.
   *
   * @throws \Exception
   */
  public function __construct(string $host = null) {
    if (!$host) {
      $host = \Drupal::service('ethereum.manager')->getCurrentServer()->getUrl();
    }
    $this->web3 = new Ethereum($host);
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
    $html = $this->web3->debugHtml;
    $this->web3->debugHtml = '';
    if (!$clear && $this->debug) {
      // Remove last HR Tag.
      $html = strrev(implode('', explode(strrev('<hr />'), strrev($html), 2)));
      $this->messenger()->addMessage(Markup::create($html), 'warning');
    }
  }


  /**
   * Calls an operation on an ethereum_server entity and reloads the listing page.
   *
   * @param \Drupal\ethereum\EthereumServerInterface $ethereum_server
   *   The server being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   */
  public function performOperation(EthereumServerInterface $ethereum_server, $op, Request $request) {
    // Perform the operation.
    $ethereum_server->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityTypeManager()->getListBuilder('ethereum_server')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#ethereum-server-list', $list));
      return $response;
    }

    // Otherwise, redirect back to the network settings page.
    return $this->redirect('ethereum.settings');
  }

}
