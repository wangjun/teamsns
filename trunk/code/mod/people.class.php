<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class peopleMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
	}
	
	
	public function index()
	{
		$people = get_data("SELECT * FROM user LIMIT 100 ");
		$data['people'] = $people;
		
		$data['title'] = $data['top_title'] = '同事';
		return render( $data );
	}
	
	public function add()
	{
		$data['title'] = $data['top_title'] = '新建帐号';
		return render( $data );
	}
	
	public function modify()
	{
		$uid = intval(v('uid'));
		if( $uid < 1 ) return info_page('错误的用户ID');
		
		if( !$user = get_line( "SELECT * FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1" ) )
			return info_page('没有该用户的数据,可能已经被删除');
		
		if( $user['id'] != uid() && $user['can_edit'] != 1 )
			return info_page('根据' . $user['name'] . '的设置,只有他自己才能编辑资料');
			
		$data['user'] = $user;	
		
		$data['title'] = $data['top_title'] = '修改' . $user['name'] . '的个人资料';
		return render( $data );
	}
	
	public function update()
	{
		$uid = intval(v('uid'));
		if( $uid < 1 ) return info_page('错误的用户ID');
		
		if( !$user = get_line( "SELECT * FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1" ) )
			return info_page('没有该用户的数据,可能已经被删除');
		
		if( $user['id'] != uid() && $user['can_edit'] != 1 )
			return info_page('根据' . $user['name'] . '的设置,只有他自己才能编辑资料');
		
		$old_name = get_var( "SELECT name FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1" );
		$name = z(t(v('name')));
		if( strlen($name) < 2  ) return ajax_box( '姓名不能少于两个字符' , '系统消息' , 2 );
		
		$email = z(t(v('email')));
		
		if( !filter_var($email, FILTER_VALIDATE_EMAIL) )
			return ajax_box( 'email地址不正确' , '系统消息' , 2 );
		
		$msn = z(t(v('msn')));
		$mobile = z(t(v('mobile')));
		$tel = z(t(v('tel')));
		
		$sql = "UPDATE user SET  name = '" . s($name) . "'  ,  email = '" . s($email) . "'  ,  msn = '" . s($msn) . "'  ,  tel = '" . s($tel) . "'  ,  mobile = '" . s($mobile) . "'   WHERE  id = '" .intval($uid) . "'   ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) != 0 )
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
		
		if( $old_name && ($old_name != $name) )
		{
			if( $uid != uid() )
				force_logout( $uid );
			else
				ss_set('name' , $name);
			
			
			if( $content = get_var("SELECT content FROM riki WHERE tag = '" . s( $old_name .'的个人页面' ) . "' ORDER BY timeline DESC LIMIT 1") )
			{
				$content = $content . '<p>本文档转移自 - [[' . $old_name . '的个人页面'. ']]</p>';
				$sql = "INSERT INTO riki ( tag , content , uid , pid ,timeline ) VALUES (  '" . s($name.'的个人页面') . "'  ,  '" . s($content) . "'  ,  '" . uid() . "'  , '0' ,  datetime('now', 'localtime')  )";
				run_sql( $sql );

				if( sqlite_last_error(db()) != 0 )
					return ajax_box('更新失败,请稍后再试' , NULL , 3 );

			}
		}
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('成功更新,页面转向中', '系统消息' , 0.5 , '?m=people' );
		else
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
	}
	
	public function remove_confirm()
	{
		$uid = intval(v('uid'));
		if( $uid < 1 ) return ajax_box('错误的用户ID');
		if( ulevel() < 5 ) return ajax_box('只有管理员才有能关闭帐号');
	
		if( !$user = get_line( "SELECT * FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1" ) )
			return ajax_box('没有该用户的数据,可能已经被删除');
			
		$data['user'] = $user;
		return render( $data , 'ajax' );	
	
	}
	
	public function avatar()
	{
		$data['title'] = $data['top_title'] = '头像';
		return render( $data );
	}
	
	public function avatar_update()
	{
		/*
			Array
			(
			    [avatar] => Array
			        (
			            [name] => 06b.jpg
			            [type] => image/jpeg
			            [tmp_name] => /Applications/MAMP/tmp/php/php7o12d2
			            [error] => 0
			            [size] => 26850
			        )

			)
		*/
		if( $_FILES['avatar']['size'] < 1 || $_FILES['avatar']['error'] != 0  ) return info_page('文件上传失败,请重试');
		$avatar_file = ROOT . 'static/data/user/u' . intval(uid()) . '.small.uif';
		
		if( reset( explode( '/' , $_FILES['avatar']['type'] ) ) != 'image' ) return info_page('只能上传图片文件');
		
		if( icon( $_FILES['avatar']['tmp_name'] , $avatar_file ) )
		{
			publish_feed( '{actor}修改了<a href="?m=people&a=profile&uid=' . uid() . '">头像</a> ' , '<img src="' . get_user_icon(uid()) . '" class="icon" />'  , 'user' , uid() .'-avatar-' . date("Y-m-d")  );
			return info_page('<a href="?m=people&a=avatar">更新成功,请点击这里查看新头像,如果头像没有更新,请多刷新几次</a>');
		}
			
		else
			return info_page('文件缩图失败,请重试');
	}
	
	public function password()
	{
		if( !$user = get_line( "SELECT * FROM user WHERE id = '" . intval( uid() ) . "' LIMIT 1" ) )
			return info_page('没有该用户的数据,可能已经被删除');

		$data['user'] = $user;	

		$data['title'] = $data['top_title'] = '密码';
		return render( $data );
	}
	
	public function password_update()
	{
		$password_old = z(t(v('password_old')));
		if( $password_old == '' ) return ajax_box('原始密码不能不为空' , '系统消息' , 3);
		
		$password_new = z(t(v('password_new')));
		if( $password_new == '' ) return ajax_box('新密码不能不为空' , '系统消息' , 3);
		
		$password_new2 = z(t(v('password_new2')));
		if( $password_new2 == '' ) return ajax_box('新密码不能不为空' , '系统消息' , 3);
		
		if( $password_new != $password_new2 ) return ajax_box('两次输入的密码不相同' , '系统消息' , 3);
		
		$user_password_md5 = get_var("SELECT password FROM user WHERE id = '" . intval(uid()) . "' LIMIT 1");
		if( md5( $password_old ) != $user_password_md5 ) return ajax_box('原密码不正确' );
		


		$sql = "UPDATE user SET password = '" . md5( $password_new ) . "' WHERE id = '" . intval(uid()) . "' AND password = '" . md5( $password_old ) . "' ";
		
		
		$ret = run_sql( $sql );

		if( (sqlite_last_error(db()) == 0) && (sqlite_last_changes (db(), $ret) == 1) )
		{
			logout();
			return ajax_box('成功更新,请使用新的密码登录', '系统消息' , 1 , '?m=user&a=login' );
		}
		else
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
		
		
		
	}
	
	public function settings()
	{
		if( !$user = get_line( "SELECT * FROM user WHERE id = '" . intval( uid() ) . "' LIMIT 1" ) )
			return info_page('没有该用户的数据,可能已经被删除');

		$data['user'] = $user;	

		$data['title'] = $data['top_title'] = '设置';
		return render( $data );
		
	}
	
	public function settings_update()
	{
	
		$uid = uid();

		$old_info = get_line( "SELECT * FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1" );
		$old_name = $old_info['name'];
		$name = z(t(v('name')));
		if( strlen($name) < 2  ) return ajax_box( '姓名不能少于两个字符' , '系统消息' , 2 );

		$email = z(t(v('email')));

		if( !filter_var($email, FILTER_VALIDATE_EMAIL) )
			return ajax_box( 'email地址不正确' , '系统消息' , 2 );

		$msn = z(t(v('msn')));
		$mobile = z(t(v('mobile')));
		$tel = z(t(v('tel')));
		$can_edit = intval(v('can_edit'));

		$sql = "UPDATE user SET  name = '" . s($name) . "'  ,  email = '" . s($email) . "'  ,  msn = '" . s($msn) . "'  ,  tel = '" . s($tel) . "'  ,  mobile = '" . s($mobile) . "' , can_edit = '" . intval( $can_edit ) . "'   WHERE  id = '" .intval($uid) . "'   ";

		run_sql( $sql );

		if( sqlite_last_error(db()) != 0 )
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
			
		
		
		if( $old_name && ($old_name != $name) )
		{
			ss_set('name' , $name);	
			
			if( $content = get_var("SELECT content FROM riki WHERE tag = '" . s( $old_name .'的个人页面' ) . "' ORDER BY timeline DESC LIMIT 1") )
			{
				$content = $content . '<p>本文档转移自 - [[' . $old_name . '的个人页面'. ']]</p>';
				$sql = "INSERT INTO riki ( tag , content , uid , pid ,timeline ) VALUES (  '" . s($name.'的个人页面') . "'  ,  '" . s($content) . "'  ,  '" . uid() . "'  , '0' ,  datetime('now', 'localtime')  )";
				run_sql( $sql );

				if( sqlite_last_error(db()) != 0 )
					return ajax_box('更新失败,请稍后再试' , NULL , 3 );

			}
		}
		
		$change = false;
		$cinfo = array();
		if( $name != $old_info['name'] )
		{
			if( $old_info['name'] != '' )
				$cinfo[] = '姓名从' . $old_info['name'] . '改为' . $name;
			else
				$cinfo[] = '姓名改为' . $name;
			
			$change = true;
		}
		
		if( $email != $old_info['email'] )
		{
			if( $old_info['email'] != '' )
				$cinfo[] = '电子邮件从' . $old_info['email'] . '改为' . $email;
			else	
				$cinfo[] = '电子邮件改为' . $email;
			
			$change = true;
		}
		
		if( $msn != $old_info['msn'] )
		{
			if( $old_info['msn'] != '' )
				$cinfo[] = 'MSN从' . $old_info['msn'] . '改为' . $msn;
			else
				$cinfo[] = 'MSN改为' . $msn;
				
			$change = true;
		}
		
		if( $tel != $old_info['tel'] )
		{
			if( $old_info['tel'] != '' )
				$cinfo[] = '办公电话从' . $old_info['tel'] . '改为' . $tel;
			else
				$cinfo[] = '办公电话改为' . $tel;
				
			$change = true;
		}
		
		if( $mobile != $old_info['mobile'] )
		{
			if( $old_info['mobile'] != '' )
				$cinfo[] = '手机从' . $old_info['mobile'] . '改为' . $mobile;
			else
				$cinfo[] = '手机改为' . $mobile;
				
			$change = true;
		}
		
		if( sqlite_last_error(db()) == 0 )
		{
			if( $change )
			{
				publish_feed( '{actor}修改了<a href="?m=people&a=profile&uid=' . $uid . '">个人资料</a> ' ,  @join( '<br/>' , $cinfo ) , 'user'  );		
				if( $email != $old_info['email'] )
				{
					logout();
					return ajax_box('成功更新,请使用新的电子邮件地址登录', '系统消息' , 1 , '?m=user&a=login' );
				}
				
			}
				
			return ajax_box('成功更新', '系统消息' , 0.5 , '?m=people&a=settings' );
		}
		else
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
		
	}
	
	public function remove()
	{
		$uid = intval(v('uid'));
		if( $uid < 1 ) return ajax_box('错误的用户ID');
		if( ulevel() < 5 ) return ajax_box('只有管理员才有能关闭帐号');
	
		if( !$user = get_line( "SELECT * FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1" ) )
			return ajax_box('没有该用户的数据,可能已经被删除');
		
		$type = intval(v('type'));
		
		if( $type == 1 )
			$sql = "DELETE FROM user WHERE id = '" . intval( $uid ) . "' ";
		else
			$sql = "UPDATE user SET level = 0 WHERE id = '" . intval( $uid ) . "' ";
		
		run_sql( $sql );

		if( sqlite_last_error(db()) == 0 )
			return ajax_box('成功更新,页面转向中', '系统消息' , 0.5 , '?m=people' );
		else
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
		
	}
	
	public function feed_remove()
	{
		$fid = intval(v('fid'));
		if( $fid < 1 ) return ajax_box('Feed ID丢失,请稍后再试');
		
		$sql = "DELETE FROM newsfeed WHERE uid = '" . intval(uid()) . "' AND id = '" . intval( $fid ) . "' ";
		
		run_sql( $sql );
		$script = '<script>$(\'feed_' . $fid . '\').setStyle(\'display\' , \'none\');</script>';
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('成功更新'.$script, '系统消息' , 0.5);
		else
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
	}
	
	public function profile()
	{
		$uid = intval(v('uid'));
		if( $uid < 1 ) $uid = uid();
 		
		$data['user'] = get_line( "SELECT * FROM user WHERE id = '" . intval($uid) . "' AND level > 0 LIMIT 1" );
		if( !$data['user'] ) return info_page('该帐户不存在或者已经被关闭');
		
		$data['feeds'] = get_data( "SELECT * FROM newsfeed WHERE uid = '" . intval($uid) . "' ORDER BY timeline DESC LIMIT 100" );
		$data['todos_open'] = get_data("SELECT * FROM todo WHERE 1  AND uid = '" . intval($uid) . "'  AND is_done != '1' AND is_delete != 1 AND is_slow != 1 ORDER BY is_start DESC , timeline DESC LIMIT 10");
		
		$data['todos_done'] = get_data("SELECT * FROM todo WHERE  1  AND uid = '" . intval($uid) . "' AND is_done = '1' AND is_delete != 1 AND is_slow != 1 ORDER BY check_time DESC , timeline DESC LIMIT 10");
		
		$data['title'] = $data['top_title'] = $data['user']['name'];
		return render( $data );
	}
	
	
	
	public function save()
	{
		$name = z(t(v('name')));
		if( strlen($name) < 2  ) return ajax_box( '姓名不能少于两个字符' , '系统消息' , 2 );
		
		$email = z(t(v('email')));
		
		if( !filter_var($email, FILTER_VALIDATE_EMAIL) )
			return ajax_box( 'email地址不正确' , '系统消息' , 2 );
		
		if( get_var("SELECT COUNT(*) FROM user WHERE email = '" . s( $email ) . "'") > 0 )
			return ajax_box( 'email已经被占用,请联系管理员' );
		
		$msn = z(t(v('msn')));
		$mobile = z(t(v('mobile')));
		$tel = z(t(v('tel')));
		
		$password = rand(1,20) . rand(50 , 80)  . rand( 5 , 9000 );
		$password = substr(  $password , 0 , 8 );
		
		$password_md5 = md5( $password );
		
		$sql = "INSERT INTO user (  name , email , msn , tel , mobile , password , level , timeline ) VALUES (   '" . s($name) . "'  ,  '" . s($email) . "'  ,  '" . s($msn) . "'  ,  '" . s($tel) . "'  ,  '" . s($mobile) . "'  ,  '" . s( $password_md5 ) . "'  ,  '1'  ,  datetime('now', 'localtime')  )";	
		
		run_sql( $sql );
		
		$notice = '<div class="p5">您的同事' . uname() .'在' . c('site_name') . '里边为您创建了帐号: <br/> Email: ' . $email . '<br/>密码:' . $password . '<br/>请通过以下地址登录并修改您的密码<br/><a href="' . $_SERVER['HTTP_REFERER'] . '">' . $_SERVER['HTTP_REFERER'] . '</a></div> <input type="button" class="button" value="进入同事列表" onclick="location=\'?m=people\'" />' ;
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('成功添加用户,您可以粘贴以下信息发送给' . $name . '<br/><br/>' . $notice , '系统消息' );
		else
			return ajax_box('操作失败,请稍后再试' , NULL , 3 );
	}
	

	
	
	
}


?>