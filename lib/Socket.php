<?php
  /* Socket Class */
  class Socket {

    public $host;
    public $port;
    public $sockRead = [];
    private $clients = [];
    private $clientCount = 0;
    private $clientIPCount = [];
    private $maxClients = 100;
    private $maxClientPerIP = 20;
    public $events = [];
    public $logging = true;

    public function __construct($host, $port) {
      $this->host = $host;
      $this->port = $port;
    }

    public function listen() {
      if (isset($this->sockRead[0])) {
        return false;
      }

      if (!$this->sockRead[0] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
        return false;
      }

      if (!socket_set_option($this->sockRead[0], SOL_SOCKET, SO_REUSEADDR, 1)) {
        socket_close($this->sockRead[0]);
        return false;
      }

      if (!socket_bind($this->sockRead[0], $this->host, $this->port)) {
        socket_close($this->sockRead[0]);
        return false;
      }

      if (!socket_listen($this->sockRead[0], 10)) {
        socket_close($this->sockRead[0]);
        return false;
      }

      if ($this->logging) {
        echo 'socket started listening on ' . $this->host . ':' . $this->port . PHP_EOL;
      }

      $write = null;
      $except = null;
      while (true) {
        $changed = $this->sockRead;
        if (socket_select($changed, $write, $except, 0) < 1) {
          continue;
        }

        foreach ($changed as $socketId => $socket) {
          // client socket updates
          if ($socketId != 0) {
            $data = '';
  					$bytes = @socket_recv($socket, $data, 4096, 0);
            $client = $this->clients[$socketId];

            if ($bytes === false) {
              $this->removeClient($socketId);
            } elseif ($bytes > 0) {
              $this->processSocketMessage($socketId, $data);
            } else {
              $this->removeClient($socketId);
            }

          // server socket change (client trying to connect)
          } else {
            $spawn = socket_accept($this->sockRead[0]);
            $socketIdFromAdd = $this->addClient($spawn);
          }
        }
      }
    }

    public function addClient($socket) {
      $this->clientCount++;
      socket_getpeername($socket, $clientIP);

      if (isset($this->clientIPCount[$clientIP])) {
        $this->clientIPCount[$clientIP]++;
      } else {
        $this->clientIPCount[$clientIP] = 1;
      }

  		$socketId = $this->getNextClientId();
      $this->sockRead[$socketId] = $socket;

      $socketClient = new SocketConnectedClient();
      $socketClient->id = $socketId;
      $socketClient->ip = $clientIP;
      $socketClient->socket = $socket;
      $socketClient->server = $this->sockRead[0];
  		$this->clients[$socketId] = $socketClient;

      // begin: on connection event
      if (array_key_exists('connect', $this->events)) {
        call_user_func($this->events['connect'], $this->clients[$socketId]);
      }
      // end: on connection event
      return $socketId;
    }

    public function removeClient($socketId) {
      $client = $this->clients[$socketId];
      $socket = $client->socket;
      socket_close($socket);

      // begin: on disconnect event
      if (array_key_exists('disconnect', $this->events)) {
        $socketClient = new SocketConnectedClient();
        $socketClient->id = $client->id;
        $socketClient->ip = $client->ip;
        call_user_func($this->events['disconnect'], $socketClient);
      }
      // end: on disconnect event

      $clientIP = $client->ip;
      if ($this->clientIPCount[$clientIP] > 1) {
        $this->clientIPCount[$clientIP]--;
      } else {
        unset($this->clientIPCount[$clientIP]);
      }

      $this->clientCount--;
      unset($this->clients[$socketId]);
      unset($this->sockRead[$socketId]);
    }

    private function getNextClientId() {
      $i = 1;
      while (isset($this->sockRead[$i])) {
        $i++;
      }
      return $i;
    }

    public function processSocketMessage($socketId, $data) {
      $dataArr = explode("***emitter-data/***", $data);
      if (count($dataArr) == 2) {
        $emitter = str_replace('***emitter/***', '', $dataArr[0]);
        $emitter = str_replace('***/emitter***', '', $emitter);
        $emitterData = str_replace('***/emitter-data***', '', $dataArr[1]);

        // begin: emitter
        $client = $this->clients[$socketId];
        if (array_key_exists($emitter, $client->events)) {
          call_user_func($client->events[$emitter], $emitterData);
        }
        // end: emitter
      }
    }

    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }
  }

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
  }

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

    public function emit($event, $data) {
      $sockMsg = "***emitter/***${event}***/emitter***";
      $sockMsg .= "***emitter-data/***$data***/emitter-data***";
      fwrite($this->server, $sockMsg);
    }

    public function processSocketMessage($data) {
      $dataArr = explode("***emitter-data/***", $data);
      if (count($dataArr) == 2) {
        $emitter = str_replace('***emitter/***', '', $dataArr[0]);
        $emitter = str_replace('***/emitter***', '', $emitter);
        $emitterData = str_replace('***/emitter-data***', '', $dataArr[1]);
        // begin: emitter
        if (array_key_exists($emitter, $this->events)) {
          call_user_func($this->events[$emitter], $emitterData);
        }
        // end: emitter
      }
    }

    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }

    public function listen() {
      $dataFromServer = '';
      while (!feof($this->server)) {
        $dataFromServer .= fread($this->server, 1);
        if (substr($dataFromServer, -19) == '***/emitter-data***') {
          $this->processSocketMessage($dataFromServer);
          $dataFromServer = '';
        }
      }
    }
  }
