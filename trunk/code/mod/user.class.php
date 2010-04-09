<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class userMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
	}
	
	public function login()
	{
		//echo 'It works';
		$data['title'] = $data['top_title'] = '登录';
		render( $data );
	}
	
	public function logout()
	{
		logout();
		info_page('<a href="?m=user&a=login">成功退出,点击这里以其他帐号登录</a>');
	}
	
	public function login_check()
	{
		if( !v('email') ) return ajax_box('电子邮件地址不能为空');
		if( !v('password') ) return ajax_box('密码不能为空');
		
		$email = z(v('email'));
		$password = md5(z(v('password')));
		
		if( $user = get_line("SELECT * FROM user WHERE email = '" . s($email) . "' AND password = '" . s( $password ) . "' LIMIT 1") )
		{
			$_SESSION['uid'] = $user['id'];
			$_SESSION['name'] = $user['name'];
			$_SESSION['email'] = $user['email'];
			$_SESSION['level'] = $user['level'];
			
			publish_feed( '{actor}上线了', NULL , 'user' , uid() .'-' . 'login-' . date("Y-m-d") , false );
			
			return ajax_box( '欢迎回来,' . $user['name'] . '.正在转向' , NULL , 0.5 , '?m=dashboard' );
		}
		else
			return ajax_box('电子邮件或者密码错误,请重试');
	}
	
	
}


?>