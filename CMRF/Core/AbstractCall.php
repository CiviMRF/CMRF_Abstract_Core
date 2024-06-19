<?php

/**
 * Abstract base class for Calls
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

use CMRF\Core\Call as CallInterface;


abstract class AbstractCall implements CallInterface {

  protected static $api_options  = array('limit', 'offset', 'sort');
  protected static $cmrf_options = array('cache');
  protected static $protected    = array('action', 'entity', 'version');

  protected $id           = NULL;
  protected $reply_date = NULL;
  protected $scheduled_date = NULL;
  /** @var \DateTime  */
  protected $date = NULL;
  protected $retry_count = 0;
  /** @var \CMRF\Core\Core */
  protected $core         = NULL;
  protected $connector_id = NULL;
  /** @var \CMRF\PersistenceLayer\CallFactory */
  protected $factory      = NULL;

  /**
   * @var array
   *  Array with callback functions
   */
  protected $callbacks = array();

  public function __construct($core, $connector_id, $factory, $id = NULL) {
    $this->factory      = $factory;
    $this->core         = $core;
    $this->connector_id = $connector_id;
    $this->id           = $id;
    $this->date = new \DateTime();

    if ((new \ReflectionMethod($this, 'getApiVersion'))->getDeclaringClass()->getName() === __CLASS__) {
      trigger_error(
        sprintf(
          'Implementation of "getApiVersion" in class "%s" will be removed in next major version',
          __CLASS__
        ),
        \E_USER_DEPRECATED
      );
    }
  }

  /**
   * @todo Remove in next major version.
   *
   * @return string Always returns "3" for backward compatibility.
   */
  public function getApiVersion(): string {
    return '3';
  }

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
    // Include Entity and Action in the request hash to ensure cached dataset retrieval 
    // actually returns the expected values
    $request = [ $this->getEntity(), $this->getAction(), $this->getRequest() ];
    self::normaliseArray($request);
    return sha1(json_encode($request));
  }

  public static function getHashFromParams($entity, $action, $parameters, $options) {
    $filtered_options = array();
    foreach ($options as $key => $value) {
      if (in_array($key, self::$api_options, TRUE)) {
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

  /** @return \DateTime */
  public function getDate() {
    return $this->date;
  }

  public function setDate(\DateTime $date) {
    $this->date = $date;
  }

  public function getRetryCount()
  {
    return $this->retry_count;
  }

  public function setRetryCount($count)
  {
    $this->retry_count=$count;
  }

  /**
   * @inheritDoc
   */
  public function executeCallbacks() {
    foreach($this->callbacks as $callback) {
      if (is_callable($callback)) {
        call_user_func($callback, $this);
      }
    }
  }

  protected function compileRequest($parameters, $options) {
    $request = $parameters;

    $all_options = $options ?? [];
    $request['options'] = array();
    foreach ($all_options as $key => $value) {
      if (in_array($key, self::$api_options, TRUE)) {
        $request['options'][$key] = $value;
      }
    }

    return $request;
  }

  protected function extractOptions($request) {
    // only return the options from the request
    $options = array();
    foreach ($request as $key => $value) {
      if (in_array($key, self::$api_options, TRUE)) {
        $options[$key] = $value;
      }
    }

    foreach ($request as $key => $value) {
      if (in_array($key, self::$cmrf_options, TRUE)) {
        $options[$key] = $value;
      }
    }

    return $options;
  }

  protected function extractParameters($request) {
    // filter out all unwanted fields
    if ('3' === ($request['version'] ?? '3')) {
      foreach (self::$api_options as $field_name) {
        if (isset($request[$field_name])) {
          unset($request[$field_name]);
        }
      }
    }

    foreach (self::$cmrf_options as $field_name) {
      if (isset($request[$field_name])) {
        unset($request[$field_name]);
      }
    }

    foreach (self::$protected as $field_name) {
      if ($field_name == 'action' && ($request['action'] ?? NULL) != ($this->getAction()) ) {
        // Some actions may expect an 'action' key in the request parameters (e.g. "validate"),
        // so don't filter that out if 'action' and '$request['action]' are different
        continue;
      } elseif (isset($request[$field_name])) {
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

