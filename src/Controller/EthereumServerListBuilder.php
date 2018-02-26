<?php

namespace Drupal\Ethereum\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a listing of Example.
 */
class EthereumServerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['is_enabled'] = $this->t('Enabled');
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Name');
    $header['network_id'] = $this->t('Network ID');
    $header['url'] = $this->t('Server url');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

//    $row['is_enabled'] = new FormattableMarkup ('<span data-server-enabled="@enabled">@symbol</span>', [
//        '@enabled' => $entity->is_enabled ? 'true' : 'false',
//        '@symbol' => $entity->is_enabled ? '✔' : '✘',
//      ]
//    );

    $row['is_enabled'] = $entity->is_enabled ? '✔' : '✘';

    $row['id'] = $entity->id();

    $row['label'] = new FormattableMarkup ('
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
        '@description' => $entity->description,
        '@enabled' => $entity->is_enabled ? 'true' : 'false',
        '@address' => $entity->url,
        '@network_id' => $entity->network_id,
      ]
    );

    $row['network_id'] = $entity->network_id;

//    $row['url'] = new FormattableMarkup ('<span data-server-address="@address">@address</span>', [
//        '@address' => $entity->url,
//      ]
//    );
    $row['url'] = $entity->url;

    return $row + parent::buildRow($entity);
  }

}
