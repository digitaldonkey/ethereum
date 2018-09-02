<?php

namespace Drupal\ethereum_user_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\EthereumManagerInterface;
use Ethereum\Ethereum;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;
use Drupal\ethereum_smartcontract\SmartContractInterface;

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
   * The Ethereum manager service.
   *
   * @var \Drupal\ethereum\EthereumManagerInterface
   */
  protected $ethereumManager;

  /**
   * Constructs a new AdminForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Ethereum\Ethereum $web3
   * @param \Drupal\ethereum\EthereumManagerInterface $ethereum_manager
   *   The Ethereum manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Ethereum $web3, EthereumManagerInterface $ethereum_manager) {
    parent::__construct($config_factory);
    $this->web3 = $web3;
    $this->ethereumManager = $ethereum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ethereum.client'),
      $container->get('ethereum.manager')
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
    $defaultServer = $this->ethereumManager->getCurrentServer();

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

    $form['deployed'] = $this->getDeployedAsTable($contract);

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
        \Drupal::messenger()->addMessage($this->t(
            'Successfully called contractExtists at: @address',
            array('@address' => $address))
        );
      }
      else {
        \Drupal::messenger()->addMessage($this->t(
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


  /**
   * Contract Network Info Table.
   *
   * @param $contract SmartContractInterface
   *    Smart contract config entity.
   *
   * @return array
   *    Table render array.
   */
  protected function getDeployedAsTable(SmartContractInterface $contract) {

    $deployed = $contract->getDeployed();

    $formElement = array(
      '#type' => 'table',
      '#header' => [
        'Network ID',
        'Network',
        'Contract address',
        'Block Explorer',
      ],
    );
    foreach ($deployed as $id => $net) {
      $formElement[$id]['id'] = array(
        '#markup' => '<b>' . $net['id'] . '</b>',
      );
      $formElement[$id]['net'] = array(
        '#markup' => $net['label'] . '<br/>'.
          '<small>' . $net['description'] . '</small>',
      );
      $formElement[$id]['contract'] = array(
        '#markup' => $net['contract_address'],
      );
      // Provide link to contract.
      if (isset($net['link_to_address']) && $net['link_to_address']) {
        $addr = str_replace('@address', $net['contract_address'] ,$net['link_to_address']);
        $url = Url::fromUri($addr);
        $linkText = substr($addr, 0, 47) . '...';
        $link = Link::fromTextAndUrl($linkText, $url);
        $formElement[$id]['explorer'] = array(
          '#markup' => $link->toString(),
        );
      }
    }
    return $formElement;
  }
}
