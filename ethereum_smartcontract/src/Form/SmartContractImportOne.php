<?php

namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the SmartContract add and edit forms.
 */
class SmartContractImportOne extends SmartContractImportMultistepFormBase {

  use TruffleHelperTrait;
  use SmartContractFormTrait;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'ethereum_smartcontract_import_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $contract = $this->entity;

    $defaultValue = $this->store->get('import_path');

    $form['import_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Import path'),
      '#maxlength' => 255,
      '#default_value' => $defaultValue ? $defaultValue : '',
      '#description' => $this->t("Path to a Truffle directory absolute or relative to Drupal Root. Must contain contracts and build/contracts directories."),
      '#required' => TRUE,
    ];

    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $importDir = trim(rtrim($form_state->getValue('import_path', "/")));

    if (!$this->validateTruffleDir($importDir)) {
      $form_state->setErrorByName('import_path', $this->t('A Truffle import directory must have "contracts" and a "build/contracts" subdirectories.'));
    }

    $contracts = $this->getContractsFromTruffle($importDir);
    if (count($contracts) <= 1) {
      $form_state->setErrorByName('import_path', $this->t('Could not find any Contract data in  in ' . $this->getTruffleDataDir($importDir)));
      return;
    }

    // Save to session.
    $this->store->set('import_path', $importDir);
    $this->store->set('contracts', $contracts);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.smartcontract.import_form_two');
  }

}
