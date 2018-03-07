<?php
  class RocketClient {
    public $host;
    public $port;
    public $Server;
    public $events = [];
    
    public function __construct($host, $port) {
      $this->host = $host;
      $this->port = $port;
      
      $this->Server = fsockopen($host, $port);
    }
    
    public function emit($event, $data) {
      $encodedData = gzencode($data);
      $msg = '<message><type>emit</type><name>'. $event .'</name><data>'. $encodedData .'</data></message>';
      fwrite($this->Server, $msg);
    }
    
    public function on($event, $callback) {
      $this->events[$event] = $callback;
    }
    
    public function listen() {
      
    }
  }
