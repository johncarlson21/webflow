<?php

/*
 * 
 *	PDO Class used to setup system PDO usage
 */

namespace Webflow;

use PDO;

class Wpdo extends PDO
{
    public function __construct($file = 'db.ini')
    {
        if (!$settings = parse_ini_file($file, TRUE)) throw new exception('Unable to open ' . $file . '.');
       
        $dns = $settings['database']['driver'] .
        ':host=' . $settings['database']['host'] .
        ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
        ';dbname=' . $settings['database']['schema'];
		
		$dbOptions = [
		\PDO::ATTR_DEFAULT_FECTH_MODE => \PDO::FETCH_OBJ,
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
		];
       
        return parent::__construct($dns, $settings['database']['username'], $settings['database']['password'], $dbOptions);
    }
}