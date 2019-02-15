<?php

namespace Drupal\node\Controller;

use Symfony\Component\HttpFoundation\Response;


class NodeListing {
  public function index() {
    return new Response('listing');
  }
}