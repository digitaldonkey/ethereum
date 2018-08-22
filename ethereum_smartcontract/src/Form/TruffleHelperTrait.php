<?php


namespace Drupal\ethereum_smartcontract\Form;


use Drupal\ethereum\Controller\EthereumController;
use Drupal\ethereum_smartcontract\Entity\SmartContract;
use Ethereum\SmartContract as Web3Contract;


trait TruffleHelperTrait {

  /**
   * @param string $file Path to Truffle Json file.
   * @param SmartContract|null  $entity
   *
   * @return \Drupal\ethereum_smartcontract\Entity\SmartContract|null
   */
  protected static function truffleToEntity(string $file, SmartContract $entity = null) {
    $meta = Web3Contract::createMetaFromTruffle($file);
    $networks = (array) $meta->networks;

    if (!is_object($meta)) {
      return null;
    }
    $values = [
      'contract_src' => $meta->source,
      'contract_compiled_json' => '',
      'networks' => self::importNetworks($networks),
      'imported_file' => $file,
      'is_imported' => TRUE,
      'abi' => json_encode((array) $meta->abi),
    ];

    if ($entity) {
      foreach ($values as $k => $v) {
        $entity->set($k, $v);
      }
    }
    else {
      $entity = new SmartContract($values, 'smartcontract');
      // Make sure user can Edit the machine name ID.
      $entity->enforceIsNew();
    }
    return $entity;
  }


  /**
   *
   * @param Object[] $deployedNetworks
   * @return array
   */
  protected static function importNetworks(array $deployedNetworks) {
    $available = EthereumController::getNetworks();
    $return = [];
    foreach ($deployedNetworks as $netId => $network) {
      if (isset($available[$netId])) {
        $return[$netId] = $network->address;
      }
      else {
        \Drupal::messenger()->addWarning(\Drupal::translation()->translate('The Network with id "%netId" is not available in Drupal.', array(
          '%netId' => $netId,
        )));
      }
    }
    return $return;
  }


  /**
   * @param $dir
   *
   * @return bool
   */
  protected function validateTruffleDir($dir) {
    $dirContent = scandir($dir);
    if (!in_array('contracts', $dirContent) || !in_array('build', $dirContent)) {
      return FALSE;
    }
    $dirBuildDirContent = scandir($dir . '/build');
    if (!in_array('contracts', $dirBuildDirContent) ) {
      return FALSE;
    }
    return TRUE;
  }


  /**
   * @param $dir
   *
   * @return array
   */
  protected function getContractsFromTruffle($dir) {
    try {
      return Web3Contract::createFromTruffleBuildDirectory(
        $this->getTruffleDataDir($dir)
      );
    }
    catch (\Exception $exception) {
      return [];
    }
  }


  /**
   * @param $path
   *
   * @return string
   */
  protected static function getTruffleDataDir($path) {
    return $path . '/build/contracts';
  }

  /**
   * @param string $path
   * @param string $contractName
   *
   * @return string
   */
  protected static function getTruffleCompiledPath(string $path, string $contractName) {
    return self::getTruffleDataDir($path) . '/' . $contractName .'.json';
  }

}
