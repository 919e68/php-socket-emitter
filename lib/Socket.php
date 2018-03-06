<?php
  class Socket {

    public $host;
    public $port;
    public $socket;
    public $sockRead = [];
    public $clients = [];
    public $clientCount = 0;
    public $clientIPCount = [];
    public $maxClients = 100;
    public $maxClientPerIP = 20;
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
          // client socket messages
          if ($socketId != 0) {
            $data = '';
  					$bytes = @socket_recv($socket, $data, 4096, 0);
            $client = $this->clients[$socketId];

            if ($bytes === false) {
              $this->removeClient($socketId);
              if ($this->logging) {
                echo 'client disconnected: ip=' . $client['ip']  . ', id=' . $client['id'] . PHP_EOL;
              }
              continue;
            }

            echo 'message from client: ' . $data . PHP_EOL;

          // server socket change (client trying to connect)
          } else {
            $spawn = socket_accept($this->sockRead[0]);
            $socketIdFromAdd = $this->addClient($spawn);
            if ($spawn && $this->logging) {
              socket_write($spawn, 'msg from server');
              socket_getpeername($spawn, $ip);
              echo "client connected: ip={$ip}, id={$socketIdFromAdd}" . PHP_EOL;
            }
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
  		$this->clients[$socketId] = [
        'id'     => $socketId,
        'ip'     => $clientIP,
        'socket' => $socket
      ];

      return $socketId;
    }

    public function removeClient($socketId) {
      $client = $this->clients[$socketId];
      $socket = $client['socket'];
      socket_close($socket);

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

    public function getNextClientId() {
      $i = 1;
      while (isset($this->sockRead[$i])) {
        $i++;
      }
      return $i;
    }
  }
