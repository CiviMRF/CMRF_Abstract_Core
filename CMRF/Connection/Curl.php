<?php

/**
 * Remote CiviCRM connection based on CURL
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Connection;

use CMRF\Core\Call as Call;
use CMRF\Core\Connection as Connection;

include_once('CMRF/Core/Connection.php');

class Curl extends Connection
{
  /**
   * execute the given call synchroneously
   * 
   * return call status
   */
  public function executeCall(Call $call) {
    $profile               = $this->getProfile();

    $request               = $this->getAPI3Params($call);
    $request['api_key']    = $profile['api_key'];
    $request['key']        = $profile['site_key'];
    $request['json']       = 1;
    $request['version']    = 3;
    $request['entity']     = $call->getEntity();
    $request['action']     = $call->getAction();

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST,           1);
    curl_setopt($curl, CURLOPT_POSTFIELDS,     $request);
    curl_setopt($curl, CURLOPT_URL,            $profile['url']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSLVERSION,     1);

    $response = curl_exec($curl);
    
    if (curl_error($curl)){
      $call->setStatus(Connection::STATUS_FAILED, curl_error($curl));
      return NULL;
    } else {
      $reply = json_decode($response, true);
      if ($reply===NULL) {
        $call->setStatus(Connection::STATUS_FAILED, curl_error($curl));
        return NULL;
      } else {
        $call->setReply($reply);
        return $reply;
      }
    }
  }


  /**
   * queue call for asynchroneous execution
   */
  public function queueCall(Call $call) {
    return $this->executeCall($call);
  }

}
