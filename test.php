<?php

  $test = simplexml_load_string('<message><type>emit</type><name>hello</name><data>Hello World</data></message>');
  
  $json = json_encode($test);
  $data = json_decode($json, true);
  
  $sample = '*type*emit*/type*' . "\r\n";
  $sample .= '*name*Hello*/name*' . "\r\n";
  $sample .= '*data*' . "\r\n";
  
  $sample .= "\r\n" . '*/data*';
  
  // function getType($str) {
  // 
  // }
    
  $end = strpos($sample, '*/type*');
  // echo substr($sample, 6, $end);
  echo $end;
  
  
  