<?php

namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form handler for the SmartContract add and edit forms.
 */
class SmartContractImportTwo extends SmartContractImportMultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'ethereum_smartcontract_import_two';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $available_Contracts = $this->store->get('contracts');
    $defaultValue = $this->store->get('select_contract');

    $form['select_contract'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Contract to Import'),
      '#options' => $this->selectifyContracts($available_Contracts),
      '#default_value' => $defaultValue ? $defaultValue : '',
    ];

    $form['actions']['submit']['#value'] = $this->t('Next');
    $form['actions']['previous'] = [
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 0,
      '#url' => Url::fromRoute('entity.smartcontract.import_form_one'),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('select_contract');
    $available_Contracts = $this->store->get('contracts');
    if (!isset($available_Contracts[$selected])) {
      $form_state->setErrorByName('select_contract', $this->t('Contract not available. Please restart.'));
      return;
    }
    $this->store->set('select_contract', $selected);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.smartcontract.import_form_three');
  }

}
