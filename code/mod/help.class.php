<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class helpMod 
{
	function __construct()
	{
		// 载入默认的
		//parent::__construct();
	}
	
	
	public function index()
	{
		session_start();
		foreach( $_SESSION as $key=>$value )
		{
			unset( $_SESSION[$key] );
		}
		
		return die('清除完成');
	}
}


?>