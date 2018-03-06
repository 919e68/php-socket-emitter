<?php
  require './lib/Socket.php';

  $socket = new Socket('127.0.0.1', 7000);

  $socket->on('connect', function($client) {
    echo 'client connected id=' . $client->id . PHP_EOL;

    $client->on('serverEvent', function($data) {
      var_dump($data);
    });

    $client->emit('clientEvent', 'DataFromServer');
  });

  $socket->on('disconnect', function($client) {
    echo 'client disconnected id=' . $client->id . PHP_EOL;
  });

  $socket->listen();
