<?php
/**
* @file
* Contains \Drupal\ethereum\Form\AdminForm.
*/

namespace Drupal\ethereum_user_connector\Form;


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
    return 'ethereum_user_connector';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ethereum.user_connector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('ethereum.user_connector.settings');

//    $form['scheme'] = [
//      '#type' => 'select',
//      '#title' => t("Scheme"),
//      '#options' => [
//        'http' => 'HTTP',
//        'https' => 'HTTPS',
//      ],
//      '#default_value' => $config->get('scheme'),
//    ];

    // Todo: Type should be Ethereum address
    $form['user_connector_contract'] = [
      '#type' => 'textfield',
      '#title' => t("Login Contract Address"),
      '#default_value' => $config->get('user_connector_contract'),
    ];
//    $form['port'] = [
//      '#type' => 'number',
//      '#min' => 1,
//      '#max' => 65535,
//      '#step' => 1,
//      '#title' => t("Port"),
//      '#default_value' => $config->get('port'),
//    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    try {
      $client = new EthereumClient($values['hostname']);

      // TODO

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
    $config = \Drupal::configFactory()->getEditable('ethereum.settings');

    //$settings = ['scheme', 'hostname', 'port'];
    $settings = ['hostname'];
    $values = $form_state->getValues();
    foreach ($settings as $setting) {
      $config->set($setting, $values[$setting]);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
