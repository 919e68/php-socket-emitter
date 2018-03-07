<?php


  /* SocketClient Class */
  class SocketClient {
    public $host;
    public $port;
    public $server;
    public $events = [];
    private $listening = false;

    public function __construct($host, $port) {
      $this->host = $host;
      $this->port = $port;
      $this->server = fsockopen($host, $port);
    }

    public function send($data) {
      if (!$this->server) {
        socket_close($this->server);
        return false;
      }

      fwrite($this->server, $data);
    }


    // public function emit($event, $data) {
    //   $sockMsg = "***emitter/***${event}***/emitter***";
    //   $sockMsg .= "***emitter-data/***$data***/emitter-data***";
    //   fwrite($this->server, $sockMsg);
    // }
    //
    // public function processSocketMessage($data) {
    //   $dataArr = explode("***emitter-data/***", $data);
    //   if (count($dataArr) == 2) {
    //     $emitter = str_replace('***emitter/***', '', $dataArr[0]);
    //     $emitter = str_replace('***/emitter***', '', $emitter);
    //     $emitterData = str_replace('***/emitter-data***', '', $dataArr[1]);
    //     // begin: emitter
    //     if (array_key_exists($emitter, $this->events)) {
    //       call_user_func($this->events[$emitter], $emitterData);
    //     }
    //     // end: emitter
    //   }
    // }
    //
    // public function on($event, $callback) {
    //   $this->events[$event] = $callback;
    // }
    //
    public function listen() {
      $bufferFromServer = '';
      while (!feof($this->server)) {
        $bufferFromServer .= fread($this->server, 1);
        // if (substr($bufferFromServer, -19) == '***/emitter-data***') {
        //   $this->onMessage($bufferFromServer);
        //   $bufferFromServer = '';
        // }
        <message><type></type><name></name><data></data></message>
      }

      if ()
    }
  }
