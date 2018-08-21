<?php
namespace Drupal\ethereum_smartcontract\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of SmartContract.
 */
class SmartContractListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['status'] = $this->t('Active');
    $header['is_imported'] = $this->t('Imported');
    $header['label'] = $this->t('SmartContract');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['status'] = $entity->status() ? '✔' : '✖';
    $row['is_imported'] = $entity->get('is_imported') ?  : '';
    $row['is_imported'] =  $entity->get('is_imported') ? new FormattableMarkup('<span title="' . $entity->get('imported_file') . '">✔</span>', []) : '';

    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
