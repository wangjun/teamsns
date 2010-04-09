<?php
// the main 

// lazy functiones

function code_mode()
{
	if( !isset( $GLOBALS['LZ_CODE_MODE'] ) )
	{
		$GLOBALS['LZ_CODE_MODE'] = 'dev';
	}
	return $GLOBALS['LZ_CODE_MODE'];
}

if(!function_exists('sqlite_last_error')){
	function sqlite_last_error($db)
	{
		return $db->errorCode();
	}
}

if(!function_exists('sqlite_last_changes')){
	function sqlite_last_changes($db,$ret=null)
	{
		if(function_exists('sqlite_changes') && !defined('IN_SQLITE3') && !IN_SQLITE3){
			return sqlite_changes($db);
		}
		else
		{
			return $ret;
		}
	}
}

function v( $str )
{
	return isset( $_REQUEST[$str] ) ? $_REQUEST[$str] : false;
}

function z( $str )
{
	return strip_tags( $str );
}

function c( $str )
{
	return isset( $GLOBALS['config'][$str] ) ? $GLOBALS['config'][$str] : false;
}

function g( $str )
{
	return isset( $GLOBALS[$str] ) ? $GLOBALS[$str] : false;	
}

function e($message = null,$code = null) 
{
	throw new Exception($message,$code);
}

function t( $str )
{
	return trim($str);
}

// session management
function ss( $key )
{
	return isset( $_SESSION[$key] ) ?  $_SESSION[$key] : false;
}

function ss_set( $key , $value )
{
	return $_SESSION[$key] = $value;
}

function uid()
{
	return ss('uid');
}

function uname()
{
	return ss('name');
}

function ulevel()
{
	return ss('level');
}


// render functiones
function render( $data = NULL , $layout = 'default' )
{
	if( code_mode() == 'test' ) return $data;
	$GLOBALS['layout'] = $layout;
	$layout_file = CROOT . 'view/layout/' . $layout . '/index.tpl.html';
	if( file_exists( $layout_file ) )
	{
		@extract( $data );
		require( $layout_file );
	}
}

function info_page( $info , $layout = 'default' )
{
	$GLOBALS['m'] = 'default';
	$GLOBALS['a'] = 'info';
	$data['title'] = $data['top_title'] = '系统消息';
	$data['info'] = $info;
	render( $data , $layout );
}


// pdo version

function db()
{
	if( !isset( $GLOBALS['LZ_DB'] ) )
	{
		
		$db_config = $GLOBALS['config']['db'];
		try
		{
			 $GLOBALS['LZ_DB'] = new PDO("sqlite:" . $db_config['file_name'] );

			$GLOBALS['LZ_DB']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	
		}
		catch(PDOException $e)
		{
			echo $e->getMessage();
			return false;
		}
				
		//$GLOBALS['LZ_DB']->exec( "SET NAMES UTF8" );
		$GLOBALS['LZ_DB']->sqliteCreateFunction( 'UNIX_TIMESTAMP' ,  'UNIX_TIMESTAMP' );
	}

	
	return $GLOBALS['LZ_DB'];
}


function s( $str )
{
	return substr(db()->quote( $str ),1,-1);
}

function get_data( $sql )
{
	$GLOBALS['LZ_LAST_SQL'] = $sql;
	$data = Array();
	try 
	{
		$result = db()->query($sql);

		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			$data[] = $row;
		}
	}
	catch(PDOException $e)
    {
		echo $GLOBALS['LZ_LAST_SQL'];
		echo $e->getMessage();
		return false;
    }

	if( count( $data ) > 0 )
		return $data;
	else
		return false;
}

function get_line( $sql )
{
	$data = get_data( $sql );
	return @reset($data);
}

function get_var( $sql )
{
	$data = get_line( $sql );
	return $data[ @reset(@array_keys( $data )) ];
}

function last_id()
{
	return db()->lastInsertId();
}



function run_sql( $sql ) 
{
	try 
	{
		$ret = db()->exec( $sql );
	}
	catch( PDOException $e  )
	{
		echo $sql;
		echo $e->getMessage();
		return false;
	}
	
	return $ret;
}

function close_db()
{
	$GLOBALS['LZ_DB'] = NULL ;
}

/*
function UNIX_TIMESTAMP( $str )
{ 
	return strtotime( $str ); 
}
*/

function UNIX_TIMESTAMP( $str ) { return strtotime( $str ); }

