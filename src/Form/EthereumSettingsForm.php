<?php

namespace Drupal\ethereum\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\Controller\EthereumController;
use Drupal\ethereum\Entity\EthereumServer;

/**
* Defines a form to configure Ethereum connection settings for this site.
*/
class EthereumSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ethereum_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ethereum.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ethereum.settings');

    // Verify current server.
    if (!$form_state->getUserInput()) {
      $server = EthereumServer::load($config->get('current_server'));
      $verify = $server->validateConnection();
      if ($verify['error']) {
        $this->messenger()->addError($verify['message']);
      }
    }

    $form['servers'] = \Drupal::entityTypeManager()
      ->getListBuilder('ethereum_server')
      ->render();

    $form['default_network'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ethereum Default Network'),
    ];

    $enabled_servers = EthereumController::getServerOptionsArray(TRUE);
    $form['default_network']['current_server'] = [
      '#type' => 'select',
      '#title' => $this->t('Backend server'),
      '#required' => TRUE,
      '#description' => $this->t('Select a default Ethereum Node to connect Drupal backend to. Only enabled servers can be selected.'),
      '#options' => $enabled_servers,
      '#default_value' => $config->get('current_server'),
    ];

    $form['default_network']['frontend_server'] = [
      '#type' => 'select',
      '#title' => $this->t('Frontend server'),
      '#description' => $this->t('Select a default Ethereum Node to connect Drupal frontend to. Only enabled servers can be selected and it has to be on the same network as the backend server.'),
      '#empty_option' => $this->t('Same as backend'),
      '#options' => $enabled_servers,
      '#default_value' => $config->get('frontend_server'),
    ];

    $form['default_network']['infura_note'] = [
      '#type' => 'markup',
      '#markup' => '<p><a href="https://infura.io/">Infura</a> is a webservice which provides access to Ethereum.<br />Infura requires a token for access. The "drupal" token only to get started. It is not intended for production use and may be revoked on extensive usage.<br /><b>Please <a href="https://infura.io/signup">register</a> your own free Infura token for your own application or run your own Ethereum node.</b><br /></p>',
    ];

    $form['#attached']['library'][] = 'ethereum/ethereum-admin-form';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\ethereum\EthereumServerInterface[] $servers */
    $servers = EthereumServer::loadMultiple();

    // Validate the backend server.
    $backend_server_id = $form_state->getValue('current_server');
    if (!$servers[$backend_server_id]->status()) {
      $form_state->setError($form['default_network']['current_server'], $this->t('%label is not enabled.', ['%label' => $servers[$backend_server_id]->label()]));
    }

    $verify = $servers[$backend_server_id]->validateConnection();
    if ($verify['error']) {
      $form_state->setError($form['default_network']['current_server'], $verify['message']);
    }

    // Validate the frontend server.
    if ($frontend_server_id = $form_state->getValue('frontend_server')) {
      if (!$servers[$frontend_server_id]->status()) {
        $form_state->setError($form['default_network']['frontend_server'], $this->t('%label is not enabled.', ['%label' => $servers[$frontend_server_id]->label()]));
      }

      $verify = $servers[$frontend_server_id]->validateConnection();
      if ($verify['error']) {
        $form_state->setError($form['default_network']['frontend_server'], $verify['message']);
      }

      if ($servers[$frontend_server_id]->getNetworkId() != $servers[$backend_server_id]->getNetworkId()) {
        $form_state->setError($form['default_network']['frontend_server'], $this->t('The backend and frontend servers must be on the same Ethereum network.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ethereum.settings')
      ->set('current_server', $values['current_server'])
      ->set('frontend_server', $values['frontend_server'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
