<?php
namespace Drupal\ethereum_smartcontract\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
      '#description' => $this->t("Label for the Smart Contract ."),
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
      '#type' => 'textfield',
      '#title' => $this->t('contract_src'),
      '#maxlength' => 255,
      '#default_value' => $contract->contract_src,
      '#description' => $this->t("contract_src for the Smart Contract ."),
      '#required' => TRUE,
    );

    $form['contract_js'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('contract_js'),
      '#maxlength' => 255,
      '#default_value' => $contract->contract_js,
      '#description' => $this->t("contract_js for the Smart Contract ."),
      '#required' => TRUE,
    );

    // You will need additional form elements for your custom properties.
    return $form;
  }


  // TODO VALIDATE.

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
