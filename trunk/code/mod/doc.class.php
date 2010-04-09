<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/controller.class.php' );

class docMod extends controller
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		$this->check_login();
	}
	
	
	public function index()
	{
		//echo 'It works';
		$tag = z(v('tag'));
		if( $tag == '' ) $tag = uname() . '的个人页面';
		
		if( isset( $_SESSION['vtags'] ) )
		{
			if( !in_array( $tag , $_SESSION['vtags'] ) )
			{
				if( count( $_SESSION['vtags'] ) >= 5 )
					array_shift( $_SESSION['vtags'] );
				
				$_SESSION['vtags'][] = $tag; 
				
			}
		}
		else
		{
			$_SESSION['vtags'] = array($tag); 
		}
		
		
		
		if( $riki = get_line( "SELECT * FROM riki WHERE tag = '" . s( $tag ) . "' ORDER BY timeline DESC LIMIT 1" ) )
		{
			$content = $riki['content'];
			$content = preg_replace_callback ( '/\[\[(.+?)\]\]/is' , 
					create_function(     
						'$matches',
			            'return "<a href=?m=doc&a=index&tag=" . urlencode($matches[1]) . ">" . $matches[1] . "</a>";'
			        )
					, $content );
			
		}
		else
		{
			$content = @file_get_contents( CROOT . 'view/share/riki.default.tpl.html' );
		}
		
		if( $followers = get_data("SELECT uid FROM follow WHERE tag = '" . s( $tag ) . "' ") )
			foreach( $followers as $f )
				$follower_uids[] = $f['uid'];
				
		if( isset( $follower_uids ) )
			$data['follower_uids'] = $follower_uids;
			
		$data['tag'] = $tag;
		$data['content'] = $content;
		$data['vtags'] = $_SESSION['vtags'];
		
		$data['projects'] = get_data("SELECT name FROM project WHERE is_active = 1 ORDER BY timeline DESC LIMIT 10");
		
		$data['title'] = $data['top_title'] = '文档';
		return render( $data );
	}
	
	public function modify()
	{
		$tag = z(v('tag'));
		if( $tag == '' ) $tag = uname() . '个人页面';
		
		if( $riki = get_line( "SELECT * FROM riki WHERE tag = '" . s( $tag ) . "' ORDER BY timeline DESC LIMIT 1" ) )
		{
			$content = $riki['content'];
		}
		else
		{
			$content = '';
		}
		
		if( $lock = get_line( "SELECT * FROM rikilock WHERE tag = '" . s( $tag ) . "' ORDER BY edit_timeline DESC LIMIT 1" ) )
		{
			if( (time() - strtotime($lock['edit_timeline'])) < 60*60*1  && $lock['edit_uid'] != uid()  )
			{
				$data['warning'] = '您的同事<a href="?m=people&a=profile&uid=' . $lock['edit_uid'] . '">' . $lock['edit_uname'] . '</a>在'. date("d日 H:i:s" , strtotime( $lock['edit_timeline'] )) .'开始编辑了本页,如果您继续编辑,TA所做的修改将丢失.<a href="javascript:history.back(1);void(0)">点击返回</a> 或者 <a href="javascript:show_float_box(\'?m=message&a=box&to_uid=' . $lock['edit_uid'] . '\')">给TA发送私信</a>';
				
				send_notice( $lock['edit_uid'] , '{actor}试图编辑您正在编辑的文档<a href="?m=doc&tag=' . urlencode($tag) . '">' . $tag . '</a>,<a href="javascript:show_float_box(\'?m=message&a=box&to_uid=' . uid() . '\');void(0)">和TA聊聊吧</a>' , 'doc' , uid() . '-doc-lock-' . $tag . '-' . date("Y-m-d-H") , false  );
			}
			
		}
		else
		{
			// lock it
			$sql = "REPLACE INTO rikilock ( tag , edit_uid , edit_uname , edit_timeline ) VALUES (  '" . s($tag) . "' ,  '" . intval(uid()) . "'  ,  '" . s(uname()) . "'  ,  datetime('now')  )";
			
			run_sql( $sql );
		}
		
		
		
		
		include_once( CROOT . 'ext/fckeditor/fckeditor.php' );
		
		$sBasePath = 'code/ext/fckeditor/' ;

		$oFCKeditor = new FCKeditor('content') ;
		$oFCKeditor->BasePath	= $sBasePath ;
		$oFCKeditor->Value		= $content ;
		$oFCKeditor->Height = '400px';
		$data['fck'] = $oFCKeditor->CreateHtml() ;
		
		$data['pid'] = $riki['pid'];
		$data['tag'] = $tag;
		$data['title'] = $data['top_title'] = '编辑项目页';
		return render( $data );
		
	}
	
	public function unlock()
	{
		$tag = z(v('tag'));
		if( $tag == '' ) return ajax_box('tag错误');
		
		$sql = "DELETE FROM rikilock WHERE edit_uid = '" . intval(uid()) . "' AND tag = '" . s($tag) . "' ";
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , '?m=doc&tag=' . urlencode($tag) );
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
		
	}
	
	public function update()
	{
		$tag = z(v('tag'));
		if( $tag == '' ) return info_page('tag错误');
		
		$pid = intval(v('pid'));
		
		$content = v('content');
		
		$sql = "INSERT INTO riki ( tag , content , uid , pid , timeline ) VALUES (  '" . s($tag) . "'  ,  '" . s($content) . "'  ,  '" . uid() . "'  , '" . intval($pid) . "' ,  datetime('now')  )";
		
		run_sql( $sql );
		
		$sql = "DELETE FROM rikilock WHERE edit_uid = '" . intval(uid()) . "' AND tag = '" . s($tag) . "' ";
		run_sql( $sql );
		
		if( $followers = get_data("SELECT uid FROM follow WHERE tag = '" . s( $tag ) . "' ") )
			foreach( $followers as $f )
				$follower_uids[] = $f['uid'];
				
		if( isset( $follower_uids ) )
			send_notice( $follower_uids , '{actor}更新了文档 - <a href="?m=doc&tag=' . urlencode( $tag ) . '">' . $tag .'</a>' , 'doc' );
			
		
		publish_feed( '{actor}更新了文档 - <a href="?m=doc&tag=' . urlencode( $tag ) . '">' . $tag .'</a>' , NULL , 'doc' , uid() . '-doc-' . $tag .'-' . date("Y-m-d H")  );
		
		
		
		$f = '?m=doc&a=index&tag=' . urlencode($tag);
		header('Location:' . $f  );
		
		info_page( '<a hre="' . $f . '">点击这里返回</a>' );
	}
	
	public function follow()
	{
		$tag = z(v('tag'));
		if( $tag == '' ) return info_page('tag错误');
		
		$do = intval(v('do'));
		if( $do > 0 )
			$sql = "INSERT INTO follow ( tag , uid , timeline ) VALUES (  '" . s($tag) . "'  ,  '" . uid() . "'  ,  datetime('now')  )";
		else
			$sql = "DELETE FROM follow WHERE uid = '" . uid() . "' AND tag = '" . s( $tag ) . "'";
		
		run_sql( $sql );
		
		if(  sqlite_last_error(db()) == 0 )
			return ajax_box('更新成功' , NULL , 0.1 , '?m=doc&tag=' . urlencode($tag) );
		else
			return ajax_box('更新失败,请稍后再试' , NULL , 3 );
		
	}
	
	
	
}


?>