<?php

/**
 * TODO
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Connection;

use CMRF\Core\Call as Call;
use CMRF\Core\Connection as Connection;

include_once('CMRF/Core/Connection.php');

class Local extends Connection {

  public function getType() {
    return 'local';
  }

  public function isReady() {
    return function_exists('civicrm_api3');
  }

  /**
   * execute the given call synchroneously
   * 
   * return call status
   */
  public function executeCall(Call $call) {    
    try {
      $reply = civicrm_api3(
        $call->getEntity(),
        $call->getAction(),
        $this->getAPI3Params($call));      
    } catch (\Exception $e) {
      $call->setStatus(Call::STATUS_FAILED, $e->getMessage());
    }
    $call->setReply($reply);
    return $reply;
  }
}

