<?php

namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum_smartcontract\Entity\SmartContract;
use Ethereum\EthereumStatic;
use Drupal\Core\Url;

trait SmartContractFormTrait {

  /**
   * Render the form directly from a Entity.
   *
   * @param array $form
   * @param \Drupal\ethereum_smartcontract\Entity\SmartContract $contract
   *
   * @return array
   */
  public function formFromEntity(array $form, SmartContract $contract) {
    global $base_url;

    $form['#attached']['library'][] = 'ethereum_smartcontract/smartcontract-entity-form-css';

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
      '#description' => $this->t('If a contract is enabled it\'s ABI  will be loaded as javascript setting in <code>drupalSettings.ethereum_smartcontract.contracts["id" => {...}]</code>'),
      '#default_value' => $contract->status(),
    );

    // Updating imported Contracts.
    if ($contract->isImported() && !$contract->isNew()) {

      $form['imported'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Imported contract'),
      ];
      $form['imported']['info'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Imported contracts are not editable, but you may reload from disk.') . '</p>',
      ];

      $form['imported']['update'] = array(
        '#type' => 'link',
        '#title' => $this->t('Update from disk'),
        '#attributes' => array(
          'class' => array('button button--primary'),
        ),
        '#weight' => 0,
        '#url' => Url::fromRoute('entity.smartcontract.import_form_update', ['smartcontract' => $contract->id()]),
      );
      $form['imported']['filename'] = [
        '#type' => 'markup',
        '#markup' =>'<p>Source file <small><code>' . $contract->getImportedFile() . '</code></small></p>',
      ];

    }

    $form['contract_src'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('contract_src'),
      '#default_value' => $contract->getSrc(),
      '#description' => $this->t("Solidity smart contract source code. Readonly for imported contracts."),
      '#readonly' => $contract->isImported(),
      '#required' => TRUE,
      '#rows' => 20,
      '#attributes' => ['readonly' => $contract->isImported()],
  );

    $form['abi'] = array(
      '#type' => 'details',
      '#title' => t('Smart contract ABI'),
      '#description' => $this->t("Smart contract ABI is compiled by Javascript using solc compiler. It is created from the contract_src field and updated on every submit. Imported contracts use the ABI definition from Truffle compiler."),
      '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );

    if ($contract->isImported()) {
      $form['abi']['contract_js'] = [
        '#type' => 'markup',
        '#markup' => self::abiToTable($contract->getAbi()),
      ];
    }
    else {
      $form['abi']['contract_js'] = [
        '#type' => 'markup',
        '#markup' => '<div id="abi"><img src="' . $base_url .'/core/misc/throbber-active.gif" alt="loading..."/></div>',
      ];

      $form['abi']['contract_compiled_json'] = array(
        '#type' => 'hidden',
        '#default_value' => '',
      );
      $form['#attached']['library'][] = 'ethereum_smartcontract/smartcontract-entity-form';
    }


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
    foreach (\Drupal::service('ethereum.manager')->getAllNetworks() as $net) {
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
        '#attributes' => ['readonly' => $contract->isImported()],
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->entity->save()) {
      \Drupal::messenger()->addStatus($this->t('Saved the %label SmartContract.', array(
        '%label' => $this->entity->label(),
      )));
    }
    else {
      \Drupal::messenger()->addStatus($this->t('The %label SmartContract was not saved.', array(
        '%label' => $this->entity->label(),
      )));
    }
    $form_state->setRedirect('entity.smartcontract.collection');
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    $contract_src = trim($form_state->getValue('contract_src'));
    if (!$contract_src) {
      $form_state->setErrorByName('contract_src', $this->t('Contract source code is required.'));
    }

    if (!$this->entity->isImported()) {
      // solc compiled source code we use it for ABI generation,
      // but don't save it anymore.
      $contract_compiled = trim($form_state->getValue('contract_compiled_json'));
      if (!$contract_compiled) {
        $form_state->setErrorByName('contract_compiled_json', $this->t('Contract compiled ABI is required. Your browser must have javascript enabled to use this form.'));
      }
      $form_state->setValue('abi', $this->parseAbi($contract_compiled));
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

    // Un-publish if no networks are set.
    if(!count($networks) && $form_state->getValue('status')) {
      $form_state->setErrorByName('status', $this->t('Contract can not be enabled without any networks Address set.'));
    }
    $this->entity = $this->buildEntity($form, $form_state);
  }


  /**
   * Check whether an SmartContract config entity exists.
   *
   * @param $id
   *
   * @return bool
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('smartcontract')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }


  /**
   * Ugly Helper which renders ABI into a HTML table.
   *
   * @param $abi
   * @return string
  */
  protected static function abiToTable($abi) {
    $table = "";
    if ($abi) {
      $table = '<table><tbody>' .
        '<tr><th>function</th><th>return type</th><th>type</th><th>constant</th><th>payable</th><th>stateMutability</th></tr>';
      foreach ($abi as $item) {
        $signature = self::getSignature($item);
        $returnType = isset($item->outputs[0]) ? $item->outputs[0]->type : 'void';
        $isConstant = (isset($item->constant) && $item->constant) ? 'true' : 'false';
        $isPayable = (isset($item->payable) && $item->payable) ? 'true' : 'false';
        $stateMutability = (isset($item->stateMutability) && $item->stateMutability) ? 'true' : 'false';
        $table .= '<tr><td>' . $signature . '</td><td>' . $returnType . '</td>';
        $table .= '<td>' . $item->type . '</td><td>' . $isConstant . '</td>';
        $table .= '<td>' . $isPayable . '</td><td>' . $stateMutability . '</td></tr>';
      }
      $table .= '</tbody></table>';
    }
    return $table;
  }


  /**
   * @param $m
   *    Method as returned from self::getParamDefinition()
   * @return string
   *    Function signature. E.g: multiply(uint256).
   */
  protected static function getSignature(object $m)
  {
    $sign = isset($m->name) ? $m->name . '(' : 'constructor(';
    foreach ($m->inputs as $i => $item) {
      $sign .= $item->type;
      if ($i < count($m->inputs) - 1) {
        $sign .= ',';
      }
    }
    $sign .= ')';
    return $sign;
  }
}
