<?php

namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

/**
* Form handler for the SmartContract add and edit forms.
*/
class SmartContractImportUpdate extends SmartContractImportMultistepFormBase {

  use SmartContractFormTrait;
  use TruffleHelperTrait;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'ethereum_smartcontract_import_update';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* @var \Drupal\ethereum_smartContract\Entity\SmartContract $contractEntity */
    $contractEntity = $this->entity;

    $form = parent::buildForm($form, $form_state);
    $file = $contractEntity->getImportedFile();
    $contractEntity = $this->truffleToEntity($contractEntity->getImportedFile(), $contractEntity);
    if (!$contractEntity) {
      \Drupal::messenger()->addMessage(
        $this->t('Could not find any Contract data in @file', ['@file' => $file]),
        'error'
      );
      return $form;
    }

    // Make sure we don't have active contract without any network set.
    if (count($contractEntity->getNetworks()) <1 ) {
      $contractEntity->setStatus(0);
      \Drupal::messenger()->addMessage(
        $this->t('No active networks found. Disabled contract'), 'warning');
    }

    try {
      if ($contractEntity->save()) {

        //@todo We should invalidate the Rest Cache.

        \Drupal::messenger()->addMessage(
          $this->t('Updated contract data from file @file', ['@file' => $file]));
      }
    }
    catch (\Exception $exception) {
      \Drupal::messenger()->addMessage(
        $this->t('Could save entity ', ['@entity' => $contractEntity->id()]),
        'error'
      );
    }
    return $this->redirect('entity.smartcontract.edit_form', ['smartcontract' => $this->entity->id()]);
  }

}
