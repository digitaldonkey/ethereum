<?php
namespace Drupal\ethereum_txsigner\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ethereum_txsigner\Entity\Txsigner;

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

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable this transaction signer library.'),
      '#default_value' => $this->entity->status(),
    );

    $form['jsFilePath'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Javascript file.'),
      '#description' => $this->t('Javascript file.'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->jsFilePath('jsFilePath'),
      '#required' => FALSE,
    );

    $form['cssFilePath'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CSS file.'),
      '#description' => $this->t('CSS file path.'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->cssFilePath('jsFilePath'),
      '#required' => FALSE,
    );


    // --> Block visibility settings.

    return $form;
  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  $jsFile = $form_state->getValue('jsFilePath');
    if (!Txsigner::isValidFilePath($jsFile)) {
      $form_state->setErrorByName('jsFilePath', $this->t('Cant not find file @file', ['@file' => $jsFile]));
    }

    $cssFile = $form_state->getValue('cssFilePath');
    if (!Txsigner::isValidFilePath($cssFile)) {
      $form_state->setErrorByName('cssFilePath', $this->t('Cant not find file @file', ['@file' => $cssFile]));
    }
  }

  /**
  * {@inheritdoc}
  */
  public function save(array $form, FormStateInterface $form_state) {

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
