<?php

/**
 * Base calls for the CiviCRM communication core
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

use \CMRF\PersistenceLayer\CallFactory;

abstract class Core {

  /** @var CallFactory */
  protected  $callfactory;

  /** @return CallFactory */
  public function getFactory() {
    return $this->callfactory;
  }

  protected abstract function getConnection($connector_id);

  public function createCall($connector_id, $entity, $action, $parameters, $options = NULL, $callback = NULL) {
    return $this->callfactory->createOrFetch($connector_id,$this,$entity,$action,$parameters,$options,$callback);
  }

  public function __construct(CallFactory $factory) {
    //TODO: implement connection factory to support multiple connection types with a single core implementation.
    $this->callfactory=$factory;
  }

  public function getCall($call_id) {
    return $this->callfactory->loadCall($call_id,$this);
  }

  public function findCall($options) {
    //TODO: not yet implemented, as options is not yet known.
    return $this->callfactory->findCall($options,$this);
  }


  public abstract function getConnectionProfiles();

  public abstract function getDefaultProfile();

  // public function getConnectionProfile($profile_name);



  // public function registerConnector($connector_name, $profile = NULL)

  // public function unregisterConnector($connector_identifier)

  protected abstract function getRegisteredConnectors();

  protected abstract function storeRegisteredConnectors($connectors);

  protected abstract function getSettings();

  protected abstract function storeSettings($settings);



  /**
   * override if certain conditions need to be checked
   */
  public function isReady() {
    return TRUE;
  }



  public function executeCall(Call $call) {
    if ($call->getStatus() == Call::STATUS_DONE) {
      // this seems to be cached
      return $call;
    } else {
      $connection = $this->getConnection($call->getConnectorID());
      $reply = $connection->executeCall($call);
      $call->executeCallbacks();
      return $reply;
    }
  }

  public function queueCall(Call $call) {
    if ($call->getStatus() == Call::STATUS_DONE) {
      // this seems to be cached
      $this->performCallback($call);
    } else {
      $connection = $this->getConnection($call->getConnectorID());
      $connection->queueCall($call);
    }
  }

  /**
   * override for a more efficient implementation
   */
  public function getCallStatus($call_id) {
    $call = $this->getCall($call_id);
    return $call->getStatus();
  }

  public function getConnectionProfile($connector_id) {
    // find connector
    $connectors = $this->getRegisteredConnectors();
    if (!isset($connectors[$connector_id])) {
      throw new \Exception("Unregistered connector '$connector_id'.", 1);
    }

    // get profile
    $profile_name = $connectors[$connector_id]['profile'];
    if (empty($profile_name)) {
      $profile_name = $this->getDefaultProfile();
    }
    $connection_profiles = $this->getConnectionProfiles();
    if (isset($connection_profiles[$profile_name])) {
      return $connection_profiles[$profile_name];
    } else {
      throw new \Exception("Invalid profile '$profile_name'.", 1);
    }
  }

  public function registerConnector($connector_name, $profile = NULL) {
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
