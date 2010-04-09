<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class todoMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
	}

	public function index()
	{
		$pid = intval(v('pid'));
		$uid = intval(v('uid'));
		if( $uid < 1 ) $uid = uid();
		
		if( $pid > 0 )
			$where = " AND pid = '" . intval( $pid ) . "' ";
		elseif($pid == -1)
			$where = " AND ( pid = '0' OR pid = '' ) ";
		else
			$where = " ";
			
		// 不限关闭项目的todo
		$psql = '';
		if( $close_project = get_data("SELECT * FROM project WHERE is_active = 0 ") )
		{
			foreach( $close_project as $cp )
				$cpid[] = $cp['id'];
			
			if( isset( $cpid ) )
				$psql = ' AND pid NOT IN ( ' . join( ' , ' , $cpid ) . ' ) ';
		}
			
			
		
		$data['todos_open'] = get_data("SELECT * FROM todo WHERE 1 $where $psql AND uid = '" . intval($uid) . "'  AND is_done = '0' AND is_delete = '0' AND is_slow = '0' ORDER BY is_start DESC , timeline DESC");

		//echo "SELECT * FROM todo WHERE 1 $where $psql AND uid = '" . intval($uid) . "'  AND is_done = '0' AND is_delete = '0' AND is_slow = '0' ORDER BY is_start DESC , timeline DESC";
		
		$data['todos_done'] = get_data("SELECT * FROM todo WHERE  1 $where $psql AND uid = '" . intval($uid) . "' AND is_done = '1' AND is_delete != 1 AND is_slow != 1 ORDER BY check_time DESC , timeline DESC");
		$data['todos_slow'] = get_data("SELECT * FROM todo WHERE  1 $psql $where AND  uid = '" . intval($uid) . "' AND is_done != '1' AND is_delete != 1 AND is_slow = 1 ORDER BY timeline DESC");
		
		$data['todos_other'] = get_data("SELECT * FROM todo WHERE  1 $psql $where AND creator_uid = '" . intval($uid) . "'  AND  uid != '" . intval($uid) . "' AND is_done != '1' AND is_delete != 1 AND is_slow != 1 ORDER BY timeline DESC");
		
		$data['user'] = get_line("SELECT * FROM user WHERE id = '" . intval( $uid ) . "' LIMIT 1");
		$data['projects'] = get_data("SELECT * FROM project WHERE is_active = 1 ORDER BY timeline DESC LIMIT 10");
		$data['user_id'] = $uid;
		$data['pid'] = $pid;
		$data['title'] = $data['top_title'] = 'TODO';
		
		//print_r( $data );
		return render( $data );
	}
	
	public function add()
	{
		$data['title'] = $data['top_title'] = 'TODO';
		return render( $data , 'ajax' );
	}
	
	public function modify()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$data['tinfo'] = get_line( "SELECT * FROM todo WHERE uid = '" . uid() . "' AND id = '" . intval( $tid ) . "' LIMIT 1" );
		
		$data['tid'] = $tid;
		$data['title'] = $data['top_title'] = 'TODO';
		return render( $data , 'ajax' );
	}
	
	public function update()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$todo = z(v('todo'));
		$link = z(v('link'));
		$desp = z(v('desp'));

		if( $todo == '' ) return ajax_box('TODO内容不能为空');
		
		$sql = "UPDATE todo SET  name = '" . s($todo) . "' , link = '" . s($link) . "' , desp = '" . s($desp) . "'  WHERE  id = '" .intval($tid) . "'   AND  uid = '" . uid() . "'  ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function set_project()
	{
		setcookie( '__TS_LAST_TODO_TEXT' , v('text') , time() + 60*60*24*365 );
		
		$data['projects'] = get_data("SELECT * FROM project WHERE is_active = 1 ORDER BY timeline DESC LIMIT 10");
		$data['title'] = $data['top_title'] = 'TODO';
		$data['todo_page'] = intval(v('todo_page'));
		return render( $data , 'ajax' );
	}
	
	public function set_project_do()
	{
		$pid = intval(v('pid'));
		
		setcookie( '__TS_LAST_PID' , $pid , time() + 60*60*24*365 );
		
		
		
		if( intval(v('todo_page')) > 0 )
			return ajax_echo('<script>show_float_box("?m=todo&a=add&pid=' . $pid . '")</script>');
		else
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
	}
	
	public function assign_confirm()
	{	
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$data['tinfo'] = get_line( "SELECT * FROM todo WHERE uid = '" . uid() . "' AND id = '" . intval( $tid ) . "' LIMIT 1" );
		if( $data['tinfo']['creator_uid'] != uid() ) return ajax_box('你只能转让自己创建的TODO' , '系统消息' , 3 );
		
		$data['peoples'] = get_people();
		
		$data['tid'] = $tid;
		$data['title'] = $data['top_title'] = 'TODO';
		
		return render( $data , 'ajax' );
	}
	
	public function assign()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$uid = intval(v('uid'));
		if( $uid < 1 ) return ajax_box('错误的用户 ID');
		
		$todo = get_line( "SELECT * FROM todo WHERE uid = '" . uid() . "' AND id = '" . intval( $tid ) . "' LIMIT 1" );
		
		$follow_uids = @unserialize($todo['follow_uids']);
		
		$follow_uids[] = uid();
		$follow_uids = array_unique($follow_uids);
		
		
		$sql = "UPDATE todo SET uid = '" . intval( $uid ) . "' , follow_uids = '" . s(serialize($follow_uids)) . "' WHERE id = '" . intval( $tid ) . "' AND creator_uid = '" . intval(uid()) . "' ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
		{
			publish_feed( '{actor}转让了TODO <a href="?m=todo">' . $todo['name'] .'</a>给<a href="?m=peoplke&a=profile&uid=' . $uid . '">' . get_people_name( $uid ) . '</a>' , NULL ,  'todo' );
			
			send_notice( $uid , '{actor}转让了TODO <a href="?m=todo">' . $todo['name'] .'</a>给你' , 'todo' , uid() .'-todo-assign-'  .  date("Y-m-d-H") );
			
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
		}
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
		
	}
	
	public function link_confirm()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$data['tinfo'] = get_line( "SELECT * FROM todo WHERE uid = '" . uid() . "' AND id = '" . intval( $tid ) . "' LIMIT 1" );
		$data['projects'] = get_data("SELECT * FROM project WHERE is_active = 1 ORDER BY timeline DESC LIMIT 10");
		
		$data['tid'] = $tid;
		$data['title'] = $data['top_title'] = 'TODO';
		
		return render( $data , 'ajax' );
	}
	
	public function link()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$pid = intval(v('pid'));
		
		$sql = "UPDATE todo SET  pid = '" . intval($pid) . "'   WHERE  id = '" .intval($tid) . "'   AND  uid = '" . uid() . "'  ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function remove_confirm()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$data['name'] = get_var( "SELECT name FROM todo WHERE uid = '" . uid() . "' AND id = '" . intval( $tid ) . "' LIMIT 1" );
		$data['tid'] = $tid;
		$data['title'] = $data['top_title'] = 'TODO';
		return render( $data , 'ajax' );
	}
	
	public function remove()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		//$todo = get_line( "SELECT * FROM todo WHERE uid = '" . uid() . "' AND id = '" . intval( $tid ) . "' LIMIT 1" );
		
		$sql = "DELETE FROM todo WHERE id = '" . intval($tid) . "' AND uid = '" . uid() . "' ";
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
		{
			/*
			if(  time() - strtotime($todo['timeline']) <= 60 )
				run_sql( "DELETE FROM newsfeed WHERE res_id LIKE " )
			*/
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
		}
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function quick()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$sql = "UPDATE todo SET  is_slow = '0'   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER']);
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function slow()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$sql = "UPDATE todo SET  is_slow = '1'   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER']);
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function start()
	{
			$tid = intval(v('tid'));
			if( $tid < 1 ) return ajax_box('错误的TODO ID');

			$todo = get_line("SELECT * FROM todo WHERE id = '" . intval( $tid ) . "' LIMIT 1");
			
			if( $todo['init_time'] == '0000-00-00 00:00:00' )
				$sql = "UPDATE todo SET  is_start = '1' , start_time = datetime('now') , init_time = datetime('now')  WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";
			else
				$sql = "UPDATE todo SET  is_start = '1' , start_time = datetime('now')   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";

			run_sql( $sql );

			if( sqlite_last_error(db()) == 0 )
			{
				publish_feed( '{actor}开始了TODO - <a href="?m=todo&uid=' . uid() . '">' . $todo['name'] .'</a>' , NULL , 'todo' , uid() .'-todo-start-' . $tid  );
				return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER']);
			}
				
			else
				return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function stop()
	{
			$tid = intval(v('tid'));
			if( $tid < 1 ) return ajax_box('错误的TODO ID');
			
			$sql = "UPDATE todo SET  is_start = '2' , total_time = total_time + (UNIX_TIMESTAMP(datetime('now')) - UNIX_TIMESTAMP(start_time))   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";

			run_sql( $sql );

			if( sqlite_last_error(db()) == 0 )
			{
				$todo = get_line("SELECT * FROM todo WHERE id = '" . intval( $tid ) . "' LIMIT 1");
				publish_feed( '{actor}暂停了TODO - <a href="?m=todo&uid=' . uid() . '">' . $todo['name'] .'</a>' , NULL , 'todo' , uid() .'-todo-stop-' . $tid  );
				return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER']);
			}
			else
				return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function follow()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$todo = get_line("SELECT * FROM todo WHERE id = '" . intval( $tid ) . "' LIMIT 1");
		
		$follow_uids = @unserialize($todo['follow_uids']);
		
		if( intval(v('do')) == 1 )
		{
			$follow_uids[] = uid();
			$follow_uids = array_unique($follow_uids);
		}
		else
		{
			if( ($key = array_search( uid()  , $follow_uids )) !== false )
				unset( $follow_uids[$key] );
		}
		
		$sql = "UPDATE todo SET follow_uids = '" . s( serialize( $follow_uids ) ) . "' WHERE id = '" . intval($tid) . "' ";
		
		run_sql( $sql );

		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 ,  $_SERVER['HTTP_REFERER']  );
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	
	}
	
	public function check()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
		$todo = get_line("SELECT * FROM todo WHERE id = '" . intval( $tid ) . "' LIMIT 1");
		
		if( $todo['is_start'] == 1 )
		{
			$sql = "UPDATE todo SET  is_done = '1' , check_time = datetime('now') , is_start = 0 ,  total_time = total_time + (UNIX_TIMESTAMP(datetime('now')) - UNIX_TIMESTAMP(start_time))   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";
		}
		else
		{
			$sql = "UPDATE todo SET  is_done = '1' , check_time = datetime('now')   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";
		}
		
		
	
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
		{
			$total_time = intval( $todo['total_time'] );
			if( $todo['start_time'] != '0000-00-00 00:00:00' )
				$total_time = $total_time + (time() - strtotime($todo['start_time']) );	
			
			if( $total_time > 0 )
				$content = '耗时' . sec2time( $total_time );
			else
				$content = NULL;
			
			publish_feed( '{actor}完成了TODO - <a href="?m=todo&uid=' . uid() . '">' . $todo['name'] .'</a>' , $content , 'todo' , uid() .'-todo-stop-' . $tid  );
			
			if( $follow_uids = @unserialize($todo['follow_uids']) )
				send_notice( $follow_uids , '{actor}完成了TODO - <a href="?m=todo&uid=' . uid() . '">' . $todo['name'] .'</a>' , 'todo' );
			
				
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
		}
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function uncheck()
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) return ajax_box('错误的TODO ID');
		
			$sql = "UPDATE todo SET  is_done = '0'   WHERE uid = '" . uid() . "' AND id = '" .intval($tid) . "'  ";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER']);
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function desp()
	{
			$tid = intval(v('tid'));
			if( $tid < 1 ) return ajax_box('错误的TODO ID');
			
			if( !$todo = get_line("SELECT * FROM todo WHERE id = '" . $tid . "' LIMIT 1 ") )
				return ajax_box( 'todo不存在' , NULL , 3 );
			
			return ajax_box( nl2br($todo['desp']) , 'TODO备注 - ' .  $todo['name'] .''  );
		
	}
	
	public function clean()
	{
		$sql = "UPDATE todo SET is_delete = 1 WHERE uid = '" . uid() . "' AND is_done = 1 ";
		run_sql( $sql );

		if( sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER']);
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function save()
	{
		$todo = z(v('todo'));
		$link = z(v('link'));
		$desp = z(v('desp'));
		$pid = intval(v('pid'));
		
		$follow_uids = array();
		
		$uid = intval(v('uid'));
		if( $uid < 1 ) $uid = uid();
		else
		{
			setcookie( '__TS_LAST_UID' , $uid , time() + 60*60*24*365 );
			if( $uid != uid() ) $follow_uids[] = uid();
		} 
		
		if( $todo == '' ) return ajax_box('TODO内容不能为空');
		
		$sql = "INSERT INTO todo (  name , link , desp , uid , creator_uid , pid , is_done , follow_uids ,  timeline ) VALUES (    '" . s($todo) . "'  ,  '" . s($link) . "',  '" . s($desp) . "' ,'" . $uid . "'  , '" . uid() . "'  ,  '" . intval($pid) . "' , '0'  , '" . s( serialize( $follow_uids ) ) . "' , datetime('now')  )";

		
		run_sql( $sql );
		
		// 给其他人
		if( $uid != uid() )
		{
			send_notice( $uid , '{actor}转让了TODO <a href="?m=todo">' . $todo .'</a>给你' , 'todo' , uid() .'-todo-assign-'  .  date("Y-m-d-H") );
		}
		
		$link_text = '';
		if( $link != '' ) $link_text = '<img src="static/image/paperclip.gif" /><a href="' . $link . '" target="_blank">链接</a>'; 
		
		if( sqlite_last_error(db()) == 0 )
		{
			publish_feed( '{actor}添加了新的TODO - <a href="?m=todo&uid=' . uid() . '">' . $todo .'</a>' . $link_text , NULL , 'todo'  , uid() . '-todo-add-' . last_id());
			
			return ajax_box('添加成功' , NULL , 0.1 , $_SERVER['HTTP_REFERER'] );
		
		}
		else
			return ajax_box('添加失败,请稍后再试' , NULL , 3 );
	}
	
}	