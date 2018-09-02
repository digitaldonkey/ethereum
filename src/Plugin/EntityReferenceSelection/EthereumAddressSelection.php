<?php

namespace Drupal\ethereum\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum\Entity\EthereumServer;

/**
 * Provides specific selection logic for the ethereum_address entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:ethereum_address",
 *   label = @Translation("Ethereum address selection"),
 *   entity_types = {"ethereum_address"},
 *   group = "default",
 *   weight = 1
 * )
 */
class EthereumAddressSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'restrict_to_current_network' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['restrict_to_current_network'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter the available addresses by the current network'),
      '#default_value' => $configuration['restrict_to_current_network'],
    ];
    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $configuration = $this->getConfiguration();

    // Filter by the current network if needed.
    if ($configuration['restrict_to_current_network']) {
      $current_server = \Drupal::config('ethereum.settings')->get('current_server');
      $server = EthereumServer::load($current_server);

      $query->condition('network', $server->getNetworkId(), '=');
    }

    return $query;
  }

}
