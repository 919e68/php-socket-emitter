<?php
  require './lib/Socket.php';

  $socket = new SocketClient('127.0.0.1', 7000);
  $socket->emit('serverEvent', 'DataFromClient');

  $socket->on('clientEvent', function($data) {
    var_dump($data);
  });
