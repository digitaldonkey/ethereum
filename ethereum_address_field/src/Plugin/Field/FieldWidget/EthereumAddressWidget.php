<?php

namespace Drupal\ethereum_address_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ethereum_address_widget' widget.
 *
 * @FieldWidget(
 *   id = "ethereum_address_widget",
 *   label = @Translation("Ethereum address widget"),
 *   field_types = {
 *     "ethereum_address"
 *   }
 * )
 */
class EthereumAddressWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 22,
      'placeholder' => '0xaec98826319ef42aab9530a23306d5a9b113e23d',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      // See:
      // https://www.w3schools.com/code/tryit.asp?filename=FEBTROZXKYL9
      '#pattern' => '0[x,X]{1}[0-9,a-f,A-F]{40}',
      '#attributes' => array(
        'oninvalid' => 'setCustomValidity("' . $this->t('Please enter a valid Ethereum address. E.g: 0xaEC98826319EF42aAB9530A23306d5a9b113E23D') . '")',
        'onchange' => "try{setCustomValidity('')}catch(e){}",
      ),
    ];
    return $element;
  }

}
