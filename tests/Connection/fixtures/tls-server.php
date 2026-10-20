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

// HTTPS endpoint for CertificateVerificationTest. It serves the certificate
// it is given, appends every request it receives to a log file and answers
// with an empty APIv3 result. It prints its port on the first line of stdout.
// Usage: php tls-server.php <certificate-and-key.pem> <request-log>

declare(strict_types=1);

[, $certificate_file, $request_log] = $argv;

$context = stream_context_create(['ssl' => ['local_cert' => $certificate_file]]);
$server = stream_socket_server('tls://127.0.0.1:0', $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
if (FALSE === $server) {
  fwrite(STDERR, $errstr . PHP_EOL);
  exit(1);
}

$address = stream_socket_get_name($server, FALSE);
echo substr($address, strrpos($address, ':') + 1), PHP_EOL;

$body = '{"is_error":0,"values":[]}';
while (TRUE) {
  $connection = @stream_socket_accept($server, 60);
  if (FALSE === $connection) {
    continue;
  }

  $request = '';
  while (FALSE === strpos($request, "\r\n\r\n") && !feof($connection)) {
    $request .= fread($connection, 8192);
  }
  // A client that rejects the certificate aborts the handshake and sends
  // nothing, so there is no request to record.
  if ('' === $request) {
    fclose($connection);
    continue;
  }
  [$head, $content] = explode("\r\n\r\n", $request, 2) + [1 => ''];
  if (preg_match('/^Content-Length:\s*(\d+)/mi', $head, $matches)) {
    while (strlen($content) < (int) $matches[1] && !feof($connection)) {
      $content .= fread($connection, 8192);
    }
  }
  file_put_contents($request_log, $head . "\r\n\r\n" . $content, FILE_APPEND);

  fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
  fclose($connection);
}
