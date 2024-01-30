<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation in version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace CMRF\Connection;

use CMRF\Core\Call;
use CMRF\Core\Connection;

abstract class AbstractCurlConnection extends Connection {

  /**
   * @inheritDoc
   */
  public function getSupportedApiVersions(): array {
    return ['3', '4'];
  }

  /**
   * @inheritDoc
   */
  public function isReady() {
    return extension_loaded('curl');
  }

  /**
   * @inheritDoc
   */
  public function executeCall(Call $call) {
    $curl = $this->createCurl($call);

    $response = curl_exec($curl);
    if (FALSE === $response || '' !== curl_error($curl)){
      $call->setStatus(Call::STATUS_FAILED, curl_error($curl), curl_errno($curl));
      return NULL;
    } else {
      $reply = json_decode($response, TRUE);
      if (!is_array($reply)) {
        $call->setStatus(Call::STATUS_FAILED, sprintf('JSON error: %s', json_last_error_msg()), json_last_error());
        return NULL;
      } else {
        $status = Call::STATUS_DONE;
        if (isset($reply['is_error']) && $reply['is_error'] !== 0 || isset($reply['error_code'])) {
          $status = Call::STATUS_FAILED;
        }
        $call->setReply($reply, $status);
        return $reply;
      }
    }
  }

  /**
   * @param \CMRF\Core\Call $call
   *
   * @return resource
   */
  protected function createCurl(Call $call) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, self::postDataToString($this->createPostData($call)));
    curl_setopt($curl, CURLOPT_URL, $this->getUrl($call));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

    // @todo Make disabling certificate verification optional
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    return $curl;
  }

  /**
   * @throws \InvalidArgumentException
   *   If API version is unsupported.
   */
  protected function createPostData(Call $call): array {
    switch ($call->getApiVersion()) {
      case '3':
        return $this->createPostDataV3($call);

      case '4':
        return $this->createPostDataV4($call);

      default:
        throw new \InvalidArgumentException(sprintf('Unsupported API version "%s"', $call->getApiVersion()));
    }
  }

  protected function createPostDataV3(Call $call): array {
    return [
      'entity' => $call->getEntity(),
      'action' => $call->getAction(),
      'version' => $call->getApiVersion(),
      'json' => urlencode(json_encode($this->getAPI3Params($call))),
    ];
  }

  protected function createPostDataV4(Call $call): array {
    return [
      'params' => urlencode(json_encode($call->getParameters())),
    ];
  }

  /**
   * @throws \InvalidArgumentException
   *   If API version is unsupported.
   * @throws \RuntimeException
   *   If API endpoint is not specified in profile.
   */
  protected function getUrl(Call $call): string {
    $profile = $this->getProfile();

    if ('3' === $call->getApiVersion()) {
      if (!isset($profile['url'])) {
        throw new \RuntimeException('No APIv3 endpoint specified');
      }

      return $profile['url'];
    }

    if ('4' === $call->getApiVersion()) {
      if (!isset($profile['urlV4'])) {
        throw new \RuntimeException('No APIv4 endpoint specified');
      }

      return sprintf('%s/%s/%s', $profile['urlV4'], $call->getEntity(), $call->getAction());
    }

    throw new \InvalidArgumentException(sprintf('Unsupported API version "%s"', $call->getApiVersion()));
  }

  private static function postDataToString(array $postData): string {
    $str = '';
    foreach ($postData as $key => $value) {
      $str .= sprintf('%s=%s&', $key, $value);
    }

    return $str;
  }
}
