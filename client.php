<?php
  // require './lib/Socket.php';
  //
  // $socket = new SocketClient('127.0.0.1', 7000);
  // $socket->send('Hello');
  // // $socket->on('message', function($data) {
  // //   echo 'data received: ' . $data . PHP_EOL;
  // // });
  //
  // // $server->listen();

    $host = '127.0.0.1';
    $port = 7000;

    $socket = @fsockopen($host, $port, $errno, $error);
    fwrite($socket, 'Hello From Client');


    // function reconnect() {
    //   global $socket;
    //   global $host;
    //   global $port;
    //
    //
    //   while (true) {
    //     var_dump($socket);
    //     if (!$socket) {
    //       $socket = @fsockopen($host, $port, $errno, $error);
    //     } else {
    //
    //     }
    //     sleep(1);
    //   }
    // }
