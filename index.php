<?php

$autoloader = require __DIR__ . '/vendor/autoload.php';

use Drupal\Core\DrupalKernal;
use Symfony\Component\HttpFoundation\Request;

$kernel = new DrupalKernal($autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
