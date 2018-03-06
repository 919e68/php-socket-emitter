<?php
  class Socket {

    const OPCODE_CONTINUATION = 0;
  	const OPCODE_TEXT =         1;
  	const OPCODE_BINARY =       2;
  	const OPCODE_CLOSE =        8;
  	const OPCODE_PING =         9;
  	const OPCODE_PONG =         10;

    const READY_STATE_CONNECTING = 0;
  	const READY_STATE_OPEN =       1;
  	const READY_STATE_CLOSING =    2;
  	const READY_STATE_CLOSED =     3;

    public $host;
    public $port;
    public $socket;
    public $logging = true;
    public $sockRead = [];
    public $clients = [];

    public function __construct($host, $port) {
      $this->host = $host;
      $this->port = $port;
    }

    public function listen() {
      $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
      if ($this->socket) {
        $bind = socket_bind($this->socket, $this->host, $this->port);
        $listen = socket_listen($this->socket, 3);
        $this->clients[] = $this->socket;

        if ($listen) {
          if ($this->logging) {
            echo 'socket started listening on ' . $this->host . ':' . $this->port . PHP_EOL;
          }
        }

        while (true) {
          $read = $this->clients;
          $write = NULL;
          $except = NULL;
          if (socket_select($read, $write, $except, 0) < 1) {
            continue;
          }

          if (in_array($this->socket, $read)) {
            $spawn = socket_accept($this->socket);
            $this->clients[] = $spawn;

            socket_write($spawn, "no noobs, but ill make an exception :)\n".
            "There are ".(count($this->clients) - 1)." client(s) connected to the server\n");

            socket_getpeername($spawn, $ip);

            if ($this->logging) {
              echo "client connected: {$ip}" . PHP_EOL;
            }

            $key = array_search($this->socket, $read);
            unset($read[$key]);
          }

          // loop through all the clients that have data to read from
          foreach ($read as $read_sock) {
            $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

            // check if the client is disconnected
            if ($data === false) {
              // remove client for $clients array
              $key = array_search($read_sock, $this->clients);
              unset($this->clients[$key]);
              if ($this->logging) {
                echo 'client disconnected' . PHP_EOL;
              }
              continue;
            }

            // trim off the trailing/beginning white spaces
            $data = trim($data);

            // check if there is any data after trimming off the spaces
            if (!empty($data)) {
              // send this to all the clients in the $clients array (except the first one, which is a listening socket)
              foreach ($clients as $send_sock) {
                if ($send_sock == $this->socket || $send_sock == $read_sock) {
                  continue;
                }

                socket_write($send_sock, $data."\n");
              } // end of broadcast foreach
            }
          } // end of reading foreach
        }
      }
    }

    public function stop() {
      if (!isset($this->sockRead[0])) {
        return false;
      }

      foreach ($this->clients as $clientId => $client) {

      }
    }

    public function sendClientMessage($clientId, $opcode, $message) {
      $client = $this->clients[$clientId];
      if ($client['readyState'] == self::READY_STATE_CLOSING || $client['readyState'] == self::READY_STATE_CLOSED) return true;

      // fetch message length
      $messageLength = strlen($message);

      // set max payload length per frame
      $bufferSize = 4096;

      // work out amount of frames to send, based on $bufferSize
      $frameCount = ceil($messageLength / $bufferSize);
      if ($frameCount == 0) $frameCount = 1;

      // set last frame variables
      $maxFrame = $frameCount - 1;
      $lastFrameBufferLength = ($messageLength % $bufferSize) != 0 ? ($messageLength % $bufferSize) : ($messageLength != 0 ? $bufferSize : 0);

      // loop around all frames to send
      for ($i=0; $i<$frameCount; $i++) {
        // fetch fin, opcode and buffer length for frame
        $fin = $i != $maxFrame ? 0 : self::FIN;
        $opcode = $i != 0 ? self::OPCODE_CONTINUATION : $opcode;

        $bufferLength = $i != $maxFrame ? $bufferSize : $lastFrameBufferLength;

        // set payload length variables for frame
        if ($bufferLength <= 125) {
          $payloadLength = $bufferLength;
          $payloadLengthExtended = '';
          $payloadLengthExtendedLength = 0;
        }
        elseif ($bufferLength <= 65535) {
          $payloadLength = self::PAYLOAD_LENGTH_16;
          $payloadLengthExtended = pack('n', $bufferLength);
          $payloadLengthExtendedLength = 2;
        }
        else {
          $payloadLength = self::PAYLOAD_LENGTH_63;
          $payloadLengthExtended = pack('xxxxN', $bufferLength); // pack 32 bit int, should really be 64 bit int
          $payloadLengthExtendedLength = 8;
        }

        // set frame bytes
        $buffer = pack('n', (($fin | $opcode) << 8) | $payloadLength) . $payloadLengthExtended . substr($message, $i*$bufferSize, $bufferLength);

        // send frame
        $socket = $client['socket'];

        $left = 2 + $payloadLengthExtendedLength + $bufferLength;
        do {
          $sent = @socket_send($socket, $buffer, $left, 0);
          if ($sent === false) return false;

          $left -= $sent;
          if ($sent > 0) $buffer = substr($buffer, $sent);
        }
        while ($left > 0);
      }

      return true;
    }

    public sendClientClose($clientId, $status = false) {
      $client = $this->clients[$clientId];
      if ($client['readyState'] == self::READY_STATE_CLOSING || $client['readyState'] == self::READY_STATE_CLOSED) {
        return true;
      }

      $client['closeStatus'] = $status;
      $status = $status !== false ? pack('n', $status) : '';
      $this->sendClientMessage($clientId, self::OPCODE_CLOSE, $status);
      $client['readyState'] = self::READY_STATE_CLOSING
    }
  }
