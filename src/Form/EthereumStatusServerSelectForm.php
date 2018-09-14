<?php

namespace Drupal\ethereum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\EthereumManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to select one of the active Ethereum network servers.
 */
class EthereumStatusServerSelectForm extends FormBase {

  /**
   * The Ethereum manager service.
   *
   * @var \Drupal\ethereum\EthereumManagerInterface
   */
  protected $ethereumManager;

  /**
   * Constructs a new EthereumStatusServerSelectForm.
   *
   * @param \Drupal\ethereum\EthereumManagerInterface $ethereum_manager
   *   The Ethereum Manager service.
   */
  public function __construct(EthereumManagerInterface $ethereum_manager) {
    $this->ethereumManager = $ethereum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ethereum.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ethereum_status_server_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $server_id = NULL) {
    $config = $this->config('ethereum.settings');
    $enabled_servers = $this->ethereumManager->getServersAsOptions(TRUE);

    foreach ($enabled_servers as $name => $label) {
      if ($name === $config->get('current_server')) {
        $enabled_servers[$name] = $label . ' (' . $this->t('default server') . ')';
      }
      if ($name === $config->get('frontend_server')) {
        $enabled_servers[$name] = $label . ' (' . $this->t('frontend server') . ')';
      }
    }

    $form['#title'] = $this->t('Check the status of any active server');

    $form['server'] = [
      '#type' => 'select',
      '#title' => $this->t('Reporting on:'),
      '#required' => TRUE,
      '#description' => $this->t('Choose a server to report on. Only enabled servers are listed.'),
      '#options' => $enabled_servers,
      '#default_value' => $server_id ?: $config->get('current_server'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('server')) {
      $form_state->setRedirect(
        'ethereum.status',
        ['server_id' => $form_state->getValue('server')]
      );
    }
  }

}
