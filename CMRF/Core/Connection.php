<?php

/**
 * TODO
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Core;

abstract class Connection {

  protected $core         = NULL;
  protected $connector_id = NULL;

  /**
   * @param \CMRF\Core\Core $core
   * @param string $connector_id
   */
  public function __construct($core, $connector_id) {
    $this->core = $core;
    $this->connector_id = $connector_id;

    if ((new \ReflectionMethod($this, 'getSupportedApiVersions'))->getDeclaringClass()->getName() === __CLASS__) {
      trigger_error(
        sprintf(
          'Method "getSupportedApiVersions" in class "%s" will be made abstract in next major version',
          __CLASS__
        ),
        \E_USER_DEPRECATED
      );
    }
  }

  /**
   * @return string
   */
  abstract public function getType();

  /**
   * @return bool
   */
  abstract public function isReady();

  /**
   * @param \CMRF\Core\Call $call
   *
   * @return array|null The JSON decoded response or null on error.
   *
   * @throws \InvalidArgumentException
   */
  abstract public function executeCall(Call $call);

  /**
   * Default implementation returns only "3" for backward compatibility.
   *
   * @todo Make abstract in next major version.
   *
   * @return string[]
   */
  public function getSupportedApiVersions(): array {
    return ['3'];
  }

  /**
   * @return \CMRF\Core\Core
   */
  public function getCore() {
    return $this->core;
  }

  /**
   * @param \CMRF\Core\Call $call
   *
   * @return array
   */
  public function getAPI3Params($call) {
    $parameters = $call->getParameters();

    $options = $call->getOptions();
    if (!empty($options['limit'])) {
      $parameters['option.limit'] = (int) $options['limit'];
    }

    return $parameters;
  }

  public function queueCall(Call $call) {
    // TODO: override if async calls are possible
    $this->executeCall($call);
  }

  public function isCallSupported(Call $call): bool {
    return in_array($call->getApiVersion(), $this->getSupportedApiVersions(), TRUE);
  }

  /**
   * @return array
   */
  protected function getProfile() {
    return $this->core->getConnectionProfile($this->connector_id);
  }

}

