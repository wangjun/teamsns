<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class projectMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
	}
	
	
	public function index()
	{
		$old = intval(v('old'));
		if( $old > 0 ) 
			$where = " AND is_active = 0 ";
		else
			$where = " AND is_active = 1 ";
		
		$projects = get_data("SELECT * FROM project WHERE 1 $where ORDER BY timeline DESC LIMIT 20 ");
		$data['projects'] = $projects;
		$data['title'] = $data['top_title'] = '项目';
		return render( $data );
	}
	
	public function add()
	{
		$data['title'] = $data['top_title'] = '添加项目';
		return render( $data , 'ajax' );
	}
	
	public function save()
	{
		$name = z(t(v('name')));
		
		if( $name == '' ) return ajax_box('项目名称不能为空');
		
		if( get_var("SELECT COUNT(*) FROM project WHERE name = '" . s($name) . "'") > 0 ) return ajax_box( '项目名被占用,请不要重复建立项目' , '系统消息' , 3 );
		
		
		$sql = "INSERT INTO project ( name ,  creator_uid , timeline ) VALUES (  '" . s($name) . "'  ,  '" . uid() . "'  ,  datetime('now', 'localtime')  )";
		
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
		{
			publish_feed( '{actor}创建了新项目 <a href="?m=project">' . $name . '</a> ' , NULL , 'project'  );
			return ajax_box('添加成功' , NULL , 0.1 , '?m=project');
		}
				
		else
			return ajax_box('添加失败,请稍后再试' , NULL , 3 );
	}
	
	public function modify()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('项目ID');
		
		$data['name'] = get_var( "SELECT name FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1" );
		
		$data['pid'] = $pid;
		$data['title'] = $data['top_title'] = '项目';
		return render( $data , 'ajax' );
	}
	
	public function color()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('错误的项目ID');
		
		$data['pinfo'] = get_line( "SELECT * FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1" );
		$data['title'] = $data['top_title'] = '项目';
		return render( $data , 'ajax' );
	}
	
	public function color_update()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('错误的项目ID');
		
		$cid = intval(v('cid'));
		if( $cid < 1 ) return ajax_box('错误的颜色');

		$sql = "UPDATE project SET  color_id = '" . intval( $cid ) . "'   WHERE  id = '" .intval($pid) . "'  ";
		run_sql( $sql );

		if(  sqlite_last_error(db()) == 0 )
		{
			$pinfo = get_line( "SELECT * FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1" );
			
			publish_feed( '{actor}为项目 <a href="?m=project">' . $pinfo['name'] . '</a> 指定了颜色 ' , '<div style="background:' . color($cid) . ';width:16px;height:16px;border:1px solid #ccc;margin:5px;"></div>' , 'project' , uid() .'-project-color-' . date("Y-m-d")  );
			
			return ajax_box('更新成功' , NULL , 0.1 , '?m=project');
		}
			
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function update()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('错误的项目ID');
		
		$name = z(t(v('name')));

		if( $name == '' ) return ajax_box('项目名称不能为空');
		
		// update riki documents
		$old_tag = get_var("SELECT name FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1 ");
		
		$sql = "UPDATE project SET  name = '" . s($name) . "'  WHERE  id = '" .intval($pid) . "'   ";
		run_sql( $sql );
		
		if( sqlite_last_error(db()) != 0 )
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
			
		if( $content = get_var("SELECT content FROM riki WHERE tag = '" . s( $old_tag ) . "' ORDER BY timeline DESC LIMIT 1") )
		{
			$content = $content . '<p>本文档转移自 - [[' . $old_tag . ']]</p>';
			$sql = "INSERT INTO riki ( tag , content , uid , pid ,timeline ) VALUES (  '" . s($name) . "'  ,  '" . s($content) . "'  ,  '" . uid() . "'  , '" . intval($pid) . "' ,  datetime('now', 'localtime')  )";
			run_sql( $sql );
			
			if( sqlite_last_error(db()) != 0 )
				return ajax_box('更新失败,请稍后再试' , NULL , 3 );
			
		}
		
		if(  sqlite_last_error(db()) == 0 )
		{
			publish_feed( '{actor}将项目' . $old_tag . '改名为 <a href="?m=project">' . $name . '</a> ' , NULL , 'project'  );
			return ajax_box('更新成功' , NULL , 0.1 , '?m=project');
		}	
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}
	
	public function close_confirm()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('错误的项目ID');
		
		$pinfo = get_line( "SELECT * FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1" );
		if( ulevel() < 5 && $pinfo['creator_uid'] != uid() ) return ajax_box('您没有权限修改该项目');
		
		$data['name'] = $pinfo['name'];
		$data['pid'] = $pid;
		$data['title'] = $data['top_title'] = '项目';
		return render( $data , 'ajax' );
	}
	
	public function close()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('错误的项目ID');
		
		$pinfo = get_line( "SELECT * FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1" );
		if( ulevel() < 5 && $pinfo['creator_uid'] != uid() ) return ajax_box('您没有权限修改该项目');
		
		$sql = "UPDATE project SET  is_active = '0'   WHERE  id = '" .intval($pid) . "'  ";
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
		{
			publish_feed( '{actor}关闭了项目 <a href="?m=project">' . $pinfo['name'] . '</a> ' , NULL , 'project'  );
			return ajax_box('更新成功' , NULL , 0.1 , '?m=project');
		}
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
		
	}
	
	public function open()
	{
		$pid = intval(v('pid'));
		if( $pid < 1 ) return ajax_box('错误的项目ID');
		
		$pinfo = get_line( "SELECT * FROM project WHERE id = '" . intval( $pid ) . "' LIMIT 1" );
		if( ulevel() < 5 && $pinfo['creator_uid'] != uid() ) return ajax_box('您没有权限修改该项目');
		
		$sql = "UPDATE project SET  is_active = '1'   WHERE  id = '" .intval($pid) . "' ";
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , '?m=project');
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
	}

	
	
	
}


?>