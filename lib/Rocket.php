<?php
  class Rocket {

    public $ip;
    public $host;
    public $Socket;
    public $events = [];

    public function __construct($ip, $host) {
      $this->host = $host;
      $this->port = $port;

      $this->Socket = new Socket($ip, $host);
    }

    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }

    public function listen() {
      $this->Socket->listen();
    }
  }
