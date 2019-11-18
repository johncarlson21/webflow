<?php

/*
 * 
 *	PDO Class used to setup system PDO usage
 *
 */

namespace Webflow;

use PDO;

class Wpdo extends PDO {
    
    public $tables;
    
    public $datetime;
    
    public function __construct( $file = 'db.ini' ) {
        if ( !$settings = parse_ini_file( $file, TRUE ) ) throw new exception( 'Unable to open ' . $file . '.' );
        
        $this->tables = $settings['tables'];
        $this->datetime = $settings['datetime'];
        
        $dns = $settings[ 'database' ][ 'driver' ] .
        ':host=' . $settings[ 'database' ][ 'host' ] .
            ( ( !empty( $settings[ 'database' ][ 'port' ] ) ) ? ( ';port=' . $settings[ 'database' ][ 'port' ] ) : '' ) .
        ';dbname=' . $settings[ 'database' ][ 'schema' ];

        $dbOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            //PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC
        ];
        try {
            parent::__construct( $dns, $settings[ 'database' ][ 'username' ], $settings[ 'database' ][ 'password' ], $dbOptions );
        } catch ( PDOException $e ) {
            die( "Database connection failed: " . $e->getMessage() );
        }
    }
    
}