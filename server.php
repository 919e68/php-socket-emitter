<?php
  require 'autoload.php';

  $socket = new Socket('127.0.0.1', 7000);

  $socket->on('connect', function($id) {
    echo 'client connected ' . $id . PHP_EOL;
  });

  $socket->on('message', function($data, $id) {
    echo 'message from (' . $id .'): ' . $data;
  });

  $socket->on('disconnect', function($id) {
    echo 'client disconnected ' . $id . PHP_EOL;
  });

  $socket->listen();
