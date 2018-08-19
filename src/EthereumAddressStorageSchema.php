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

    // Use a composite primary key because an Ethereum address can only exist in
    // the context of a specific network.
    $schema[$this->storage->getBaseTable()]['primary key'] = [$entity_type->getKey('id'), 'network'];

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
        case 'network':
          $schema['fields'][$field_name]['not null'] = TRUE;
          $schema['fields'][$field_name]['length'] = 4;
          break;
      }
    }

    return $schema;
  }

}
