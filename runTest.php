<?php

/**
 * Simple temporary file to test redirects.
 */

require_once 'vendor/autoload.php';

use Guzzle\Http\Client;
use League\CLImate\CLImate;

if (!isset($argv[1]) || !isset($argv[2])) {
  die("Usage: php runTest.php <url.csv> <domain>\n");
}

$file = fopen($argv[1], 'r');
$domain = $argv[2];
$client = new Client($domain, array('request.options' => array('exceptions' => FALSE)));
$summary = [];
$count = 0;
$index = 1;
$climate = new CLImate;

while (($url = fgetcsv($file, 1000, ","))) {
  if ($url[0] == '') {
    break;
  }
  if (0 === strpos($url[0], '#')) {
    continue;
  }

  $source = $domain . $url[0];
  $request = $client->head($source);
  $response = $request->send();
  $http_code = $response->getStatusCode();
  $redirect_count = $response->getRedirectCount();
  $redirect_url = $response->getEffectiveUrl();

  if (404 == $http_code) {
    $climate->yellow("{$index} {$http_code}: {$source}");
    $summary['404'][] = ['status_code' => $http_code, 'source' => $source];
    $count++;
  }
  elseif (0 < $redirect_count) {
    $climate->red("{$index} {$http_code} ({$redirect_count}): {$source}");
    $summary['redirect'][] = ['status_code' => $http_code, 'source' => $source];
    $count++;
  }
  else {
    $climate->green("{$index} {$http_code}: {$source}");
  }

  $index++;
}

if (!empty($summary['404'])) {
  $climate->flank('404 pages');
  foreach ($summary['404'] as $record) {
    $climate->out("{$record['status_code']}: {$record['source']}");
  }
}

if (!empty($summary['redirect'])) {
  $climate->flank('Redirects pages');
  foreach ($summary['redirect'] as $record) {
    $climate->out("{$record['status_code']}: {$record['source']}");
  }
}
