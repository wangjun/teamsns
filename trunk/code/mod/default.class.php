<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class defaultMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
	}
	
	
	public function fortest()
	{
		$data['test'] = 'forunittest';
		$data['title'] = $data['top_title'] = 'The DATA';
		render( $data );
		//echo 'forunittest';
	}
	
	public function infopage()
	{
		info_page('For Unit Test');
	}
	
	public function info()
	{
		info_page('<a href="?m=user&a=login">登录页面</a>');
	}
	
	public function index()
	{
		$data['title'] = $data['top_title'] = '疯狂大学';
		$data['user'] = get_user(1);
		
		render( $data );
	}
	
	public function clean()
	{
		session_start();
		foreach( $_SESSION as $key=>$value )
		{
			unset( $_SESSION[$key] );
		}
		
		return info_page('清除完成');
	}
}


?>