//sqlite_create_function( db() , 'UNIX_TIMESTAMP' ,  'UNIX_TIMESTAMP' );
/*
function s( $str )
{
	return sqlite_escape_string( $str  );
}

function db()
{
	if( !isset($GLOBALS['__SQLITE_INSTANCE']) )
	{
		$db_config = c('db');
		if( !$GLOBALS['__SQLITE_INSTANCE'] = sqlite_open( $db_config['file_name'] ) )
		{
			echo 'bad connect';
		}
		
	}
	sqlite_create_function( $GLOBALS['__SQLITE_INSTANCE'] , 'UNIX_TIMESTAMP' ,  'UNIX_TIMESTAMP' );
	return $GLOBALS['__SQLITE_INSTANCE'];
}


function get_data( $sql )
{
	
	$GLOBALS['LZ_LAST_SQL'] = $sql;
	$data = Array();
	$i = 0;

	
	$result = sqlite_query( db() , $sql );
	
	if( sqlite_last_error(db()) != 0 )
		echo sqlite_error_string(sqlite_last_error(db())) .' ' . $sql;
	
	while( $Array = sqlite_fetch_array( $result, SQLITE_ASSOC  ) )
	{
		$data[$i++] = $Array;
	}
	
	if( sqlite_last_error(db()) != 0 )
		echo sqlite_error_string(sqlite_last_error(db())) .' ' . $sql;
		
	
	if( count( $data ) > 0 )
		return $data;
	else
		return false;
}

function get_line( $sql )
{
	$data = get_data( $sql );
	return @reset($data);
}

function get_var( $sql )
{
	$data = get_line( $sql );
	return $data[ @reset(@array_keys( $data )) ];
}

function last_id()
{
	return sqlite_last_insert_rowid( db());
}

function run_sql( $sql )
{
	$GLOBALS['LZ_LAST_SQL'] = $sql;
	
	if( !$ret = sqlite_exec( $sql , db() ) )
		echo sqlite_error_string(sqlite_last_error(db())) .' ' . $sql;
	
	return $ret;	
}

function close_db()
{
	sqlite_close( $GLOBALS['LZ_DB'] );
}
*/


function get_sqls_from_file( $file ) 
{
	$sql = preg_replace( "/(#.+[\r|\n]*)/" , '' , file_get_contents( $file ));
	$sql = preg_replace( "/(--.+[\r|\n]*)/" , '' , $sql );
	//echo $files;
	
	$sql               = trim($sql);
	$delimiter	= ';';
	$char              = '';
	$last_char         = '';
	$ret               = array();
	$string_start      = '';
	$in_string         = FALSE;
	$escaped_backslash = FALSE;

	for ($i = 0; $i < strlen($sql); ++$i) {
	    $char = $sql[$i];

	    // if delimiter found, add the parsed part to the returned array
	    if ($char == $delimiter && !$in_string) {
	        $ret[]     = substr($sql, 0, $i);
	        $sql       = substr($sql, $i + 1);
	        $i         = 0;
	        $last_char = '';
	    }

	    if ($in_string) {
	        // We are in a string, first check for escaped backslashes
	        if ($char == '\\') {
	            if ($last_char != '\\') {
	                $escaped_backslash = FALSE;
	            } else {
	                $escaped_backslash = !$escaped_backslash;
	            }
	        }
	        // then check for not escaped end of strings except for
	        // backquotes than cannot be escaped
	        if (($char == $string_start)
	            && ($char == '' || !(($last_char == '\\') && !$escaped_backslash))) {
	            $in_string    = FALSE;
	            $string_start = '';
	        }
	    } else {
	        // we are not in a string, check for start of strings
	        if (($char == '"') || ($char == '\'') || ($char == '')) {
	            $in_string    = TRUE;
	            $string_start = $char;
	        }
	    }
	    $last_char = $char;
	} // end for

	// add any rest to the returned array
	if (!empty($sql)) {
	    $ret[] = $sql;
	}
	return $ret;
 }



