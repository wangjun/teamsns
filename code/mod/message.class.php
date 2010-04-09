<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class messageMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
	}
	
	public function notice()
	{
		$is_new = '';
		//	$is_new = 'AND is_read = 0';
		
		$notices = get_data("SELECT * FROM notification WHERE to_uid = '" . uid() . "' $is_new ORDER BY timeline DESC  LIMIT 50 " );

		$sql = "UPDATE notification  SET is_read = 1 WHERE to_uid = '" . uid() . "'";
		run_sql( $sql );
		
		$data['notices'] = $notices;
		$data['title'] = $data['top_title'] = '通知';
		return render( $data );
	}
	
	public function index()
	{
		if( intval(v('all')) == 1 ) 
			$is_new = '';
		else
			$is_new = 'AND is_read = 0';
		$messages = get_data("SELECT *,COUNT(*) as count FROM ( SELECT * FROM message WHERE to_uid = '" . uid() . "' $is_new ORDER BY timeline DESC )  m LEFT JOIN user as u ON ( m.from_uid = u.id  ) GROUP BY m.from_uid LIMIT 20 " );
	
		$data['messages'] = $messages;
		$data['title'] = $data['top_title'] = '消息';
		return render( $data );
	}
	
	public function record()
	{
	
		$to_uid = intval(v('uid'));
		if( $to_uid < 1 ) return ajax_echo('错误的用户ID');
		$to_user = get_line("SELECT * FROM user WHERE id = '" . intval($to_uid) . "' LIMIT 1");
		$history = get_data("SELECT * FROM message WHERE (to_uid = '". intval($to_uid) ."' AND from_uid = '" . uid() . "' ) OR  (from_uid = '". intval($to_uid) ."' AND to_uid = '" . uid() . "' ) ORDER BY timeline DESC LIMIT 20 ");
		
		$sql = "UPDATE message SET is_read = 1 WHERE to_uid = '" . uid() . "' AND from_uid = '" . intval($to_uid) . "' ";
		run_sql( $sql );
			
		$data['history'] = $history;
		$data['to_user'] = $to_user;
		
		return render( $data , 'ajax' );
		
		
		
	}
	
	public function box()
	{
		$to_uid = intval(v('to_uid'));
		if( $to_uid < 1 ) return ajax_box('错误的用户ID');
		$to_user = get_line("SELECT * FROM user WHERE id = '" . intval($to_uid) . "' LIMIT 1");
		
		$history = get_data("SELECT * FROM message WHERE (to_uid = '". intval($to_uid) ."' AND from_uid = '" . uid() . "' ) OR  (from_uid = '". intval($to_uid) ."' AND to_uid = '" . uid() . "' ) ORDER BY timeline DESC LIMIT 10 ");
		
		$sql = "UPDATE message SET is_read = 1 WHERE to_uid = '" . uid() . "' AND from_uid = '" . intval($to_uid) . "' ";
		run_sql( $sql );
		
		$data['history'] = $history;
		$data['to_uid'] = $to_uid;
		$data['to_user'] = $to_user;
		return render( $data , 'ajax' );
	}
	
	public function send()
	{
		$to_uid = intval(v('to_uid'));
		if( $to_uid < 1 ) return ajax_box('错误的用户ID');
		
		$content = z(t(v('content')));
		if( strlen( $content ) < 2 ) return ajax_box('消息长度不能少于两个字符');
		
		if( ss('last_message') == $content ) return ajax_box('刚刚发送过同样的消息了');
		ss_set('last_message' , $content);
		
		$title = mb_substr( $content , 0 , 10 , 'UTF-8' );
		
			$sql = "UPDATE message SET is_read = 1 WHERE to_uid = '" . uid() . "' AND from_uid = '" . intval($to_uid) . "' ";
			run_sql( $sql );
		
		$sql = "INSERT INTO message ( from_uid , to_uid , title , content , timeline ) VALUES (  '" . uid() . "'  ,  '" . intval($to_uid) . "'  ,  '" . s($title) . "'  ,  '" . s($content) . "'  ,  datetime('now', 'localtime')  )";
		
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
			return ajax_box('发送成功' , NULL , 1 );
		else
			return ajax_box('发送失败,请稍后再试' , NULL , 3 );
		
		
	}
	
	function read()
	{
		$to_uid = intval(v('to_uid'));
		if( $to_uid < 1 ) return ajax_box('错误的用户ID');
		
		$sql = "UPDATE message SET is_read = 1 WHERE to_uid = '" . uid() . "' AND from_uid = '" . intval($to_uid) . "' ";
		run_sql( $sql );	
		
		if(  sqlite_last_error(db()) == 0 )
			return ajax_box('标记成功' , NULL , 1 , '?m=message' );
		else
			return ajax_box('标记失败,请稍后再试' , NULL , 3 );
	}
	
	function check()
	{
		$message = get_var( "SELECT COUNT(*) FROM message WHERE to_uid = '" . uid() . "' AND is_read = 0" );
		$notice = get_var( "SELECT COUNT(*) FROM notification WHERE to_uid = '" . uid() . "' AND is_read = 0" );
		$all = $message + $notice;
		
		$avatar_script = false;
		if( $message > 0 )
		{
			$sql = "SELECT from_uid , count(from_uid) as mcount FROM message WHERE to_uid = '" . uid() . "' AND is_read = 0 GROUP BY from_uid  ";
			
			
			if( $mdata = get_data( $sql ) )
				foreach( $mdata as $mline )
					$avatar_script[] = 'rock_avatar( ' . $mline['from_uid'] . ' , ' . $mline['mcount'] .  ' );';
			
			
		}
		
		if( $message > 0 )
		{
			?>
				<script>
					flash_title();
					$('pm').set('html' , '<a href="?m=message">私信(<?=intval($all)?>)</a>' );
					(function(){play_sound('message')}).delay(500);
					<?php if( $avatar_script ):  ?>
					(function(){ <?php echo @join( ';' , $avatar_script ) ?> }).delay(200);
					<?php endif; ?>
				</script>
			<?php
		}
		elseif( $notice > 0 )
		{
			?>
				<script>
					flash_title();
					$('pm').set('html' , '<a href="?m=message&a=notice">通知(<?=intval($all)?>)</a>' );
					(function(){play_sound('message')}).delay(500);
				</script>
			<?php
		}
		else
		{
			?>	<script>
					flash_title_stop();
					if($('__play_message_sound')) $('__play_message_sound').destroy();
					$('pm').set('html' , '<a href="?m=message">消息</a>' );
				</script>
			<?php
		}
	}
	

	

	
	
	
}


?>