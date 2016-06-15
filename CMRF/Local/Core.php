<?php

/**
 * A local CMRF Core implementation
 * This only works, if CiviCRM's civicrm_api3 function is available locally
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Local;

use CMRF\Core\Core        as AbstractCore;
use CMRF\Connection\Local as LocalConnection;
use CMRF\Local\Call       as Call;

include_once('CMRF/Core/Core.php');
include_once('CMRF/Connection/Local.php');
include_once('CMRF/Local/Call.php');


class Core extends AbstractCore {

  public function isReady() {
    // ready if CiviCRM is in our namespace
    return function_exists('civicrm_api3');
  }

  public function createCall($connector_id, $entity, $action, $parameters = array(), $options = array(), $callback = NULL) {
    $id = $this->generateURN("call:local");
    return new Call($connector_id, $id, $this, $entity, $action, $parameters, $options, $callback);
  }

  public abstract function getCall($call_id) {
    // TODO: implement
    return NULL;
  }

  public abstract function findCall($options) {
    // TODO: implement
    return NULL;
  }

  public function getConnection($connector_id) {
    return new LocalConnection($this);
  }

  public function getDefaultProfile() {
    return 'local';
  }

  public function getConnectionProfile($profile_name) {
    return array();
  }

  public function getConnectionProfiles() {
    return array(
      'local' => array(
        'title' => 'Local Connection',
        )
      );
  }

  public function getCallStatus($call_id) {
    $call = getCall($call_id);
    return $call->getStatus();
  }

  public function getCall($call_id) {
    // TODO:
  }

  public function findCall($options) {
    // TODO:
  }


  protected function storeConnectionProfiles($profiles) {
    // nothing to do, only one profile
  }

  protected function getRegisteredConnectors() {
    $this->loadFile('registered_connectors.json');
  }

  protected function storeRegisteredConnectors($connectors) {
    $this->storeFile('registered_connectors.json', $connectors);
  }

  protected function getSettings() {
    $this->loadFile('settings.json');
  }

  protected function storeSettings($settings) {
    $this->storeFile('setting.json', $settings);
  }








  protected function getPath($filename) {
    return sys_get_temp_dir() . '/' . $filename;
  }

  protected function loadFile($filename) { 
    $path = $this->getPath($filename);
    if (file_exists($path)) {
      return json_decode(file_get_contents($path));
    } else {
      return array();
    }
  }

  protected function storeFile($filename, $data) {
    $path = $this->getPath($filename);
    file_put_contents($path, json_encode($data));
  }
}

