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
  public function buildHeader() {
    $header['status'] = $this->t('Enabled');
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
    $row['status'] = $entity->status() ? '✔' : '✘';

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

}
