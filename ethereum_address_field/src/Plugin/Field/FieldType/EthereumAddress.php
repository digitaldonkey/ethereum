<?php

namespace Drupal\ethereum_address_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ethereum_address' field type.
 *
 * @FieldType(
 *   id = "ethereum_address",
 *   label = @Translation("Ethereum address"),
 *   description = @Translation("Provides a field for Ethereum addresses."),
 *   default_widget = "ethereum_address_widget",
 *   default_formatter = "ethereum_address_formatter_type"
 * )
 */
class EthereumAddress extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 42,
      'is_ascii' => TRUE,
      'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length
            ]),
          ],
        ],
      ]);
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Regex' => array(
            'pattern' => '/^0x/is',
            'message' => $this->t('Ethereum address must start with "0x".'),
          )
        ),
      ));
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Regex' => array(
            'pattern' => '/^0x[0-9a-f]{40}/is',
            'message' => $this->t('Ethereum requires 40 Hex chars after  "0x". E.g. "0x0000000000000000000000000000000000000000" (Regexp: "/^0x[0-9a-f]{40}/is).'),
          )
        ),
      ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => $this->t('Ethereum address has a fixed length.'),
      '#min' => 1,
      '#disabled' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = strtolower($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
