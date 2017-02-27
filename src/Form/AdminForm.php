<?php
/**
* @file
* Contains \Drupal\ethereum\Form\AdminForm.
*/

namespace Drupal\ethereum\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\Controller\EthereumController;

/**
* Defines a form to configure maintenance settings for this site.
*/
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ethereum';
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
    $config = Drupal::config('ethereum.settings');

    $form['current_server'] = [
      '#type' => 'select',
      '#title' => $this->t("Select configuration"),
      '#required' => TRUE,
      '#description' => $this->t("Select a Ethereum Node to connect Drupal backend to."),
      '#options' => [
        'testnet' => $this->t("Infura Test Network"),
        'mainnet' => $this->t("Infura Main Network"),
        'custom' => $this->t("Custom network"),
      ],
      '#default_value' => $config->get('current_server'),
    ];

    $form['testnet'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Infura Test Network"),
      '#default_value' => $config->get('testnet'),
      '#attributes' => array('disabled' => TRUE),
      '#description' => $this->t('<a href="https://www.infura.io">Infura</a> provides access to Ethereum Nodes, so that you don\'t require to host you own.'),
    ];
    $form['mainnet'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Infura Main Network"),
      '#default_value' => $config->get('mainnet'),
      '#attributes' => array('disabled' => TRUE),
    ];
    $form['custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Custom Ethereum Node"),
      '#default_value' => $config->get('custom'),
      '#description' => t("To connect to a local geth or testrpc instance you might use: <br/>http://localhost:8545<br/>"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    try {
      $host = $values[$values['current_server']];
      $eth = new EthereumController($host);
      // Try to connect.
      $eth->client->eth_protocolVersion();
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName($values['current_server'], t("Unable to connect."));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('ethereum.settings');

    //$settings = ['scheme', 'hostname', 'port'];
    $settings = ['current_server', 'mainnet', 'testnet', 'custom'];
    $values = $form_state->getValues();
    foreach ($settings as $setting) {
      $config->set($setting, $values[$setting]);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
