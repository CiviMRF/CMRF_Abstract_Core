<?php

/**
 * TODO
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

abstract class Connection {

  protected $core         = NULL;
  protected $connector_id = NULL;
  
  public function __construct($core, $connector_id) {
    $this->core = $core;
    $this->connector_id = $connector_id;
  }

  abstract public function getType();

  abstract public function isReady();

  abstract public function executeCall(Call $call);

  protected function getProfile() {
    return $this->core->getConnectionProfile($this->connector_id);
  }

  public function getCore() {
    return $this->core;
  }

  public function getAPI3Params($call) {
    $parameters = $call->getParameters();

    $options = $call->getOptions();
    if (!empty($options['limit'])) {
      $parameters['option.limit'] = (int) $options['limit'];
    }

    return $parameters;
  }

  public function queueCall(Call $call) {
    // TODO: override if async calls are possible
    $this->executeCall($call);
  }

}

