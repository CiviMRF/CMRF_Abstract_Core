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
use CMRF\Connection\CurlAuthX;

/**
 * @covers \CMRF\Connection\CurlAuthX
 * @covers \CMRF\Connection\AbstractCurlConnection
 */
final class CurlAuthXTest extends AbstractCurlConnectionTest {

  protected function createConnection(): AbstractCurlConnection {
    return new CurlAuthX($this->coreMock, 'test');
  }

  public function testGetType(): void {
    static::assertSame('curlauthx', $this->connection->getType());
  }

}
