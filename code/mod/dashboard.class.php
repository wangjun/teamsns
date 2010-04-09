<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class dashboardMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
	}
	
	
	public function index()
	{
		$uid = intval(v('uid'));
		if( $uid > 0 )
			$usql = ' AND uid = "' . intval($uid) . '"';
		elseif( intval(v('other')) > 0 )
			$usql = " AND uid != '" . intval(uid()) . "' ";	
		else
			$usql = '';	
		
		$fsql = '';
		
		if( $feed_settings = @unserialize( get_var( "SELECT feed_settings FROM user WHERE id = '" . uid() . "' LIMIT 1" ) ) )
		{
			if( is_array( $feed_settings ) )
			{
				foreach( $feed_settings as $f  )
					if( $f != '' ) $ret[] = "'" . $f . "'";
				
				if(isset( $ret ))
					$fsql = " AND aname IN ( " . join( ' , ' , $ret  )  . " ) ";
			}
			
		}
		
		//echo 'It works';
		$data['feeds'] = get_data( "SELECT * FROM newsfeed WHERE 1 $usql $fsql ORDER BY timeline DESC LIMIT 100" );
		$data['people_all'] = get_data( "SELECT * FROM user WHERE level > 0 ORDER BY id ASC LIMIT 50" );
		$data['title'] = $data['top_title'] = '近况';
		render( $data );
	}
	
	public function settings()
	{
		$data['settings'] = @unserialize( get_var( "SELECT feed_settings FROM user WHERE id = '" . uid() . "' LIMIT 1" ) );
		if( !$data['settings'] ) $data['all'] = true;
		$data['title'] = $data['top_title'] = '近况';
		render( $data , 'ajax' );
	}
	
	public function settings_update()
	{
		$settings = v('settings');
		$sql = "UPDATE user SET feed_settings = '" . s(serialize($settings)) . "' WHERE id = '" . uid() . "' ";
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , '?m=dashboard&uid=' . intval(v('uid')) );
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function cast()
	{
		$cast = z(v('cast'));
		$link = z(v('link'));
		
		if( $cast == '' ) return ajax_box('广播内容不能为空');
		
		$sql = "INSERT INTO cast ( uid , name , link , timeline ) VALUES (  '" . intval(uid()) . "'  ,  '" . s($cast) . "'  ,  '" . s($link) . "'  ,  datetime('now')  )";
		
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
		{
			if( $link != '' ) 
				$content = '<a href="' . $link . '" target="_blank">' . $cast . '</a>';
			else
				$content =  $cast ;
			
			publish_feed( '{actor}发起广播 - ' . $content , NULL , 'cast'  );
			
			if( $all_user = get_people_uids() )
				send_notice( $all_user , '{actor}发起广播 - ' . $content , 'cast' );
			
			return ajax_box('广播成功' , NULL , 0.1 , '?m=dashboard&uid=' . intval(v('uid')) . '&other=' . intval(v('other')) );
		}
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
		
	}
	
	
}


?>