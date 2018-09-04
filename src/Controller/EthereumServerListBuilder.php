<?php

namespace Drupal\Ethereum\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a listing of Ethereum servers.
 */
class EthereumServerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];
    /** @var \Drupal\ethereum\EthereumServerInterface $entity */
    foreach (parent::load() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Name');
    $header['network_id'] = $this->t('Network ID');
    $header['url'] = $this->t('Server URL');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ethereum\EthereumServerInterface $entity */
    $row['id'] = $entity->id();

    $row['label'] = new FormattableMarkup('
        <div 
          class="server-info"
          data-server-enabled="@enabled"
          data-server-address="@address"
          data-server-network_id="@network_id"
        >
          <b>@label</b>
          <div>@description</div>
          <div class="live-info"></div>
        </div>', [
        '@label' => $entity->label(),
        '@description' => $entity->get('description'),
        '@enabled' => $entity->status() ? 'true' : 'false',
        '@address' => $entity->getUrl(),
        '@network_id' => $entity->getNetworkId(),
      ]
    );

    $row['network_id'] = $entity->getNetworkId();

    $row['url'] = $entity->getUrl();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();

    $build['#type'] = 'container';
    $build['#attributes']['id'] = 'ethereum-server-list';
    $build['#attached']['library'][] = 'core/drupal.ajax';
    $build['#cache'] = [
      'contexts' => $this->entityType->getListCacheContexts(),
      'tags' => $this->entityType->getListCacheTags(),
    ];

    $build['enabled']['heading']['#markup'] = '<h2>' . $this->t('Enabled', [], ['context' => 'Plural']) . '</h2>';
    $build['disabled']['heading']['#markup'] = '<h2>' . $this->t('Disabled', [], ['context' => 'Plural']) . '</h2>';

    foreach (['enabled', 'disabled'] as $status) {
      $build[$status]['#type'] = 'container';
      $build[$status]['table'] = [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => [],
      ];
      foreach ($entities[$status] as $entity) {
        $build[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    $build['enabled']['table']['#empty'] = $this->t('There are no enabled servers.');
    $build['disabled']['table']['#empty'] = $this->t('There are no disabled servers.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Add AJAX functionality to enable/disable operations.
    foreach (['enable', 'disable'] as $op) {
      if (isset($operations[$op])) {
        $operations[$op]['url'] = $entity->toUrl($op);
        // Enable and disable operations should use AJAX.
        $operations[$op]['attributes']['class'][] = 'use-ajax';
      }
    }

    return $operations;
  }

}
