<?php

namespace Drupal\ethereum\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\Entity\EthereumServer;
use Drupal\ethereum\Controller\EthereumController;

/**
 * Form handler for the Ethereum Server add and edit forms.
 */
class EthereumServerForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\ethereum\EthereumServerInterface
   */
  protected $entity;

  /**
  * {@inheritdoc}
  */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $server = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ethereum node name'),
      '#maxlength' => 255,
      '#default_value' => $server->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $server->id(),
      '#machine_name' => [
        'exists' => [EthereumServer::class, 'load'],
      ],
      '#disabled' => !$server->isNew(),
    ];

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t("Network URI"),
      '#description' => $this->t("Server URI with port number. e.g: http://localhost:8545"),
      '#required' => TRUE,
      '#default_value' => $server->getUrl(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t("Disabled servers will be ignored entirely. You can't disable the current default server."),
      '#default_value' => $server->status(),
      '#disabled' => $server->isDefaultServer(),
    ];

    $form['network_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Ethereum Network ID'),
      '#options' => EthereumController::getNetworksAsOptions(),
      '#required' => TRUE,
      '#default_value' => $server->getNetworkId(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A short description of this Ethereum node.'),
      '#maxlength' => 512,
      '#default_value' => $server->get('description'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $server = $this->entity;
    $status = $server->save();

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The server %label has been updated.', ['%label' => $server->label()]));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The server %label has been added.', ['%label' => $server->label()]));
    }

    $form_state->setRedirect('ethereum.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Disabling of the current default server is prohibited.
    if ($this->entity->isDefaultServer() && !$form_state->getValue('status')) {
      $form_state->setError($form['status'], $this->t('You can not disable the current default server.'));
    }
  }

}
