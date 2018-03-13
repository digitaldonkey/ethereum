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

    // Verify current server.
    $server = EthereumController::getServerById($config->get('current_server'));
    $verify = EthereumController::validateServerConnection($server);
    if ($verify['error']) {
      drupal_set_message($verify['message'], 'error');
    }

    $form['servers'] = Drupal::entityTypeManager()
      ->getListBuilder('ethereum_server')
      ->render();

    $form['default_network'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ethereum Default Network'),
    ];

    $form['default_network']['current_server'] = [
      '#type' => 'select',
      '#title' => $this->t("Default Server"),
      '#required' => TRUE,
      '#description' => $this->t("Select a default Ethereum Node to connect Drupal backend to. Only enabled servers can be selected."),
      '#options' => EthereumController::getServerOptionsArray( TRUE),
      '#default_value' => $config->get('current_server'),
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
    $serverId = $form_state->getValues()['current_server'];

    $server = EthereumController::getServerById($serverId);

    if (!$server->get('is_enabled')) {
      $form_state->setError($form['current_server'],  $server->label() . t(' is not enabled.'));
    }

    $verify = EthereumController::validateServerConnection($server);
    if ($verify['error']) {
      $form_state->setError(
        $form['default_network']['current_server'],
        $verify['message']
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('ethereum.settings');
    $settings = ['current_server'];
    $values = $form_state->getValues();
    foreach ($settings as $setting) {
      $config->set($setting, $values[$setting]);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
