<?php

namespace Drupal\ethereum\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ethereum_address_with_network' field type.
 *
 * @FieldType(
 *   id = "ethereum_address_with_network",
 *   label = @Translation("Ethereum address with network"),
 *   description = @Translation("Provides a field for Ethereum addresses that are dependent to a specific network."),
 *   default_widget = "ethereum_address",
 *   default_formatter = "ethereum_address",
 *   list_class = "\Drupal\ethereum\Plugin\Field\FieldType\EthereumAddressFieldItemList",
 * )
 */
class EthereumAddressWithNetworkItem extends EthereumAddressItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['network'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Network'))
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['network'] = [
      'type' => 'varchar_ascii',
      'length' => 10,
      'binary' => FALSE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = [
      'value' => '0x0000000000000000000000000000000000000000',
      'network' => '3',
    ];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Ensure that the default value for the 'network' property is the Ethereum
    // network ID of the current Ethereum server.
    if (empty($this->network)) {
      $this->network = \Drupal::service('ethereum.manager')->getCurrentNetworkId();
    }
  }

}
