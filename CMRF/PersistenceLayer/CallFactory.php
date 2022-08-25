<?php
/**
 * Created by PhpStorm.
 * User: enno
 * Date: 18.07.17
 * Time: 14:53
 */

namespace CMRF\PersistenceLayer;

use CMRF\Core\Call;
use CMRF\Core\Core;

class CallFactory {

  private $delegated_constructor;
  private $delegated_loader;

  public function __construct(callable $constructor, callable $loader) {
    $this->delegated_constructor = $constructor;
    $this->delegated_loader = $loader;
  }

  protected function call_construct($connector_id, $core, $entity, $action, $parameters, $options,
    $callback/*, string $api_version = '3'*/
  ) {
    if (7 === func_num_args()) {
      trigger_error(
        sprintf('Calling %s without API version is deprecated', __METHOD__),
        E_USER_DEPRECATED
      );
      $api_version = '3';
    } else {
      $api_version = func_get_arg(7);
    }

    return call_user_func_array(
      $this->delegated_constructor, [
        $connector_id,
        $core,
        $entity,
        $action,
        $parameters,
        $options,
        $callback,
        $this,
        $api_version,
      ]
    );
  }

  protected function call_load($connector_id, $core, $record) {
    return call_user_func_array($this->delegated_loader, [$connector_id, $core, $record, $this]);
  }

  /**
   * @return \CMRF\Core\Call
   */
  public function createOrFetch($connector_id, $core, $entity, $action, $parameters, $options,
    $callback/*, string $api_version = '3'*/
  ) {
    if (func_num_args() < 8) {
      $api_version = '3';
    } else {
      $api_version = func_get_arg(7) ?? '3';
    }

    return $this->call_construct($connector_id, $core, $entity, $action, $parameters, $options, $callback, $api_version);
  }

  public function createOrFetchV3(string $connector_id, Core $core, string $entity, string $action, array $parameters,
    array $options, $callback): Call {
    return $this->createOrFetch($connector_id, $core, $entity, $action, $parameters, $options, $callback, '3');
  }

  public function createOrFetchV4(string $connector_id, Core $core, string $entity, string $action, array $parameters,
    array $options, $callback): Call {
    return $this->createOrFetch($connector_id, $core, $entity, $action, $parameters, $options, $callback, '4');
  }

  public function update(Call $call) {
    //basic implementation does not handle persistence.
  }

  public function purgeCachedCalls() {
    //basic implementation does not handle persistence. No problem here.
  }

  /**
   * Returns the queued calls which are ready for processing.
   *
   * @return array
   *   The array consists of the call ids
   */
  public function getQueuedCallIds() {
    // basic implementation does not handle persistence. No problem here.
    return array();
  }

  public function loadCall($call_id,$core) {
    return null;
  }

  public function findCall($options,$core) {
    return null;
  }

}
