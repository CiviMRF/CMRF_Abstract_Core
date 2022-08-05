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

namespace CMRF\Tests\Connection;

use CMRF\Connection\AbstractCurlConnection;
use CMRF\Core\Call;
use CMRF\Core\Core;
use CMRF\PersistenceLayer\CallFactory;
use CMRF\Tests\Core\TestCall;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractCurlConnectionTest extends TestCase {

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject&\CMRF\PersistenceLayer\CallFactory
   */
  protected MockObject $call_factory_mock;

  protected AbstractCurlConnection $connection;

  protected array $connection_profile = [];

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject&\CMRF\Core\Core
   */
  protected MockObject $coreMock;

  protected function setUp(): void {
    parent::setUp();
    $this->call_factory_mock = $this->createMock(CallFactory::class);
    $this->coreMock = $this->createMock(Core::class);
    $this->coreMock->method('getConnectionProfile')->with('test')
      ->willReturnCallback(fn() => $this->connection_profile);

    $this->connection_profile += [
      'url' => getenv('CIVICRM_APIv3_URL'),
      'urlV4' => getenv('CIVICRM_APIv4_URL'),
      'api_key' => getenv('CIVICRM_API_KEY'),
      'site_key' => getenv('CIVICRM_SITE_KEY'),
    ];

    if (empty($this->connection_profile['api_key'])) {
      static::markTestSkipped('No CiviCRM API key specified in environment variable CIVICRM_API_KEY');
    }

    if (empty($this->connection_profile['site_key'])) {
      static::markTestSkipped('No CiviCRM site key specified in environment variable CIVICRM_SITE_KEY');
    }

    $this->connection = $this->createConnection();
    if (!$this->connection->isReady()) {
      static::markTestSkipped('curl extension is not loaded');
    }
  }

  public function testGetSupportedApiVersions(): void {
    static::assertSame(['3', '4'], $this->connection->getSupportedApiVersions());
  }

  public function testV3(): void {
    if (empty($this->connection_profile['url'])) {
      static::markTestSkipped('No CiviCRM APIv3 URL specified in environment variable CIVICRM_APIv3_URL');
    }

    $call = new TestCall($this->coreMock, 'test', $this->call_factory_mock, '3', 'Contact', 'get', ['id' => 1]);
    static::assertTrue($this->connection->isCallSupported($call));
    $reply = $this->connection->executeCall($call);
    static::assertIsArray($reply);
    static::assertArrayHasKey('values', $reply);
    static::assertCount(1, $reply['values']);
    static::assertSame($reply, $call->getReply());
    static::assertSame(Call::STATUS_DONE, $call->getStatus());
    static::assertNull($call->getErrorMessage());
    static::assertNull($call->getErrorCode());
  }

  public function testV3Invalid(): void {
    if (empty($this->connection_profile['url'])) {
      static::markTestSkipped('No CiviCRM APIv3 URL specified in environment variable CIVICRM_APIv3_URL');
    }

    $call = new TestCall($this->coreMock, 'test', $this->call_factory_mock, '3', 'Invalid', 'get', []);
    static::assertTrue($this->connection->isCallSupported($call));
    $reply = $this->connection->executeCall($call);
    static::assertEquals([
      'error_code' => 'not-found',
      'entity' => 'Invalid',
      'action' => 'get',
      'is_error' => 1,
      'error_message' => 'API (Invalid, get) does not exist (join the API team and implement it!)',
    ], $reply);
    static::assertSame($reply, $call->getReply());
    static::assertSame(Call::STATUS_FAILED, $call->getStatus());
    static::assertNull($call->getErrorMessage());
    static::assertNull($call->getErrorCode());
  }

  public function testV4(): void {
    if (empty($this->connection_profile['urlV4'])) {
      static::markTestSkipped('No CiviCRM APIv4 URL specified in environment variable CIVICRM_APIv4_URL');
    }

    $call = new TestCall($this->coreMock, 'test', $this->call_factory_mock, '4', 'Contact', 'get', [
      'where' => [['id', '=', '1']],
    ]);
    static::assertTrue($this->connection->isCallSupported($call));
    $reply = $this->connection->executeCall($call);
    static::assertIsArray($reply);
    static::assertArrayHasKey('values', $reply);
    static::assertCount(1, $reply['values']);
    static::assertSame($reply, $call->getReply());
    static::assertSame(Call::STATUS_DONE, $call->getStatus());
    static::assertNull($call->getErrorMessage());
    static::assertNull($call->getErrorCode());
  }

  public function testV4Invalid(): void {
    if (empty($this->connection_profile['urlV4'])) {
      static::markTestSkipped('No CiviCRM APIv4 URL specified in environment variable CIVICRM_APIv4_URL');
    }

    $call = new TestCall($this->coreMock, 'test', $this->call_factory_mock, '4', 'Invalid', 'get', []);
    static::assertTrue($this->connection->isCallSupported($call));
    $reply = $this->connection->executeCall($call);
    static::assertEquals([
      'error_code' => 0,
      'error_message' => 'API (Invalid, get) does not exist (join the API team and implement it!)',
    ], $reply);
    static::assertSame($reply, $call->getReply());
    static::assertSame(Call::STATUS_FAILED, $call->getStatus());
    static::assertNull($call->getErrorMessage());
    static::assertNull($call->getErrorCode());
  }

  public function testV4InvalidParameters(): void {
    if (empty($this->connection_profile['urlV4'])) {
      static::markTestSkipped('No CiviCRM APIv4 URL specified in environment variable CIVICRM_APIv4_URL');
    }

    $call = new TestCall($this->coreMock, 'test', $this->call_factory_mock, '4', 'Contact', 'get', [
      'where' => ['invalid'],
    ]);
    static::assertTrue($this->connection->isCallSupported($call));
    $reply = $this->connection->executeCall($call);
    static::assertNull($reply);
    static::assertSame($reply, $call->getReply());
    static::assertSame(Call::STATUS_FAILED, $call->getStatus());
    static::assertSame('JSON error: Syntax error', $call->getErrorMessage());
    static::assertSame(4, $call->getErrorCode());
  }

  abstract protected function createConnection(): AbstractCurlConnection;

}
