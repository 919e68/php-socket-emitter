<?php
  class Socket {

    public $host;
    public $port;
    public $sockRead = [];
    private $clients = [];
    private $clientCount = 0;
    private $clientIPCount = [];
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
              $this->message($socketId, $data);
            } else {
              $this->removeClient($socketId);
            }

          // server socket change (client trying to connect)
          } else {
            $spawn = socket_accept($this->sockRead[0]);
            $socketIdFromAdd = $this->createClient($spawn);
          }
        }
      }
    }

    private function generateId() {
      $i = 1;
      while (isset($this->sockRead[$i])) {
        $i++;
      }
      return $i;
    }

    public function createClient($socket) {
      $this->clientCount++;
      socket_getpeername($socket, $clientIP);

      if (isset($this->clientIPCount[$clientIP])) {
        $this->clientIPCount[$clientIP]++;
      } else {
        $this->clientIPCount[$clientIP] = 1;
      }

  		$socketId = $this->generateId();
      $this->sockRead[$socketId] = $socket;

  		$this->clients[$socketId] = [
        'id'     => $socketId,
        'ip'     => $clientIP,
        'socket' => $socket
      ];

      // begin: on connection event
      if (array_key_exists('connect', $this->events)) {
        call_user_func($this->events['connect'], $socketId);
      }
      // end: on connection event
      return $socketId;
    }

    public function removeClient($socketId) {
      $client = $this->clients[$socketId];
      $socket = $client['socket'];
      socket_close($socket);

      // begin: on disconnect event
      if (array_key_exists('disconnect', $this->events)) {
        call_user_func($this->events['disconnect'], $socketId);
      }
      // end: on disconnect event

      $clientIP = $client['ip'];
      if ($this->clientIPCount[$clientIP] > 1) {
        $this->clientIPCount[$clientIP]--;
      } else {
        unset($this->clientIPCount[$clientIP]);
      }

      $this->clientCount--;
      unset($this->clients[$socketId]);
      unset($this->sockRead[$socketId]);
    }

    public function message($socketSenderId, $data) {
      if (array_key_exists('message', $this->events)) {
        call_user_func($this->events['message'], $data, $socketSenderId);
      }
      // $type = substr($data, 0, 16);
      //
      // if (strpos($type, 'emitter') !== false) {
      //   $dataArr = explode("***emitter-data/***", $data);
      //   if (count($dataArr) == 2) {
      //     $emitter = str_replace('***emitter/***', '', $dataArr[0]);
      //     $emitter = str_replace('***/emitter***', '', $emitter);
      //     $emitterData = str_replace('***/emitter-data***', '', $dataArr[1]);
      //
      //     $client = $this->clients[$socketSenderId];
      //     if (array_key_exists($emitter, $client->events)) {
      //       call_user_func($client->events[$emitter], $emitterData);
      //     }
      //   }
      //
      // } elseif (strpos($type, 'broadcast') !== false) {
      //   $dataArr = explode("***broadcast-data/***", $data);
      //   if (count($dataArr) == 2) {
      //     $emitter = str_replace('***broadcast/***', '', $dataArr[0]);
      //     $emitter = str_replace('***/broadcast***', '', $emitter);
      //     $emitterData = str_replace('***/broadcast-data***', '', $dataArr[1]);
      //
      //     foreach ($sockRead as $socketId => $socket) {
      //       if ($socketId != 0 && $socketId != $socketSenderId) {
      //         $client = $this->clients[$socketId];
      //         if (array_key_exists($emitter, $client->events)) {
      //           call_user_func($client->events[$emitter], $emitterData);
      //         }
      //       }
      //     }
      //   }
      // }
    }

    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }
  }
