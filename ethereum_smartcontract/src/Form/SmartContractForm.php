<?php
namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ethereum\Controller\EthereumController;


/**
* Form handler for the SmartContract add and edit forms.
*/
class SmartContractForm extends EntityForm {

  /**
  * Constructs an SmartContractForm object.
  *
  * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
  *   The entity query.
  */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
  * {@inheritdoc}
  */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
  * {@inheritdoc}
  */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $contract= $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $contract->label(),
      '#description' => $this->t("Label for smart contract ."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $contract->id(),
      '#machine_name' => array(
      'exists' => array($this, 'exist'),
    ),
      '#disabled' => !$contract->isNew(),
    );

    $form['contract_src'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('contract_src'),
      '#default_value' => $contract->contract_src,
      '#description' => $this->t("contract_src for smart contract ."),
      '#required' => TRUE,
    );

    $form['contract_js'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('contract_js'),
      '#maxlength' => 255,
      '#default_value' => $contract->contract_js,
      '#description' => $this->t("contract_js for smart contract ."),
      '#required' => TRUE,
    );

    // Deployment by network.
    // UI-Table with contract deploy address per network.
    $form['networks'] = array(
      '#type' => 'table',
      '#header' => [
        'Network ID',
        'Network',
        'Contract address',
      ],
    );
    foreach (EthereumController::getNetworks() as $i => $net) {
      $form['networks'][$i]['id'] = array(
        '#markup' => '<b>' . $net['id'] . '</b>',
      );
      $form['networks'][$i]['net'] = array(
        '#markup' => $net['label'] . '<br/>'.
        '<small>' . $net['description'] . '</small>',
      );
      $form['networks'][$i]['contract'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#title_display' => 'invisible',
      );
    }

    return $form;
  }


  // TODO VALIDATE.

  /**
   * {@inheritdoc}
   */
//  public function validateForm(array $form, FormStateInterface $form_state) {
//
//
//  }
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $contract_src = trim($form_state->getValue('contract_src'));

//    $active_server = Drupal::config('ethereum.settings')->get('current_server');
//    $val = $form_state->getValue($active_server);
//
//    try {
//      $eth = new EthereumController();
//
//      // Validate contract address.
//      $signature = '0x' . $this->config('ethereum_user_connector.settings')->get('contract_contractExists_call');
//      /**
//       * E.g:
//       * curl -X POST --data '{"jsonrpc":"2.0","method":"eth_call","params":[{"to":"0xaaaafb8dbb9f5c9d82f085e770f4ed65f3b3107c", "data":"0x06ae9483"},"latest"],"id":1}' localhost:8545
//       */
//      $message = new CallTransaction(new EthD20($val), NULL, NULL, NULL, NULL, new EthD($signature));
//      $result = $eth->client->eth_call($message, new EthBlockParam());
//      //
//      // Debug JsonRPC contract validation call.
//      // $eth->debug();
//      //
//      // Set expected data type.
//      $contract_exists = $result->convertTo('bool')->val();
//      if (!$contract_exists) {
//        $form_state->setErrorByName('contract_address', $this->t('Unable to verify that contract exists at address: @address'), array('@address' => $val));
//      }
//    }
//    catch (\Exception $exception) {
//      $msg = $this->t("Unable find contract in currently active network. Please validate contract address on the network selected in admin/config/ethereum/network.");
//      $msg .= 'Error: ' . $exception->getMessage();
//      $form_state->setErrorByName($active_server, $msg);
//    }

  }

  /**
  * {@inheritdoc}
  */
  public function save(array $form, FormStateInterface $form_state) {
    $contract = $this->entity;
    $status = $contract->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label SmartContract.', array(
        '%label' => $contract->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label SmartContract was not saved.', array(
        '%label' => $contract->label(),
      )));
    }
    $form_state->setRedirect('entity.smartcontract.collection');
  }

  /**
  * Helper function to check whether an SmartContract configuration entity exists.
  */
  public function exist($id) {
    $entity = $this->entityQuery->get('smartcontract')
    ->condition('id', $id)
    ->execute();
    return (bool) $entity;
  }

}
