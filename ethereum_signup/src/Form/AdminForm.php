<?php
/**
* @file
* Contains \Drupal\ethereum\Form\AdminForm.
*/

namespace Drupal\ethereum_signup\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;
use Ethereum\EthD;
use Ethereum\EthD20;
use Drupal\Core\Url;
use Ethereum\CallTransaction;
use Drupal\user\RoleInterface;
/**
* Defines a form to configure maintenance settings for this site.
*/
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ethereum_signup_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ethereum_signup.settings'];
  }




  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('ethereum_signup.settings');

    // Email option.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Signup settings'),
      '#open' => TRUE,
    ];

    $form['settings']['require_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require email to sign up.'),
      '#default_value' => $config->get('require_mail'),
      // TODO This should be tackled in input validation.
      '#description' => $this->t('Not requiring a email?<br />Make sure "Require email verification when a visitor creates an account" and "Notify user when account is activated" are not checked in account settings.'),
    ];

    $form['settings']['user_ethereum_register'] = [
      '#type' => 'radios',
      '#title' => $this->t('Who can register accounts using Etherum signup?'),
      '#default_value' => $this->getRegisterValue($config->get('require_admin_confirm'), $config->get('require_mail_confirm')),
      '#options' => [
        'visitors' => $this->t('Visitors can directly log in.'),
        'admin_confirm' => $this->t('Require Administrator to approve accounts.'),
        'email_confirm' => $this->t('Require email confirmation before activating accounts.'),
      ]
    ];

    $form['settings']['ui_visible_without_web3'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Ethereum for everybody'),
      '#default_value' => $config->get('ui_visible_without_web3'),
      '#description' => $this->t('The user needs a web3 provider enabled in the browser in order to sign transactions. Un-checking will hide the "signup with Ethereum" option if the users browser does not support web3. You may use Metamask browser plugin (currently available for Chrome and Firefox) or Mist browser developed by Ethereum foundation.'),
    ];

    $form['settings']['login_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect after login'),
      '#default_value' => $config->get('login_redirect'),
      '#description' => $this->t('Path relative to drupal web root. The value "user" will redirect to account home. Only users logging in with Ethereum will be redirected.'),
      '#required' => TRUE,
    ];

    // Login role option.
    $form['role'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration Role'),
      '#open' => TRUE,
    ];
    // Do not allow users to set the anonymous role.
    $roles = user_role_names(TRUE);

    $form['role']['register_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration Role'),
      '#empty_value' => '',
      '#default_value' => $config->get('register_role'),
      '#options' => $roles,
      '#description' => $this->t('This role will be automatically assigned to Users authenticated with Ethereum Signup module. "Authenticated user" will always be set. Change to add an additional role.'),
    ];


    // Register option.
    $form['register'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration text'),
      '#open' => TRUE,
    ];
    $form['register']['register_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Register link text'),
      '#default_value' => $config->get('register_link_text'),
      '#description' => $this->t('Text for registration link.'),
      '#required' => TRUE,
    ];
    $form['register']['register_terms_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Terms text'),
      '#default_value' => $config->get('register_terms_text'),
      '#description' => $this->t('This text the user will be presented to digitally sign on registration.<br />Disabling this option will hide the log-in links unless window.web3 is available.'),
      '#required' => TRUE,
    ];


    // Login option.
    $form['login'] = [
      '#type' => 'details',
      '#title' => $this->t('Login text'),
      '#open' => TRUE,
    ];

    $form['login']['login_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login link text'),
      '#default_value' => $config->get('login_link_text'),
      '#description' => $this->t('Text for registration link.'),
      '#required' => TRUE,
    ];
    $form['login']['login_welcome_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login text'),
      '#default_value' => $config->get('login_welcome_text'),
      '#description' => $this->t('This text the user will be presented to digitally sign on login. It should contain a "#date" placeholder.</br>The date will be added to have a unique message (hash) on each login request.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = Drupal::configFactory()->getEditable('ethereum_signup.settings');

    // Process select value into ....
    $this->setRegisterValue($form_state);

    // White listing variables
    $settings = [
      'require_mail',
      'require_mail_confirm',
      'require_admin_confirm',
      'login_redirect',
      'ui_visible_without_web3',
      'register_role',
      'register_link_text',
      'register_terms_text',
      'login_link_text',
      'login_welcome_text',
    ];
    $values = $form_state->getValues();
    foreach ($settings as $setting) {
      $config->set($setting, $values[$setting]);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * Pre processing submission.
   *
   * Select user_ethereum_register is mapped to require_mail_confirm and
   * require_admin_confirm.
   * Additionally require_mail is set if require_mail_confirm=TRUE.
   *
   * @param $form_state
   *    FormStateInterface.
   */
  private function setRegisterValue(FormStateInterface $form_state) {
    $registration_type = $form_state->getValue('user_ethereum_register');
    $form_state->unsetValue('user_ethereum_register');
    switch ($registration_type) {
      case 'visitors':
        $form_state->setValue('require_mail_confirm', FALSE);
        $form_state->setValue('require_admin_confirm', FALSE);
        break;
      case 'email_confirm':
        $form_state->setValue('require_mail_confirm', TRUE);
        $form_state->setValue('require_admin_confirm', FALSE);
        break;
      default:
        $form_state->setValue('require_mail_confirm', FALSE);
        $form_state->setValue('require_admin_confirm', TRUE);
        break;
    }

    // Make sure email required is set when we need to send mails in registration process.
    if ($registration_type !== 'visitors') {
      $form_state->setValue('require_mail', TRUE);
      drupal_set_message($this->t('Enabled "email required" because admin or email confirmation was set.'), 'warning');
    }

  }

  /**
   * Register options select.
   *
   * Create default value for select from config
   *
   * @param bool $admin_approval
   *  Config setting require_admin_confirm.
   * @param bool $email_confirm
   *  Config setting require_admin_confirm.
   *
   * @return string
   *  Default value for select.
   */
  private function getRegisterValue($admin_approval, $email_confirm) {
    if ($admin_approval) {
      return 'admin_confirm';
    }
    if ($email_confirm) {
      return 'email_confirm';
    }
    return 'visitors';
  }

}
