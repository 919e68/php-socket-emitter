<?php
  require 'autoload.php';

  $socket = new RocketClient('127.0.0.1', 7000);
  $msg = 'Hello From Wilson';
  echo strlen($msg);
  $socket->emit('HelloEvent', 'Hello From Wilson');
  $socket->listen();