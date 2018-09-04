<?php

namespace Drupal\ethereum\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ethereum_address' field type.
 *
 * @FieldType(
 *   id = "ethereum_address",
 *   label = @Translation("Ethereum address"),
 *   description = @Translation("Provides a field for Ethereum addresses."),
 *   default_widget = "ethereum_address",
 *   default_formatter = "ethereum_address",
 *   list_class = "\Drupal\ethereum\Plugin\Field\FieldType\EthereumAddressFieldItemList",
 * )
 */
class EthereumAddressItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(TRUE);
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
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 42,
          'binary' => FALSE,
        ],
        'network' => [
          'type' => 'varchar_ascii',
          'length' => 10,
          'binary' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'Regex' => [
          'pattern' => '/^0x[0-9a-f]{40}/is',
          'message' => $this->t('An Ethereum address must start with "0x", followed by 40 hexadecimal characters. For example: "0x0000000000000000000000000000000000000000".'),
        ]
      ],
    ]);

    return $constraints;
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
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = strtolower($this->value);

    // Ensure that the default value for the 'network' property is the Ethereum
    // network ID of the current Ethereum server.
    if (empty($this->network)) {
      $this->network = \Drupal::service('ethereum.manager')->getCurrentNetworkId();
    }
  }

}
