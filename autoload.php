<?php
  ini_set('memory_limit', '-1');

  class Autoload {
    public static function init() {
      spl_autoload_register(function ($name) {
        try {
          $path = __DIR__ . '/lib';
          $classFile = $path . '/' . $name . '.php';
          if (file_exists($classFile)) {
            require $classFile;
          }
        } catch (Exception $e) {
          echo "cant load class $name";
        }
      });
    }
  }

  Autoload::init();
