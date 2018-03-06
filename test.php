<?php
  require './lib/Socket.php';


  $socket = new Socket('127.0.0.1', 7000);
  $socket->listen();
