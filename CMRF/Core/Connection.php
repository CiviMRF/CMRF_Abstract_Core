<?php

/**
 * TODO
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

abstract class Connection {

  protected $id           = NULL;
  protected $core         = NULL;
  protected $connector_id = NULL;

  
  public function __construct($id, $core, $connector_id) {
    $this->id           = $id;
    $this->core         = $core;
    $this->connector_id = $connector_id;
  }

  public function getCore() {
    return $this->core;
  }

  public function getConnectorID() {
    return $this->connector_id;
  }

  public function getID() {
    return $this->id;
  }

  public function getProfile() {
    return  $this->core->getConnectionProfile($this->core->getDefaultProfile());
  }

  public function queueCallChain($call_list) {
    // TODO: implement
    throw new Exception("TODO: link calls with chaining callback", 1);
  }

  public function getAPI3Params($call) {
    $parameters = $call->getParameters();

    $options = $call->getOptions();
    if (!empty($options['limit'])) {
      $parameters['option.limit'] = int($options['limit']);
    }

    return $parameters;
  }

  /**
   * execute the given call synchroneously
   * 
   * return call status
   */
  abstract public function executeCall(Call $call);


  /**
   * queue call for asynchroneous execution
   */
  abstract public function queueCall(Call $call);

}

