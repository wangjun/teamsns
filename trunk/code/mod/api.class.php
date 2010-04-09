<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class apiMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_api_auth();
	}

	public function auth() 
	{
		$this->return_obj['code'] = 1000;
		$this->return_obj['content'] = true;
		$this->send_return();
	}

	/*
	api.get_todo_list - 获取当前用户未完成的todo
	return  obj.code = int , obj.content = array
	*/
	public function get_todo_list() 
	{
		$data = get_data("SELECT * FROM todo WHERE uid = '" . intval($this->uid) . "'  AND is_done = '0' AND is_delete = '0' AND is_slow = '0' ORDER BY is_start DESC , timeline DESC");

		$this->return_obj['code'] = 1000;
		$this->return_obj['content'] = $data;
		$this->send_return();
	}

	/*
	api.set_todo_done - 将当前用户的某todo标记为完成
	input	tid - int = todo id
	return	obj.code - int , obj.content - bool 
	*/
	public function set_todo_done() 
	{
		$tid = intval(v('tid'));
		if( $tid < 1 ) 
		{
			$this->return_obj['code'] = 1002;
			$this->return_obj['content'] = 'Bad Args - tid';
			$this->send_return();
		}
		
		$todo = get_line("SELECT * FROM todo WHERE id = '" . intval( $tid ) . "' LIMIT 1");
		
		if( $todo['is_start'] == 1 )
		{
			$sql = "UPDATE todo SET  is_done = '1' , check_time = datetime('now', 'localtime') , is_start = 0 ,  total_time = total_time + (UNIX_TIMESTAMP(datetime('now', 'localtime')) - UNIX_TIMESTAMP(start_time))   WHERE uid = '" . $this->uid . "' AND id = '" .intval($tid) . "'  ";
		}
		else
		{
			$sql = "UPDATE todo SET  is_done = '1' , check_time = datetime('now', 'localtime')   WHERE uid = '" . $this->uid . "' AND id = '" .intval($tid) . "'  ";
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
			
			publish_feed(  '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>完成了TODO - <a href="?m=todo&uid=' .$this->uid . '">' . $todo['name'] .'</a>' , $content , 'todo' , $this->uid .'-todo-stop-' . $tid  );
			
			if( $follow_uids = @unserialize($todo['follow_uids']) )
				send_notice( $follow_uids ,  '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>完成了TODO - <a href="?m=todo&uid=' . $this->uid . '">' . $todo['name'] .'</a>' , 'todo' );
			
			$this->return_obj['code'] = 1000;
			$this->return_obj['content'] = true;
			$this->send_return();	
			
		}
		else
		{
			$this->return_obj['code'] = 1000;
			$this->return_obj['content'] = false;
			$this->send_return();
		}
	}

		public function add_cast()
	{
		$cast = z(v('cast'));

		
		if( $cast == '' )
		{
			$this->return_obj['code'] = 1002;
			$this->return_obj['content'] = 'Bad Args - cast is not long enough';
			$this->send_return();
		}
		
		$sql = "INSERT INTO cast ( uid , name , link , timeline ) VALUES (  '" . $this->uid . "'  ,  '" . s($cast) . "'  ,  ''  ,   datetime('now', 'localtime')   )";
		
		run_sql( $sql );
		
		if( sqlite_last_error(db()) == 0 )
		{
		
			$content =  $cast ;
			
		
			
			if( t(v('silent')) == 'cast' )
			{
				//send_notice( 1 , '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>发起广播 - ' . $content . v('silent') , 'cast' );			
				publish_feed( '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>发起广播 - ' . $content , NULL , 'cast'  );
				if( $all_user = get_people_uids() )
					send_notice( $all_user , '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>发起广播 - ' . $content , 'cast' );
			}
			else
			{
				publish_feed( '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>更新了状态 - ' . $content , NULL , 'user'  );
			}
			
			$this->return_obj['code'] = 1000;
			$this->return_obj['content'] = true;
			$this->send_return();
		}
		else
		{
			$this->return_obj['code'] = 1000;
			$this->return_obj['content'] = false;
			$this->send_return();
		}
		
	}

	public function add_todo() 
	{
		$todo = z(t(v('todo')));
		if( strlen( $todo ) < 2 )
		{
			$this->return_obj['code'] = 1002;
			$this->return_obj['content'] = 'Bad Args - name is not long enough';
			$this->send_return();
		}

		$sql = "INSERT INTO todo (  name ,  uid , creator_uid , pid , is_done , follow_uids ,  timeline ) VALUES (    '" . s($todo) . "' ,'" . $this->uid . "'  , '" . $this->uid . "'  ,  '0' , '0'  , '' , datetime('now', 'localtime')  )";

		run_sql( $sql );

		if( sqlite_last_error(db()) == 0 )
		{
			publish_feed(   '<a href="?m=people&a=profile&uid=' . $this->uid . '">' . $this->uname . '</a>添加了新的TODO - <a href="?m=todo&uid=' . $this->uid . '">' . $todo .'</a>', NULL , 'todo'  , $this->uid . '-todo-add-' . last_id());
			
			$this->return_obj['code'] = 1000;
			$this->return_obj['content'] = true;
			$this->send_return();
		
		}
		else
		{
			$this->return_obj['code'] = 1000;
			$this->return_obj['content'] = false;
			$this->send_return();
		}
		
	}

	/*
	api.has_notice - 检查当前用户是否有新的通知
	return  obj.code = int , obj.content = int 
	*/
	public function has_notice() 
	{
		$message = get_var( "SELECT COUNT(*) FROM message WHERE to_uid = '" . $this->uid . "' AND is_read = 0" );
		$notice = get_var( "SELECT COUNT(*) FROM notification WHERE to_uid = '" . $this->uid . "' AND is_read = 0" );
		$all = $message + $notice;
		
		$all = $all>0?$all:false;

		$this->return_obj['code'] = 1000;
		$this->return_obj['content'] = $all;
		$this->send_return();
	}


	
	
	
	private function check_api_auth()
	{
		/*
		$email = $_POST['email'];
		$password = $_POST['password'];
		*/

		$email = $_GET['email'];
		$password = $_GET['password'];
	
		if( !$user = get_line( "SELECT * FROM user WHERE email = '" . s( $email ) . "' AND password = '" . s( $password ) . "' LIMIT 1" ) )
		{
			$this->return_obj['code'] = 1001;
			$this->return_obj['content'] = 'Auth failure';
			$this->send_return();
		}
		
		$this->uid = $user['id'];
		$this->uname = $user['name'];

	
		if( $info = get_line("SELECT * FROM session WHERE uid = '" . intval( $this->uid ) . "' LIMIT 1") )
		{
			$sql = "UPDATE session SET timeline = datetime('now', 'localtime')  WHERE uid = '" . intval($this->uid ) . "'";
		}
		else
			$sql = "INSERT INTO session ( uid , session_key , url , timeline ) VALUES (  '" . intval($this->uid) . "'  ,  'client'  ,  'client'  ,  datetime('now', 'localtime')  )";

		run_sql( $sql );
		
	}

	private function send_return() 
	{
		echo json_encode( $this->return_obj );
		exit;
	}

	


}