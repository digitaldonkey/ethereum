<?php
/**
* @file
* Contains \Drupal\ethereum\Form\AdminForm.
*/

namespace Drupal\ethereum\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Url;
use Graze\GuzzleHttp\JsonRpc\Client as JsonRpcClient;

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
    $config = \Drupal::config('ethereum.settings');

    $form['scheme'] = [
      '#type' => 'select',
      '#title' => t("Scheme"),
      '#options' => [
        'http' => 'HTTP',
        'https' => 'HTTPS',
      ],
      '#default_value' => $config->get('scheme'),
    ];
    $form['hostname'] = [
      '#type' => 'textfield',
      '#title' => t("Hostname"),
      '#default_value' => $config->get('hostname'),
    ];
    $form['port'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 65535,
      '#step' => 1,
      '#title' => t("Port"),
      '#default_value' => $config->get('port'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $uri = new Url($values['scheme'], $values['hostname'], NULL, NULL, $values['port']);

    try {
      $client = JsonRpcClient::factory($uri);
      $client->send($client->request(1, 'web3_clientVersion', []))->getRpcResult();
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
    $settings = ['scheme', 'hostname', 'port'];
    $values = $form_state->getValues();
    foreach ($settings as $setting) {
      $config->set($setting, $values[$setting]);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
