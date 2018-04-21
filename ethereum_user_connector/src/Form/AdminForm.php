<?php

namespace Drupal\ethereum_user_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\Controller\EthereumController;
use Ethereum\DataType\CallTransaction;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\EthD;
use Ethereum\DataType\EthD20;
use Ethereum\Ethereum;
use Masterminds\HTML5\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;

/**
* Defines a form to configure maintenance settings for this site.
*/
class AdminForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The Ethereum JsonRPC client.
   *
   * @var \Ethereum\Ethereum
   */
  protected $web3;

  /**
   * Constructs a new AdminForm.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Ethereum $web3) {
    parent::__construct($config_factory);
    $this->web3 = $web3;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ethereum.client')
    );
  }

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

    $defaultServer = EthereumController::getDefaultServer();

    $contract = \Drupal::entityTypeManager()
      ->getStorage('smartcontract')->load('register_drupal');

    $linkCollection = Link::createFromRoute(
      'Configuration Entity',
      'entity.smartcontract.collection'
    )->getUrl()->toString();

    $linkContract = $contract->toUrl()->toString();

    $form['html'] = [
      '#markup' => $this->t(
        '<p>Smart contracts are now <a href="@linkCollection">SmartContract Configuration Entities</a>.<br/> The registry contract <strong>register_drupal</strong> can be <a href="@linkContract">edited here</a>.</p>',
        [
          '@linkContract' => $linkContract,
          '@linkCollection' => $linkCollection
        ]
      ),
    ];

    $form['deployed'] = $contract->getDeployedAsTable();

    $form['actions'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Validate'),
    );

    $form['actions']['info'] = [
      '#markup' => '<p>' . $this->t('Validate that Contract exists on current Drupal default network <strong>@label (@id)</strong>', ['@label' =>  $defaultServer->label(), '@id' => $defaultServer->getNetworkId()]) . '</p>',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Validate on default server.'),
      '#button_type' => 'primary',
    ];

    return $form; // parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $contract = EthereumUserConnectorController::getContractEntity();
    $address = $contract->getCurrentNetworkAddress();

    try {
      // Callable instance od the deployed contract.
      $register_drupal = $contract->getCallable();
      $contract_exists = $register_drupal->contractExists()->val();

      if ($contract_exists) {
        drupal_set_message($this->t(
            'Sucessfully called contractExtists at: @address',
            array('@address' => $address))
        );
      }
      else {
        drupal_set_message($this->t(
          'Unable to verify that contract exists at address: @address',
          array('@address' => $address))
        );
      }
    }
    catch (\Exception $exception) {
      $msg = $this->t("Unable find contract in currently active network. Please validate contract address on the network selected in admin/config/ethereum/network.");
      $msg .= 'Error: ' . $exception->getMessage();
      $form_state->setErrorByName('submit', $msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Require to implement submitForm or we will get a
    // "Settings saved" from parent, but there is nothing to save currently.
    //    parent::submitForm($form, $form_state);
  }

}
