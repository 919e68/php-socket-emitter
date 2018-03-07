<?php
  require './lib/Socket.php';

  $server = new SocketClient('127.0.0.1', 7000);
  $server->on('message', function($data) {
    echo 'data received: ' . $data . PHP_EOL;
  });

  $server->listen();
