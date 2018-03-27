<?php
namespace Drupal\ethereum_txsigner\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* Form handler for the TxsignerForm add and edit forms.
*/
class TxsignerForm extends EntityForm {

  /**
  * Constructs an TxsignerForm object.
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

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
      'exists' => array($this, 'exist'),
    ),
      '#disabled' => !$this->entity->isNew(),
    );

    $form['is_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable this transaction signer library.'),
      '#default_value' => $this->entity->is_enabled,
    );

    // TODO
    // We might implement a use only on this path condition (like block visibility).
    // See: https://www.previousnext.com.au/blog/using-drupal-8-condition-plugins-api
    // Implement per TxSigner or for all TxSigners?

    return $form;
  }

  /**
  * {@inheritdoc}
  */
  public function save(array $form, FormStateInterface $form_state) {

    // TODO
    // We should ensure that always at least one TX signer is active.

    $status = $this->entity->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Transaction signer.', array(
        '%label' => $this->entity->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label was not saved.', array(
        '%label' => $this->entity->label(),
      )));
    }
    $form_state->setRedirect('entity.txsigner.collection');
  }

  /**
  * Helper function to check whether an TX signer configuration entity exists.
  */
  public function exist($id) {
    $entity = $this->entityQuery->get('txsigner')
    ->condition('id', $id)
    ->execute();
    return (bool) $entity;
  }

}
