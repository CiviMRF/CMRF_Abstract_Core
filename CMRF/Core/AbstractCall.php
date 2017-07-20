<?php

/**
 * Abstract base class for Calls
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

use CMRF\Core\Call as CallInterface;


abstract class AbstractCall implements CallInterface {

  protected static $api_options  = array('limit', 'offset', 'sort', 'sequential');
  protected static $cmrf_options = array('cache');
  protected static $protected    = array('action', 'entity');

  protected $id           = NULL;
  protected $reply_date = NULL;
  protected $scheduled_date = NULL;
  protected $retry_count = 0;
  protected $core         = NULL;
  protected $connector_id = NULL;
  /** @var \CMRF\PersistenceLayer\CallFactory */
  protected $factory      = NULL;

  public function __construct($core, $connector_id, $factory,$id=NULL) {
    $this->factory      = $factory;
    $this->core         = $core;
    $this->connector_id = $connector_id;
    $this->id           = $id;
  }

  abstract public function getEntity();

  abstract public function getAction();

  abstract public function getParameters();

  abstract public function getRequest();

  abstract public function getOptions();

  abstract public function getStatus();

  /**
   * Returns the date and time when the call should be processed.
   *
   * @return \DateTime|null
   */
  abstract public function getCachedUntil();

  abstract public function getStats();

  abstract public function setStatus($status, $error_message, $error_code);

  abstract public function setReply($data, $newstatus);

  abstract public function getReply();

  abstract public function triggerCallback();


  public function getID() {
    return $this->id;
  }

  public function setID($id) {
    $this->id = $id;
  }

  public function getCore() {
    return $this->core;
  }

  public function getConnectorID() {
    return $this->connector_id;
  }

  public function getValues() {
    $reply = $this->getReply();
    if (isset($reply['values'])) {
      return $reply['values'];
    } else {
      return array();
    }
  }

  public function getHash() {
    $request = $this->getRequest();
    self::normaliseArray($request);
    return sha1(json_encode($request));
  }

  public static function getHashFromParams($entity, $action, $parameters, $options) {
    $filtered_options = array();
    foreach ($options as $key => $value) {
      if (in_array($key, self::$api_options)) {
        $filtered_options[$key] = $value;
      }
    }

    $request = $parameters;
    $request['options'] = $filtered_options;
    $request['entity']  = $entity;
    $request['action']  = $action;
    self::normaliseArray($request);
    return sha1(json_encode($request));
  }

  /** @return \DateTime */
  public function getReplyDate()
  {
    return $this->reply_date;
  }

  public function setReplyDate(\DateTime $date)
  {
    $this->reply_date=$date;
  }

  /** @return \DateTime */
  public function getScheduledDate() {
    return $this->scheduled_date;
  }

  public function setScheduledDate(\DateTime $date) {
    $this->scheduled_date = $date;
  }

  public function getRetryCount()
  {
    return $this->retry_count;
  }

  public function setRetryCount($count)
  {
    $this->retry_count=$count;
  }


  protected function compileRequest($parameters, $options) {
    $request = $parameters;
    $request['entity'] = $this->getEntity();
    $request['action'] = $this->getAction();

    $all_options = $options;
    $request['options'] = array();
    foreach ($all_options as $key => $value) {
      if (in_array($key, self::$api_options)) {
        $request['options'][$key] = $value;
      }
    }

    return $request;
  }

  protected function extractOptions($request) {
    // only return the options from the request
    $options = array();
    foreach ($request as $key => $value) {
      if (in_array($key, self::$api_options)) {
        $options[$key] = $value;
      }
    }

    foreach ($request as $key => $value) {
      if (in_array($key, self::$cmrf_options)) {
        $options[$key] = $value;
      }
    }

    return $options;
  }

  protected function extractParameters($request) {
    // filter out all unwanted fields
    foreach (self::$api_options as $field_name) {
      if (isset($request[$field_name])) {
        unset($request[$field_name]);
      }
    }

    foreach (self::$cmrf_options as $field_name) {
      if (isset($request[$field_name])) {
        unset($request[$field_name]);
      }
    }

    foreach (self::$protected as $field_name) {
      if (isset($request[$field_name])) {
        unset($request[$field_name]);
      }
    }

    return $request;
  }

  protected static function normaliseArray(&$array) {
    ksort($array);
    foreach($array as &$value) {
      if (is_array($value)) {
        self::normaliseArray($value);
      }
    }
  }
}

