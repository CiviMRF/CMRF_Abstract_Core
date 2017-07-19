<?php
/**
 * Created by PhpStorm.
 * User: enno
 * Date: 18.07.17
 * Time: 14:53
 */

namespace CMRF\PersistenceLayer;


class CallFactory {

  private $delegated_constructor;
  private $delegated_loader;

  public function __construct(callable $constructor, callable $loader) {
    $this->delegated_constructor=$constructor;
    $this->delegated_loader=$loader;
  }

  protected function call_construct($connector_id, $core, $entity, $action, $parameters, $options, $callback) {
    return call_user_func($this->delegated_constructor,array($connector_id,$core,$entity,$action,$parameters,$options,$callback,$this));
  }

  protected function call_load($connector_id,$core,$record) {
    return call_user_func($this->delegated_loader,array($connector_id,$core,$record,$this));
  }

  /** @return \CMRF\Core\Call */
  public function createOrFetch($connector_id,$core,$entity,$action,$parameters,$options,$callback) {
    return $this->call_construct($connector_id,$core,$entity,$action,$parameters,$options,$callback);
  }

  public function update(\CMRF\Core\Call $call) {
    //basic implementation does not handle persistence.
  }

  public function purgeCachedCalls() {
    //basic implementation does not handle persistence. No problem here.
  }

  public function loadCall($call_id,$core) {
    return null;
  }

  public function findCall($options,$core) {
    return null;
  }
}
