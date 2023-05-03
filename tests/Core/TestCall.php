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

namespace CMRF\Tests\Core;

use CMRF\Core\AbstractCall;
use CMRF\Core\Core;
use CMRF\PersistenceLayer\CallFactory;

final class TestCall extends AbstractCall {

  private string $apiVersion;

  private string $entity;

  private string $action;

  private array $parameters;

  private array $options;

  private string $status = self::STATUS_INIT;

  private ?string $cached_until = NULL;

  private array $metadata = [];

  private ?array $reply = NULL;

  private ?string $error_message = NULL;

  private $error_code = NULL;

  public function __construct(Core $core, string $connector_id, CallFactory $factory, string $apiVersion, string
  $entity, string $action, array $parameters, array $options = []) {
    parent::__construct($core, $connector_id, $factory, NULL);
    $this->apiVersion = $apiVersion;
    $this->entity = $entity;
    $this->action = $action;
    $this->parameters = $parameters;
    $this->options = $options;
  }

  public function getApiVersion(): string {
    return $this->apiVersion;
  }

  public function getEntity() {
    return $this->entity;
  }

  public function getAction() {
    return $this->action;
  }

  public function getParameters() {
    return $this->parameters;
  }

  public function getRequest() {
    return $this->parameters;
  }

  public function getOptions() {
    return $this->options;
  }

  public function getStatus() {
    return $this->status;
  }

  public function getCachedUntil() {
    return $this->cached_until;
  }

  public function getMetadata() {
    return $this->metadata;
  }

  public function setStatus($status, $error_message, $error_code) {
    $this->status = $status;
    $this->error_message = $error_message;
    $this->error_code = $error_code;
  }

  public function getReply() {
    return $this->reply;
  }

  public function setReply($data, $newstatus) {
    $this->reply = $data;
    $this->status = $newstatus;
  }

  public function triggerCallback() {
  }

  public function getErrorMessage(): ?string {
    return $this->error_message;
  }

  public function getErrorCode() {
    return $this->error_code;
  }

}
