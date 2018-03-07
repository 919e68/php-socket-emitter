<?php
  class Rocket {

    public $host;
    public $port;
    public $Socket;
    public $events = [];

    public function __construct($host, $port) {
      $this->host = $host;
      $this->port = $port;

      $this->Socket = new Socket($host, $port);
    }

    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }

    public function listen() {
      $this->Socket->on('connect', function($id) {
        if (array_key_exists('connect', $this->events)) {
          call_user_func($this->events['connect'], $id);
        }
      });
    
      $this->Socket->on('message', function($data, $id) {
        echo $data;
        $xml = simplexml_load_string($data);
        
        echo $xml;
        if ($xml) {
          $jsonString = json_encode($xml);
          $json = json_decode($jsonString, true);
          var_dump($json);
        }
      });
    
      $this->Socket->on('disconnect', function($id) {
        if (array_key_exists('disconnect', $this->events)) {
          call_user_func($this->events['disconnect'], $id);
        }
      });
      
      $this->Socket->listen();
    }
  }
