<?php

namespace Drupal\ethereum\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ethereum\Controller\EthereumController;
use Drupal\ethereum\Plugin\Field\FieldType\EthereumAddressItem;

/**
 * Plugin implementation of the 'ethereum_address' formatter.
 *
 * @FieldFormatter(
 *   id = "ethereum_address",
 *   label = @Translation("Ethereum address"),
 *   field_types = {
 *     "ethereum_address",
 *     "ethereum_address_with_network",
 *   }
 * )
 */
class EthereumAddressFormatter extends BasicStringFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_to_network' => '',
      'rel' => '',
      'target' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['link_to_network'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the network address page'),
      '#default_value' => $this->getSetting('link_to_network'),
    ];
    $elements['rel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to the link'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('rel'),
      '#states' => array(
        'visible' => array(
          array(
            'input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][link_to_network]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    ];
    $elements['target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
      '#states' => array(
        'visible' => array(
          array(
            'input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][link_to_network]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    if (!empty($settings['link_to_network'])) {
      $summary[] = $this->t('Link to the network address page');
    }
    if (!empty($settings['rel'])) {
      $summary[] = $this->t('Add rel="@rel"', ['@rel' => $settings['rel']]);
    }
    if (!empty($settings['target'])) {
      $summary[] = $this->t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if (empty($settings['link_to_network'])) {
      return parent::viewElements($items, $langcode);
    }

    /** @var \Drupal\ethereum\EthereumManagerInterface $ethereum_manager */
    $ethereum_manager = \Drupal::service('ethereum.manager');
    $all_networks = $ethereum_manager->getAllNetworks();
    $current_network_id = $ethereum_manager->getCurrentNetworkId();
    $field_type_stores_network = $items->getFieldDefinition()->getType() === 'ethereum_address_with_network';

    foreach ($items as $delta => $item) {
      // If the field type associates an address to a specific network, use the
      // specified network, otherwise just use the current network.
      $network = $field_type_stores_network ? $all_networks[$item->network] : $all_networks[$current_network_id] ;
      $url = $this->buildUrl($item, $network);

      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $item->value,
        '#url' => $url,
        '#options' => $url->getOptions(),
      ];
    }

    return $elements;
  }

  /**
   * Builds the \Drupal\Core\Url object for a field item.
   *
   * @param \Drupal\ethereum\Plugin\Field\FieldType\EthereumAddressItem $item
   *   The  field item being rendered.
   * @param array $network
   *   An associative array with network information.
   *
   * @return \Drupal\Core\Url
   *   A Url object.
   */
  protected function buildUrl(EthereumAddressItem $item, array $network) {
    if (!empty($network['link_to_address'])) {
      $network_link = str_replace('@address', $item->value, $network['link_to_address']);
      $url = Url::fromUri($network_link);
    }
    else {
      $url = Url::fromRoute('<none>');
    }

    $settings = $this->getSettings();
    $options = $url->getOptions();

    // Add optional 'rel' attribute to link options.
    if (!empty($settings['rel'])) {
      $options['attributes']['rel'] = $settings['rel'];
    }
    // Add optional 'target' attribute to link options.
    if (!empty($settings['target'])) {
      $options['attributes']['target'] = $settings['target'];
    }
    $url->setOptions($options);

    return $url;
  }

}
