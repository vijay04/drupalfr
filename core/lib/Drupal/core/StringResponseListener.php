<?php

namespace Drupal\Core;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;

class StringResponseListener implements EventSubscriberInterface {
  public function onView(GetResponseForControllerResultEvent $event) {
    $response = $event->getControllerResult();
    if (is_string($response)) {
      $event->setResponse(new Response($response));
    }
  }
  public static function getSubscribedEvents() {
    return ['kernel.view' => 'onView'];
  }
}