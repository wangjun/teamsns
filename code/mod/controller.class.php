<?php
if( !defined('IN') ) die('bad request');


class controller 
{
	function __construct()
	{
		// load datafunction
		
		$data_function_file = CROOT . 'function/' . g('m') . '.function.php';
		if( file_exists( $data_function_file ) )
		{
			require_once( $data_function_file );
		}
		
	}
	
	public function check_login()
	{
		if( !is_login() )
		{
			info_page('<a href="?m=user&a=login">请登录以后再进行操作</a>');
			exit;
		}
		
		if( ulevel() < 1 )
		{
			info_page('<a href="?m=user&a=login">该帐号已经被关闭,如有任何疑问请联系系统管理员</a>');
			exit;
		} 
	}
	
	public function index()
	{
		// 
	} 
}

?>