function ajax_echo( $info )
{
	if( code_mode() == 'test' )
	{
		return $info;
	}
	else
	{
		header("Content-Type:text/xml;charset=utf-8");
		header("Expires: Thu, 01 Jan 1970 00:00:01 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		echo $info;
	}
}

function ajax_box( $content , $title = '系统消息' , $close_time = 0 , $forward = '' )
{
	if( code_mode() == 'test' )
	{
		return $content;
	}
	else
	{
		require_once( CROOT . 'view/layout/ajax/box.tpl.html' );
	}
}

function fliter( $array , $pre )
{
	$ret = array();
	foreach( $array as $key=>$value )
	{
		if( strpos( $key , $pre ) === 0 )
			$ret[$key] = $value;
	}
	return $ret;
}

function uses( $m )
{
	include_once( CROOT . 'function/' . basename($m) . '.function.php' );
}

function is_login()
{
	return ss('uid') > 0;
}

function menu()
{
	if( !isset( $GLOBALS['config']['menu'] ) )
		include_once( CROOT .  'config/menu.config.php' );
	
	return $GLOBALS['config']['menu'];
}

function tab()
{
	if( !isset( $GLOBALS['config']['tab'] ) )
		include_once( CROOT .  'config/menu.config.php' );
	
	return $GLOBALS['config']['tab'];
}

function get_innerhtml( $type , $data )
{
	$tpl_file = CROOT . 'view/component/' . basename( $type ) . '.tpl.html';
	if( file_exists( $tpl_file ) )
	{
		ob_start();
		@extract( $data );
		require( $tpl_file );
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	else
		return false;
}


function get_user_icon( $uid , $type = 'small' )
{
	$user_file = 'static/data/user/u' . intval($uid) . '.' . basename( $type ) . '.uif'; 
	$default_file = 'static/data/user/u0.' . basename( $type ) . '.uif';
	
	if( file_exists( $user_file ) )
		return $user_file;
	else
		return $default_file;
}

function logout()
{
	foreach( $_SESSION as $key=>$value )
		unset( $_SESSION[$key] );
/*
		unset($_SESSION['uid']);
		unset($_SESSION['name']);
		unset($_SESSION['email']);
		unset($_SESSION['level']);
*/
}

function icon( $file , $icon , $size = 32 )
{
	if( !isset($GLOBALS['icon']) )
	{
		require_once( CROOT . 'function/icon.class.php' );
		$GLOBALS['icon'] = new icon();
	}
	
	$GLOBALS['icon']->path = $file;
	$GLOBALS['icon']->size = $size;
	$GLOBALS['icon']->dest = $icon;
	$GLOBALS['icon']->createIcon();
	
	return file_exists( $icon );
}

function online_uid()
{
	if( !isset( $GLOBALS['online_uid'] )  )
	{
		if( $uids = get_data("SELECT uid FROM session WHERE timeline >= '" . date( "Y-m-d H:i:s" , time() - c('online_time') ) . "'" ) )
		{
			$uid_array = array();
			foreach( $uids as $uid )
				$uid_array[] = $uid['uid'];
			
			$GLOBALS['online_uid'] = $uid_array;
		}
		else
			$GLOBALS['online_uid'] =array();
	}
	
	return $GLOBALS['online_uid'];
	
}

function online()
{
	if( !is_login() ) return false;
	
	// gc
	if( rand( 1 , 20 ) == 10 )
		run_sql("DELETE FROM session WHERE timeline < '" . date( "Y-m-d H:i:s"  , time() - c('online_time') ) . "'");

	// timeline >= '" . date( "Y-m-d H:i:s" , time() - c('online_time') ) . "'"
	
	$session_key = $_COOKIE[session_name()]; 
	$url = '?m=' . g('m') .'&a=' . g('a');
	
	if( $info = get_line("SELECT * FROM session WHERE uid = '" . intval(uid()) . "' LIMIT 1") )
	{
		if( $info['force_logout'] == '1' )
		{
			$uid = uid();
			logout();
			run_sql("DELETE FROM session WHERE uid = '" . intval($uid) .  "'");
			info_page('<a href="?m=user&a=login">您被强制退出,请重新登录</a>');
			exit;
		}
		
		$sql = "UPDATE session SET timeline = datetime('now', 'localtime') , session_key = '" . s( $session_key ) . "' WHERE uid = '" . intval(uid()) . "' ";
	}
	else
		$sql = "INSERT INTO session ( uid , session_key , url , timeline ) VALUES (  '" . intval(uid()) . "'  ,  '" . s($session_key) . "'  ,  '" . s($url) . "'  ,  datetime('now', 'localtime')  )";
	
	run_sql( $sql );
	return  sqlite_last_error(db()) == 0;
}

function short_tag( $tag , $len = 10 )
{
	if( mb_strlen( $tag , 'UTF-8' ) > $len )
		return mb_substr( $tag , 0 , $len-2 , 'UTF-8' ).'..';
	else
		return $tag;
}

function color( $id = 1 )
{
	if( $id == 1 ) return 'red';
	if( $id == 2 ) return 'green';
	if( $id == 3 ) return '#F59709';
	if( $id == 4 ) return '#660099';
	if( $id == 5 ) return '#000066';
	if( $id == 6 ) return '#33FF00';
	if( $id == 7 ) return '#FFCCFF';
}

function project()
{
	if( !isset($GOLBALS['projects']) )
	{
		$data = get_data("SELECT * FROM project LIMIT 100");
		$ret = array();
		if( is_array( $data ) )
			foreach( $data as $item )
				$ret[$item['id']] = $item;
		
		$GOLBALS['projects'] = $ret;
	}
	
	return $GOLBALS['projects'];
}

function get_project_name( $pid )
{
	$pid = intval( $pid );
	$pinfo = project();
	
	if( $pid == 0 || !isset( $pinfo[$pid] ) ) 
		return '未指定项目';
	else
		return $pinfo[$pid]['name'];
}

function the( $array , $key = NULL )
{
	if( $key == NULL ) return reset( $array );
	return isset( $array[$key] ) ? $array[$key] : false;
}


function sec2time($time)
{
	if(is_numeric($time))
	{
    	$value = array();

		if($time >= 31556926)
		{
			$value["year"] = floor($time/31556926);
			$time = ($time%31556926);
		}
		
		if($time >= 86400)
		{
			$value["day"] = floor($time/86400);
			$time = ($time%86400);
		}

		if($time >= 3600)
		{
			$value["hour"] = floor($time/3600);
			$time = ($time%3600);
		}

		if($time >= 60)
		{
			$value["minute"] = floor($time/60);
			$time = ($time%60);
		}

		$value["second"] = floor($time);
		
		$str = '';
		if( isset( $value['year'] ) ) $str .= $value['year'] . '年';
		if( isset( $value['day'] ) ) $str .= $value['day'] . '天';
		if( isset( $value['hour'] ) ) $str .= $value['hour'] . '小时';
		if( isset( $value['minute'] ) ) $str .= $value['minute'] . '分';
		if( isset( $value['second'] ) ) $str .= $value['second'] . '秒';
		
		return  $str;		
	}
	else
		return false;

}

function get_time( $type = 'today' )
{
	$startTsCurrentWeek = mktime(0, 0, 0, date('m'), date('d') - date('w'), date('Y'));
	$startTsNextWeek = $startTsCurrentWeek + (7*86400);
	$startTsPreviousWeek = $startTsCurrentWeek - (7*86400);
	
	switch( $type )
	{
		case 'today':
		default:
			$start = date( "Y-m-d 00:00:01");
			$end = date( "Y-m-d H:i:s" );
			$text = '今日';
			break;
		
		case 'yestoday':
			$start = date( "Y-m-d 00:00:01" , strtotime("-1 day"));
			$end = date( "Y-m-d 00:00:01" );
			$text = '昨日';
			break;
			
		case 'thisweek':
			$start = date( "Y-m-d 00:00:01" , $startTsCurrentWeek);
			$end = date( "Y-m-d H:i:s" , $startTsNextWeek );
			$text = '本周';
			break;	
		
		case 'lastweek':
			$start = date( "Y-m-d 00:00:01" , $startTsPreviousWeek);
			$end = date( "Y-m-d H:i:s" , $startTsCurrentWeek );
			$text = '上周';
			break;	
	}
	
	return array( 'start' => $start , 'end' => $end , 'text' => $text );
	
}

function get_people( $self = false )
{
	if( $self )
		$usql = '';
	else
		$usql = "AND id != '" . uid() . "'";
	if( !isset( $GLOBALS['team_people'] ) )
	{
		$GLOBALS['team_people'] = get_data("SELECT * FROM user WHERE level > 0 $usql  ORDER BY id ASC LIMIT 10");
	}
	
	return $GLOBALS['team_people'];
}

function get_people_name( $uid )
{
	if( $array = get_people(true) )
	{
		foreach( $array as $item )
		{
			if( $item['id'] == $uid ) return $item['name'];
		}
		return false;
	}
	return false;

}

function get_people_uids()
{
	if( $array = get_people(true) )
	{
		foreach( $array as $item )
		{
			$ret[] = $item['id'];
		}
		if( isset( $ret ) ) return $ret;
	}
	
	return false;
}

function force_logout( $uid )
{
	$sql = "INSERT INTO session ( uid , force_logout ) VALUES (  '" . intval($uid) . "'  ,  '1'  )";
	run_sql( $sql );
	return  sqlite_last_error(db()) == 0;
}


function publish_feed( $title , $content ='' , $aname = 'system' , $res_id = NULL , $replace = true )
{
	$title = str_replace( '{actor}' , '<a href="?m=people&a=profile&uid=' . uid() . '">' . uname() . '</a>' , $title );
	$content = str_replace( '{actor}' , '<a href="?m=people&a=profile&uid=' . uid() . '">' . uname() . '</a>' , $content );
	
	if( $res_id === NULL ) $res_id = uid() .'-' . $aname . '-' . time(); 
	if( !$replace && ( get_var( "SELECT COUNT(*) FROM newsfeed WHERE res_id = '" . $res_id . "'" ) > 0 ) ) return true;

	$sql = "REPLACE INTO newsfeed ( aname , uid , title , content , timeline , res_id ) VALUES (  '" . s($aname) . "'  ,  '" . uid() . "'  , '" . s($title) . "'  , '" . s($content) . "'  ,  datetime('now', 'localtime')  ,  '" . s($res_id) . "'  )";
	
	run_sql( $sql );
	
	return  sqlite_last_error(db()) == 0;
}

function send_notice( $to_uids , $content , $aname = 'system' , $res_id = NULL , $replace = true  )
{
	$content = str_replace( '{actor}' , '<a href="?m=people&a=profile&uid=' . uid() . '">' . uname() . '</a>' , $content );
	
	//$sql = "REPLACE INTO notification ( to_uid , aid , content , timeline , res_id ) VALUES ";
	$sql = "";
	
	if( is_array( $to_uids ) )
	{
		foreach( $to_uids as $uid )
		{
			if( $res_id === NULL ) 
				$new_res_id = uid() . '-' . $uid .'-' . $aname . '-' . time(); 
			else	
				$new_res_id = $res_id;
				
			if( !$replace && ( get_var( "SELECT COUNT(*) FROM notification WHERE res_id = '" . $new_res_id . "'" ) > 0 ) ) return true;	
			$vsql[] = "REPLACE INTO notification ( to_uid , aname , content , res_id , timeline ) VALUES  (  '" . intval($uid) . "'  ,  '" . s($aname) . "'  ,  '" . s($content) . "'  ,  '" . s($new_res_id) . "'  ,  datetime('now', 'localtime')  )";
		}
			
		
	}
	else
	{
		if( $res_id === NULL ) $res_id = uid() . '-' . $to_uids .'-' . $aname . '-' . time(); 
		if( !$replace && ( get_var( "SELECT COUNT(*) FROM notification WHERE res_id = '" . $res_id . "'" ) > 0 ) ) return true;
		$vsql[] = "REPLACE INTO notification ( to_uid , aname , content , res_id , timeline ) VALUES (  '" . intval($to_uids) . "'  ,  '" . s($aname) . "'  ,  '" . s($content) . "'  ,  '" . s($res_id) . "'  ,  datetime('now', 'localtime')  )";
	}
		
	
	if( isset($vsql) )
	{
		$sql = $sql . join( ' ; ' , $vsql );
		run_sql( $sql );
		return  sqlite_last_error(db()) == 0;  
	}
}

function uv( $name )
{
	if( !empty($name) ) return intval( $name );
	else return '';
}

function get_current_pid()
{
	$pid = intval(v('pid'));
	if( $pid < 1 )
		if( isset( $_COOKIE['__TS_LAST_PID'] ) ) $pid = intval($_COOKIE['__TS_LAST_PID']);
		
	return $pid;
}

function get_current_pid_link( $close = false )
{
	$pid = get_current_pid();
	if( $close )
		return '<a href="javascript:show_float_box(\'?m=todo&a=set_project&todo_page=1\')">' . get_project_name( $pid ) . '</a>';
	else
		return '<a href="javascript:show_float_box(\'?m=todo&a=set_project\')"><span class="gray">' . get_project_name( $pid ) . '</span></a>';
		
	
}

function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();

    preg_match_all('@(\w+)=(?:([\'"])([^\2]+)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}




?>