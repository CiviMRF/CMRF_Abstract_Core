<?php

/**
 * Interface for CiviCRM API call objects
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;


Interface Call {

  const STATUS_INIT    = 10;
  const STATUS_WAITING = 20;
  const STATUS_SENDING = 30;
  const STATUS_DONE    = 40;
  const STATUS_RETRY   = 50;
  const STATUS_FAILED  = 60;


  public function getID();

  public function getEntity();

  public function getAction();

  public function getParameters();

  public function getOptions();

  public function getStatus();

  public function getStats();

  public function setStatus($status, $error_message, $error_code);

  public function setReply($data);

  public function getValues();

  
}

