<?php

class Application {

  public static function mergeArrays($array1, $array2) {
    $merged = $array1;
    foreach($array2 as $key => $value) {
      $merged[$key] = $value;
    }
    return $merged;
  }

  function htmlEncode($var)
  {
    return htmlentities($var, ENT_QUOTES, 'UTF-8') ;
  }

};

?>