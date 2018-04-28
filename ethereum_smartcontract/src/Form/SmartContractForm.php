<?php
namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ethereum\Controller\EthereumController;
use Ethereum\EthereumStatic;


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
    global $base_url;

    $form = parent::form($form, $form_state);

    $contract= $this->entity;

    $form['#attached']['library'][] = 'ethereum_smartcontract/smartcontract-entity-form';

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

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable this contract'),
      '#description' => $this->t('If a contract is enabled it\'s ABI  will be loaded as javascript setting in <code>drupalSettings.ethereum.contracts["id" => {...}]</code>'),
      '#default_value' => $this->entity->status(),
    );

    $form['contract_src'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('contract_src'),
      '#default_value' => $contract->getSrc(),
      '#description' => $this->t("contract_src for smart contract ."),
      '#required' => TRUE,
      '#rows' => 20,
    );

    $form['abi'] = array(
        '#type' => 'details',
        '#title' => t('Smart contract ABI'),
        '#description' => $this->t("Smart contract ABI is currently compiled by Javascript using solc compiler. It is created from the contract_src field and updated on every submit."),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );

    $form['abi']['contract_js'] = [
        '#type' => 'markup',
        '#markup' => '<div id="abi"><img src="' . $base_url .'/core/misc/throbber-active.gif" alt="loading..."/></div>',
    ];

    $form['abi']['contract_compiled_json'] = array(
        '#type' => 'hidden',
        '#default_value' => '',
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
    foreach (EthereumController::getNetworks() as $net) {
      $form['networks'][$net['id']]['id'] = array(
        '#markup' => '<b>' . $net['id'] . '</b>',
      );
      $form['networks'][$net['id']]['net'] = array(
        '#markup' => $net['label'] . '<br/>'.
        '<small>' . $net['description'] . '</small>',
      );
      $val = isset($contract->getNetworks()[$net['id']]) ? $contract->getNetworks()[$net['id']] : '';
      $form['networks'][$net['id']]['contract'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#title_display' => 'invisible',
        '#default_value' => $val,
      );
    }
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

      $contract_src = trim($form_state->getValue('contract_src'));
      if (!$contract_src) {
        $form_state->setErrorByName('contract_src', $this->t('Contract source code is required.'));
      }

      $contract_src = trim($form_state->getValue('contract_compiled_json'));
      if (!$contract_src) {
          $form_state->setErrorByName('contract_compiled_json', $this->t('Contract compiled ABI is required. Your browser must have javascript enabled to use this form.'));
      }

      $networks = [];
      foreach ($form_state->getValue('networks') as $netId => $net) {
          $val = trim($net['contract']);
          // Only validate if value is set
          if ($val) {
              // @todo We might add a network based validation which checks if
              //    the contract is actually deployed at this address.
              if (!EthereumStatic::isValidAddress($val)) {
                  $form_state->setError($form['networks'][$netId],
                    $this->t('@address is not a formally valid Ethereum contract address.',
                      ['@address' => $val]
                    )
                  );
              }
              else {
                  $networks[$netId] = $val;
              }
          }
      }
      $form_state->setValueForElement($form['networks'], $networks);

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
