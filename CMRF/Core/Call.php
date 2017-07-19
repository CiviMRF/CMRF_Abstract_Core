<?php

/**
 * Interface for CiviCRM API call objects
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;


Interface Call {

  const STATUS_INIT    = 'INIT';
  const STATUS_WAITING = 'WAIT';
  const STATUS_SENDING = 'SEND';
  const STATUS_DONE    = 'DONE';
  const STATUS_RETRY   = 'RETRY';
  const STATUS_FAILED  = 'FAIL';

  public function getID();

  public function setID($id);

  public function getConnectorID();

  public function getEntity();

  public function getAction();

  public function getParameters();

  public function getRequest();

  public function getOptions();

  public function getStatus();

  public function getStats();

  public function setStatus($status, $error_message, $error_code);

  public function getReply();

  public function setReply($data, $newstatus);

  public function getValues();

  public function triggerCallback();

  public function getHash();

  /** @return \DateTime */
  public function getReplyDate();

  public function setReplyDate(\DateTime $date);

  public function getRetryCount();

  public function setRetryCount($count);

}

