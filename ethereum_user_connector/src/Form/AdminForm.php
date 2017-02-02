<?php
/**
* @file
* Contains \Drupal\ethereum\Form\AdminForm.
*/

namespace Drupal\ethereum_user_connector\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Ethereum\EthereumClient;

/**
* Defines a form to configure maintenance settings for this site.
*/
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ethereum_user_connector_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ethereum_user_connector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('ethereum_user_connector.settings');
    // Todo: Type should be Ethereum address
    // Todo: Add second field for Test-net.
    $form['contract_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Login Contract Address"),
      '#default_value' => $config->get('contract_address'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = Drupal::config('ethereum.settings');
    try {

      $client = new EthereumClient($config->get('hostname'));

      // TODO
      // Validate address
      // Get contract abi?
      $DET = $config->get('hostname');

//      $X = $client->net_listening();
//      $client->eth_protocolVersion();
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('', t("Unable to connect."));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('ethereum_user_connector.settings');

    // White listing variables
    $settings = ['contract_address'];
    $values = $form_state->getValues();
    foreach ($settings as $setting) {
      $config->set($setting, $values[$setting]);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
