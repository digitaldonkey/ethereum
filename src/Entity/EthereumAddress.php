<?php

namespace Drupal\ethereum\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ethereum\EthereumAddressInterface;

/**
 * Defines the ethereum_address entity class.
 *
 * @ContentEntityType(
 *   id = "ethereum_address",
 *   label = @Translation("Ethereum address"),
 *   label_collection = @Translation("Ethereum address"),
 *   label_singular = @Translation("address"),
 *   label_plural = @Translation("addresses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count address",
 *     plural = "@count addresses"
 *   ),
 *   handlers = {
 *     "storage_schema" = "Drupal\ethereum\EthereumAddressStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\ethereum\EthereumAddressAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\ethereum\EthereumAddressListBuilder",
 *   },
 *   admin_permission = "administer site configuration",
 *   base_table = "ethereum_address",
 *   entity_keys = {
 *     "id" = "address",
 *     "label" = "address",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/ethereum/addresses/address/{ethereum_address}",
 *     "add-form" = "/admin/config/ethereum/addresses/add",
 *     "edit-form" = "/admin/config/ethereum/addresses/address/{ethereum_address}/edit",
 *     "delete-form" = "/admin/config/ethereum/addresses/address/{ethereum_address}/delete",
 *     "collection" = "/admin/config/ethereum/addresses",
 *   }
 * )
 */
class EthereumAddress extends ContentEntityBase implements EthereumAddressInterface {

  /**
   * {@inheritdoc}
   */
  public function getNetworkId() {
    return $this->get('network')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNetworkID($network_id) {
    $this->set('network', $network_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isContract() {
    return (bool) $this->get('contract')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setContract($is_contract) {
    $this->set('contract', $is_contract);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['address'] = BaseFieldDefinition::create('ethereum_address')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'basic_string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'ethereum_address',
      ]);

    $fields['network'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Network'))
      ->setDescription(new TranslatableMarkup('The Ethereum network on which this address exists.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDefaultValueCallback('\Drupal\ethereum\Entity\EthereumAddress::getCurrentNetworkId')
      ->setSetting('allowed_values_function', '\Drupal\ethereum\Controller\EthereumController::getNetworksAsOptions')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ]);


    $fields['contract'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Is contract'))
      ->setDefaultValue(FALSE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ]);

    return $fields;
  }

  /**
   * Default value callback for the 'network' base field.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentNetworkId() {
    $current_server = \Drupal::config('ethereum.settings')->get('current_server');
    $server = EthereumServer::load($current_server);
    return [$server->getNetworkId()];
  }

}
