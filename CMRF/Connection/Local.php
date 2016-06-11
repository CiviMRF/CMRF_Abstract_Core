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

class Local extends Connection
{
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
    } catch (Exception $e) {
      $call->setStatus(Call::STATUS_FAILED, $e->getMessage());
    }
    $this->processReply($reply);
    return $reply;
  }


  /**
   * queue call for asynchroneous execution
   */
  public function queueCall(Call $call) {
    return $this->executeCall($call);
  }

}

