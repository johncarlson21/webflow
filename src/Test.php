<?php

namespace Webflow;

//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;
//use GuzzleHttp\Client;

class Test
{
	
  //constants
  
  public function __construct() {
      error_log('we got to the construct');
  }
  
  public function Hello() {
      echo "We are in the Hello function!";
  }
  
}
