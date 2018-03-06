<?php
  class Socket {
    
    public $host;
    public $port;
    public $socket;
    
    public __construct($host, $port) {
      $this->host = $host;
      $this->port = $port;
    }
    
    public listen() {
      $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
      if ($this->socket) {
        $bind = socket_bind($socket, $this->host, $this->port);
        if ($bind) {
          $listen = socket_listen($socket, 3);
          $spawn = socket_accept($socket);
          if ($spawn) {
            
          }
        }
      }
    }
  }
