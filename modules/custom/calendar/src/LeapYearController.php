<?php

namespace Drupal\calendar;

use Symfony\Component\HttpFoundation\Response;


class LeapYearController {
  function view() {
    return new Response('Yep, this is a leap year!');
  }
}