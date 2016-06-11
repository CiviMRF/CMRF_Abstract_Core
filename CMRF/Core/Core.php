<?php

/**
 * Base calls for the CiviCRM communication core
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

abstract class Core
{

  public abstract function createCall($entity, $action, $parameters, $options = NULL, $callback = NULL);
  
  public abstract function getConnectionProfiles();

  public abstract function getDefaultProfile();

  public abstract function isReady();


  abstract public function getCallStatus($call_id);

  abstract public function getCall($call_id);

  abstract public function findCall($options);




  protected abstract function _createConnection($connection_id, $connector_id);

  protected abstract function storeConnectionProfiles($profiles);

  protected abstract function getRegisteredConnectors();

  protected abstract function storeRegisteredConnectors($connectors);

  protected abstract function getConnections();

  protected abstract function storeConnections($connections);

  protected abstract function getSettings();

  protected abstract function storeSettings($settings);


  public function getConnectionProfile($profile_name) {
      $connection_profiles = $this->getConnectionProfiles();
      if (isset($connection_profiles[$profile_name])) {
        return $connection_profiles[$profile_name];
      } else {
        return NULL;
      }
    }


  public function createConnection($connector_id) {
    $connection_id = $this->generateURN("$connector_id");
    return $this->_createConnection($connection_id, $connector_id);
  }

  public function registerConnector($connector_name, $profile = NULL) {
    // first, make sure the profile is o.k.
    if ($profile === NULL) {
      $profile = $this->getDefaultProfile();
    }

    $profiles = $this->getConnectionProfiles();

    if (!isset($profiles[$profile])) {
      throw new Exception("Invalid profile '$profile'.", 1);
    }


    // find a new ID for the connector
    $connectors   = $this->getRegisteredConnectors();
    $connector_id = $this->generateURN("connector:$connector_name", $connectors);
    $connectors[$connector_id] = array(
      'type'    => $connector_name,
      'profile' => $profile,
      'id'      => $connector_id
      );
    $this->storeRegisteredConnectors($connectors);

    return $connector_id;
  }


  public function unregisterConnector($connector_identifier) {


  }


  /**
   * poor man's ID generator
   */
  protected function generateURN($type, &$existing_key_map = NULL) {
    $prefix = "urn:cmrf:" . $type;
    $new_id = NULL;
    do {
      $new_id = $prefix . substr(sha1(rand()), 25);
    } while (isset($existing_key_map[$new_id]));
    return $new_id;
  }

}
