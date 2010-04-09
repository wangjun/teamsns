<?php
// the front page of lazyphp

ini_set('display_errors',true);
error_reporting(E_ERROR);
ini_set( 'magic_quotes_gpc' , false );

@date_default_timezone_set('Asia/Shanghai');

// 常量
define( 'IN' , true );
define( 'IN_SQLITE3', true);
define( 'ROOT' , dirname( __FILE__ ) . '/' );
define( 'CROOT' , ROOT . 'code/'  );

// global functiones
include_once( CROOT . 'function/init.function.php' );
include_once( CROOT . 'function/core.function.php' );
include_once( CROOT . 'config/core.config.php' );


if( !file_exists( CROOT . 'config/db.config.php' ) )
{
	if( file_exists( ROOT . 'db/clean.db' . ( IN_SQLITE3 ? '3' : '' )) )
	{
		$rand_db = substr(md5('ts_' . rand( 1 , 9999 ) . time()) , 0 , rand( 10 , 15 ) ) . '.db'.( IN_SQLITE3 ? '3' : '' );
		copy( ROOT . 'db/clean.db'.( IN_SQLITE3 ? '3' : '' ) , ROOT . 'db/' . $rand_db  );

		$content = '<' . '?php ' . '$GLOBALS[\'config\'][\'db\'][\'file_name\'] =\'db/' .  $rand_db . '\';' . '?' . '>';
		file_put_contents(  CROOT . 'config/db.config.php' , $content );
	}
}

include_once( CROOT . 'config/db.config.php' );

session_set_cookie_params( 60*60*24*3  );
session_save_path( ROOT .'session/' );
session_start();


// 请求转发
$m = $GLOBALS['m'] = v('m') ? v('m') : c('default_mod');
$a = $GLOBALS['a'] = v('a') ? v('a') : c('default_action');

$m = basename(strtolower( $m ));

$mod_file = CROOT . 'mod/' . $m .'.class.php';

if( !file_exists( $mod_file ) ) die('Can\'t find controller file - ' . $m . '.class.php');
require( $mod_file );

if( !class_exists( $m.'Mod' ) ) die('Can\'t find class - '   . $m . 'Mod');

$class_name =$m.'Mod'; 

$o = new $class_name;
if( !method_exists( $o , $a ) ) die('Can\'t find method - '   . $a . ' ');

$sec  = intval(date("s"));
if( $sec >= 0 && $sec <=10 )
{
	online();
}

call_user_method( $a , $o );




?>