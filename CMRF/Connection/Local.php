<?php

/**
 * TODO
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Connection;

use CMRF\Core\Call;
use CMRF\Core\Connection;

class Local extends Connection {

  /**
   * @inheritDoc
   */
  public function getSupportedApiVersions(): array {
    return ['3', '4'];
  }

  /**
   * @inheritDoc
   */
  public function getType() {
    return 'local';
  }

  /**
   * @inheritDoc
   */
  public function isReady() {
    return function_exists('civicrm_api3');
  }

  /**
   * @inheritDoc
   *
   * Execute the given call synchronously.
   */
  public function executeCall(Call $call) {
    if (!$this->isCallSupported($call)) {
      throw new \InvalidArgumentException(sprintf('Unsupported API version "%s"', $call->getApiVersion()));
    }

    try {
      if ('3' === $call->getApiVersion()) {
        $reply = $this->executeCallV3($call);
      } else {
          $reply = $this->executeCallV4($call);
      }
      $call->setReply($reply);

      return $reply;
    } catch (\Exception $e) {
      $call->setStatus(Call::STATUS_FAILED, $e->getMessage());

      return NULL;
    }
  }

  private function executeCallV3(Call $call): array {
    $reply = civicrm_api3($call->getEntity(), $call->getAction(), $this->getAPI3Params($call));
    // Hack from CiviCRM core to make the reply behave similar as the remote API.
    // Meaning that a scalar value (a number, string etc.) should be wrapped in an array by the key result.
    if (is_scalar($reply)) {
      if (!$reply) {
        $reply = 0;
      }
      $reply = [
        'is_error' => 0,
        'result' => $reply,
      ];
    }

    return $reply;
  }

  private function executeCallV4(Call $call): array {
    $result = civicrm_api4($call->getEntity(), $call->getAction(), $call->getParameters());

    return $result->getArrayCopy();
  }

}

