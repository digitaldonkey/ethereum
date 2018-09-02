<?php

namespace Drupal\ethereum;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the ethereum_address schema handler.
 */
class EthereumAddressStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add a UNIQUE index on the 'address' and 'network' fields because two
    // identical addresses can not live on the same network.
    $schema[$this->storage->getBaseTable()]['unique keys'] += [
      'ethereum_address__address_network' => ['address', 'network'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == $this->storage->getBaseTable()) {
      switch ($field_name) {
        case 'address':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
        case 'network':
          $schema['fields'][$field_name]['not null'] = TRUE;
          $schema['fields'][$field_name]['length'] = 10;
          break;
      }
    }

    return $schema;
  }

}
