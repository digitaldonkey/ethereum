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

  use SmartContractFormTrait;

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
    /* @var $contract \Drupal\ethereum_smartcontract\Entity\SmartContract */
    $contract = $this->entity;
    return $this->formFromEntity($form, $contract);
  }

  /**
   * @param string $contract_compiled
   *
   * @return string
  */
  private static function parseAbi($contract_compiled) {
    return json_decode($contract_compiled)->interface;
  }
}
