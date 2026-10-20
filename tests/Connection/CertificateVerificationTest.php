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

use CMRF\Connection\Curl;
use CMRF\Core\Call;
use CMRF\Core\Core;
use CMRF\PersistenceLayer\CallFactory;
use CMRF\Tests\Core\TestCall;
use PHPUnit\Framework\TestCase;

/**
 * Runs real calls against a local HTTPS endpoint with a self-signed
 * certificate, so the outcome does not depend on a CiviCRM installation.
 *
 * @covers \CMRF\Connection\AbstractCurlConnection
 * @covers \CMRF\Connection\Curl
 */
final class CertificateVerificationTest extends TestCase {

  private array $profile = [];

  /**
   * @var resource|null
   */
  private $server;

  private string $certificateFile = '';

  private string $requestLog = '';

  protected function setUp(): void {
    parent::setUp();
    foreach (['curl', 'openssl'] as $extension) {
      if (!extension_loaded($extension)) {
        static::markTestSkipped(sprintf('The %s extension is not loaded', $extension));
      }
    }

    $this->certificateFile = (string) tempnam(sys_get_temp_dir(), 'cmrf-cert-');
    $this->requestLog = (string) tempnam(sys_get_temp_dir(), 'cmrf-request-');
    $this->writeSelfSignedCertificate($this->certificateFile);

    $this->server = proc_open(
      [PHP_BINARY, __DIR__ . '/fixtures/tls-server.php', $this->certificateFile, $this->requestLog],
      [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes
    );
    $port = trim((string) fgets($pipes[1]));
    if (!ctype_digit($port)) {
      static::fail('The TLS server did not start: ' . stream_get_contents($pipes[2]));
    }

    $this->profile = [
      'url' => sprintf('https://127.0.0.1:%s/', $port),
      'api_key' => 'test-api-key',
      'site_key' => 'test-site-key',
    ];
  }

  protected function tearDown(): void {
    if (is_resource($this->server)) {
      proc_terminate($this->server);
      proc_close($this->server);
    }
    @unlink($this->certificateFile);
    @unlink($this->requestLog);
    parent::tearDown();
  }

  public function testUntrustedCertificateIsRejectedByDefault(): void {
    $call = $this->createCall();
    $reply = $this->createConnection()->executeCall($call);

    static::assertNull($reply);
    static::assertSame(Call::STATUS_FAILED, $call->getStatus());
    // The handshake fails before any request is sent, so the keys never
    // leave the client.
    static::assertSame('', (string) file_get_contents($this->requestLog));
  }

  public function testUntrustedCertificateIsAcceptedWhenTheProfileOptsOut(): void {
    $this->profile['ssl_verify'] = FALSE;
    $call = $this->createCall();
    $reply = $this->createConnection()->executeCall($call);

    static::assertSame(['is_error' => 0, 'values' => []], $reply);
    static::assertSame(Call::STATUS_DONE, $call->getStatus());
    static::assertStringContainsString('api_key=test-api-key', (string) file_get_contents($this->requestLog));
  }

  private function createConnection(): Curl {
    $core = $this->createMock(Core::class);
    $core->method('getConnectionProfile')->with('test')
      ->willReturnCallback(fn() => $this->profile);

    return new Curl($core, 'test');
  }

  private function createCall(): TestCall {
    return new TestCall($this->createMock(Core::class), 'test', $this->createMock(CallFactory::class), '3', 'Contact', 'get', []);
  }

  private function writeSelfSignedCertificate(string $file): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $request = openssl_csr_new(['commonName' => '127.0.0.1'], $key, ['digest_alg' => 'sha256']);
    $certificate = openssl_csr_sign($request, NULL, $key, 1, ['digest_alg' => 'sha256']);
    openssl_x509_export($certificate, $certificate_pem);
    openssl_pkey_export($key, $key_pem);
    file_put_contents($file, $certificate_pem . $key_pem);
  }

}
