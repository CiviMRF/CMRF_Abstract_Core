<?php

/**
 * A simple, serialisable implementation of CMRF\Core\Call
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Local;

use CMRF\Core\Call as CallInterface;

include_once('CMRF/Core/Call.php');

class Call implements CallInterface {

  protected $id;
  protected $core;
  protected $data;

  public function __construct($id, $core, $entity, $action, $parameters, $options, $callback) {
    $this->id   = $id;
    $this->core = $core;
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

  public function setReply($data) {
    $this->data['reply'] = $data;
  }

  public function getID() {
    return $this->id;
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

  public function getValues() {
    if (isset($this->data['reply']['values'])) {
      return $this->data['reply']['values'];
    } else {
      return array();
    }
  }

  public function setStatus($status, $error_message, $error_code = NULL) {
    $this->data['status']        = $status;
    $this->data['error_message'] = $error_message;
    $this->data['error_code']    = $error_code;
  }

}

