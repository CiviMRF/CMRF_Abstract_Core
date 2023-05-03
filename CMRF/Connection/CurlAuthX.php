<?php

/**
 * Remote CiviCRM connection based on CURL
 * Uses the new CiviCRM auth extension. Authentication
 * is done with X-Civi-Auth em X-Civi-Key headers
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Connection;

use CMRF\Core\Call;

class CurlAuthX extends AbstractCurlConnection {

  /**
   * @inheritDoc
   */
  public function getType() {
    return 'curlauthx';
  }

  /**
   * @inheritDoc
   */
  protected function createCurl(Call $call) {
    $curl = parent::createCurl($call);
    $profile = $this->getProfile();
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      'X-Requested-With: XMLHttpRequest',
      "X-Civi-Auth: Bearer {$profile['api_key']}",
      "X-Civi-Key: {$profile['site_key']}",
    ]);

    return $curl;
  }

}
