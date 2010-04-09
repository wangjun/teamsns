<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class reportMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
		//error_reporting( E_ALL^E_NOTICE );
	}
	
	public function index()
	{
		$uid = intval(v('uid'));
		if( $uid > 0 ) 
			$usql = " AND uid = '" . intval( $uid ) . "' ";
		else
			$usql = "  ";
			
		
		
		$plan = intval(v('plan'));
		
		if( $plan == 1 )
		{
			// todo
			$sql = "SELECT *,t.name as name, u.name as uname , t.timeline as timeline , t.check_time as check_time , t.uid as uid , t.pid as pid , t.start_time as start_time FROM todo as t LEFT JOIN user as u ON ( t.uid = u.id ) WHERE 1 $usql AND is_done = 0 AND is_slow = 0 ORDER BY pid DESC , uid ASC ,  t.timeline ASC LIMIT 100 ";
		}
		elseif( $plan == 2 )
		{
			// 冻结
			$sql = "SELECT *,t.name as name, u.name as uname , t.timeline as timeline , t.check_time as check_time , t.uid as uid , t.pid as pid  , t.start_time as start_time FROM todo as t LEFT JOIN user as u ON ( t.uid = u.id ) WHERE 1 $usql AND is_done = 0 AND is_slow = 1 ORDER BY pid DESC , uid ASC ,  t.timeline ASC LIMIT 100 ";
		}
		elseif( $plan == 3 )
		{
			// 进行中
			$sql = "SELECT *,t.name as name, u.name as uname , t.timeline as timeline , t.check_time as check_time , t.uid as uid , t.pid as pid , t.start_time as start_time FROM todo as t LEFT JOIN user as u ON ( t.uid = u.id ) WHERE 1 $usql AND is_done = 0 AND is_slow = 0 AND is_start = 1 ORDER BY pid DESC , uid ASC ,  t.timeline ASC LIMIT 100 ";
		}
		else
		{
			$date_start = z(v('date_start'));
			$date_end = z(v('date_end'));
			
			if( $date_start != '' && $date_end != '' )
			{
				$start = $date_start;
				$end = $date_end;
				$text = '指定时间段';
			}
			else
			{
				$type = z(t(v('type')));
				$ret = get_time( $type );
				@extract($ret);
			}
			
			
			
			$sql = "SELECT *,t.name as name, u.name as uname , t.timeline as timeline  , t.check_time as check_time , t.uid as uid , t.pid as pid FROM todo as t LEFT JOIN user as u ON ( t.uid = u.id ) WHERE 1 $usql AND t.is_done = 1 AND t.check_time >= '$start 00:00:01' AND t.check_time <= '$end 23:59:59' ORDER BY t.pid DESC , t.uid ASC ,  t.check_time ASC  LIMIT 100 ";
		}
	
	
		
		//echo $sql;
		
		$ret = array();
		$users = array();
		if( $todo_done = get_data($sql) )
		{
			foreach( $todo_done as $item )
			{
				//print_r( $item );
				if( $plan > 0 )
					$date = date("m月d日" , strtotime( $item['timeline'] ));
				else
					$date = date("m月d日" , strtotime( $item['check_time'] ));

				$ret[intval($item['pid'])][$item['uid']][$date][] = $item;
				if( !isset( $users[$item['uid']] ) )  $users[$item['uid']] = $item['uname'];
			}
		}

		//print_r( $todo_done );

		$data['plan'] = $plan;
		$data['todo_done'] = $ret;
		$data['users'] = $users;
		$data['title'] = $data['top_title'] = '报告';
		
		if( $plan < 1 )
		{
			$data['text'] = $text ;
			$data['start'] = $start ;
			$data['end'] = $end ;
		}
		else
		{
			$data['notime'] = 1;
		}
		
		$data['js'][] = 'calendar.compat.js';
		$data['css'][] = 'calendar.css';
		return render( $data );

		
	}
	
	function customize()
	{
		$data['pid'] = v('pid');
		$data['uid'] = v('uid');
		return render( $data , 'ajax' );
	}
	

	
}