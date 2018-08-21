<?php

namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
* Form handler for the SmartContract add and edit forms.
*/
class SmartContractImportThree extends SmartContractImportMultistepFormBase {

  use SmartContractFormTrait;
  use TruffleHelperTrait;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'ethereum_smartcontract_import_three';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('entity.smartcontract.import_form_two'),
    );

    $file = $this->getTruffleCompiledPath(
      $this->store->get('import_path'),
      $this->store->get('select_contract')
    );

    // Import from file.
    $contractEntity = $this->truffleToEntity($file);
    if (!$contractEntity) {
      \Drupal::messenger()->addMessage(
        $this->t('Could not find any Contract data in  in @file', ['@file' => $file]),
        'error'
      );
      return $form;
    }
    $this->entity = $contractEntity;
    return $this->formFromEntity($form, $contractEntity);
  }

  /**
   * @param $contract_compiled
   *
   * Here we deal with Truffle compiled ABI.
   * This differs from the browser compiled solc.
   *
   * @return array
   */
  private static function parseAbi($contract_compiled) {
    return json_encode((array) json_decode(json_decode($contract_compiled)->metadata)->output->abi);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->save($form, $form_state);
    $this->deleteStore();
  }

}
