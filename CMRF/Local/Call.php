<?php

/**
 * A simple, serialisable implementation of CMRF\Core\Call
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Local;

use CMRF\Core\Call         as CallInterface;
use CMRF\Core\AbstractCall as AbstractCall;

include_once('CMRF/Core/AbstractCall.php');

class Call extends AbstractCall {

  protected $data;

  public function __construct($connector_id, $id, $core, $entity, $action, $parameters, $options, $callback) {
    parent::__construct($core, $connector_id, $id);
    $this->data = array(
      'id'       => $id,
      'call'     => array(
        'entity'     => $entity,
        'action'     => $action,
        'parameters' => $parameters),
      'reply'    => NULL,
      'options'  => $options,
      'callback' => $callback,
      'status'   => Call::STATUS_INIT,
      'stats'    => array(
        'created'    => date('YmdHis')
        )
      );
  }

  public function getRequest() {
    return $this->compileRequest();
  }

  public function getReply() {
    return $this->data['reply'];
  }

  public function setReply($data, $newstatus = CallInterface::STATUS_DONE) {
    $this->data['reply'] = $data;
  }

  public function getEntity() {
    return $this->data['call']['entity'];
  }

  public function getAction() {
    return $this->data['call']['action'];
  }

  public function getParameters() {
    return $this->data['call']['parameters'];
  }

  public function getOptions() {
    return $this->data['options'];
  }

  public function getStatus() {
    return $this->data['status'];
  }

  public function getStats() {
    return $this->data['stats'];
  }

  public function triggerCallback() {
    if (function_exists($this->data['callback'])) {
      $this->data['callback']($this);
    }
  }

  public function setStatus($status, $error_message, $error_code = NULL) {
    $this->data['status']        = $status;
    $this->data['error_message'] = $error_message;
    $this->data['error_code']    = $error_code;
  }

}

