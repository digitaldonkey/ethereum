<?php

namespace Drupal\ethereum\Form;


use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ethereum\Controller\EthereumController;

/**
* Form handler for the Example add and edit forms.
*/
class EthereumServerForm extends EntityForm {

  /**
  * Constructs an ExampleForm object.
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
    $server = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ethereum node name'),
      '#maxlength' => 255,
      '#default_value' => $server->label(),
      '#description' => $this->t("Readable name for Ethereum node."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $server->id(),
      '#machine_name' => [
      'exists' => [$this, 'exist'],
    ],
      '#disabled' => !$server->isNew(),
    ];

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t("Network uri"),
      '#description' => t("Server uri with port number. e.g: http://localhost:8545"),
      '#required' => TRUE,
      '#default_value' => $server->url,
    ];

    $defaultServer = \Drupal::config('ethereum.settings')->get('current_server');

    $form['is_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Server enabled'),
      '#description' => $this->t('Disabled servers will be ignored entirely. You cant disable the current Drupal default server.'),
      '#default_value' => $server->is_enabled,
      '#disabled' => ($server->id() === $defaultServer),
    );

    $form['network_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Ethereum Network ID'),
      '#options' => EthereumController::getNetworksAsOptions(),
      '#required' => TRUE,
      '#default_value' => $server->network_id,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t("What ever you want to say about this Ethereum node."),
      '#maxlength' => 512,
      '#default_value' => $server->description,
    ];

    return $form;
  }

  /**
  * {@inheritdoc}
  */
  public function save(array $form, FormStateInterface $form_state) {
    $server = $this->entity;
    $status = $server->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Example.', [
      '%label' => $server->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label Example was not saved.', [
        '%label' => $server->label(),
      ]));
    }

    $form_state->setRedirect('ethereum.settings');
  }



  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $defaultServer = \Drupal::config('ethereum.settings')->get('current_server');
    $isDefaultServer = ($form_state->getValue('id') === $defaultServer);

    // Disabling of current default server is prohibited.
    if ($isDefaultServer && !$form_state->getValue('is_enabled')) {
      $form_state->setError(
        $form['is_enabled'],
        t('You can not disable the current drupal default server.')
      );
    }
  }


  /**
  * Helper function to check whether an Example configuration entity exists.
  */
  public function exist($id) {
    $entity = $this->entityQuery->get('ethereum_server')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
