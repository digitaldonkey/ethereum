<?php

namespace Drupal\ethereum;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ethereum\Controller\EthereumController;

/**
 * Defines a class to build a listing of ethereum_address entities.
 */
class EthereumAddressListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'address' => $this->t('Address'),
      'network' => $this->t('Network'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ethereum\EthereumAddressInterface $entity */
    $row['address'] = $entity->id();
    $networks = EthereumController::getNetworksAsOptions();
    $row['network'] = $networks[$entity->getNetworkId()];
    return $row + parent::buildRow($entity);
  }

}
