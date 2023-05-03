<?php

/**
 * Remote CiviCRM connection based on CURL
 *
 * @author Björn Endres, SYSTOPIA (endres@systopia.de)
 */

namespace CMRF\Connection;

use CMRF\Core\Call;

class Curl extends AbstractCurlConnection {

  /**
   * @inheritDoc
   */
  public function getType() {
    return 'curl';
  }

  /**
   * @inheritDoc
   */
  protected function createPostData(Call $call): array {
    $post_data = parent::createPostData($call);
    $profile = $this->getProfile();
    $post_data['api_key'] = $profile['api_key'];
    $post_data['key'] = $profile['site_key'];

    return $post_data;
  }

}
