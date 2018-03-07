<?php
  /* SocketConnectedClient Class */
  class SocketConnectedClient {
    public $id;
    public $ip;
    public $socket;
    public $events = [];

    public function __construct() {

    }

    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }

    public function emit($event, $data) {
      $sockMsg = "***emitter/***${event}***/emitter***";
      $sockMsg .= "***emitter-data/***$data***/emitter-data***";
      socket_write($this->socket, $sockMsg);
    }

    public function broadcast($event, $data) {
      $sockMsg = "***broadcast/***${event}***/broadcast***";
      $sockMsg .= "***broadcast-data/***$data***/broadcast-data***";
      socket_write($this->socket, $sockMsg);
    }
  }
