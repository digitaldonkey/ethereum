<?php

namespace Drupal\ethereum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\Entity\EthereumServer;
use Drupal\ethereum\Controller\EthereumController;

/**
 * Defines a form to select one of the active Ethereum network servers.
 */
class EthereumStatusServerSelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ethereum_status_server_select_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ethereum.settings');

    $form['#title'] = $this->t('Check the status of an active network');

  // Verify current server.
    $server = EthereumServer::load($config->get('current_server'));
    $verify = $server->validateConnection();
    if ($verify['error']) {
      $this->messenger()->addError($verify['message']);
    }

    $enabled_servers = EthereumController::getServerOptionsArray(TRUE);
    $form['server'] = [
      '#type' => 'select',
      '#title' => $this->t('Reporting on'),
      '#required' => TRUE,
      '#description' => $this->t('Choose a server to report on. Only enabled servers are listed.'),
      '#options' => $enabled_servers,
      '#default_value' => $config->get('current_server'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
