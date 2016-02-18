<?php
/* 
* DLE + SearchFields v5.1
* All Rights Reserved!
* author Dave Aka Aios
* mod author Nravchik
* year 2010-2014
*/
if (!defined('DATALIFEENGINE')) { die("Hacking attempt!"); }

$disables = array( 'do', 'cstart', 'page', 'loc', 'news_direction_by', 'news_sort_by' );
$request = $_SERVER['REQUEST_URI'];
$query_string = $_SERVER['QUERY_STRING'];

if ($config['allow_alt_url']) {
	if ($_GET['loc']) {
		$location = '';
		foreach ($_GET as $key => $value) {
			if ( $key != 'do' && $key != 'loc' && !is_array($value)) {
				$location .= $key . ':' . $value . '/';
			} elseif (is_array($value)) {
				$location .= $key . ':' . implode(";", $value) . '/';
			}
		}
		header ("Location: $config[http_home_url]sf/$location");
	}

	$tmp_array = explode('/', $_GET['q']);
	$in_array = '';
	
	unset($_GET);
	$_GET['do'] = 'search_xf';
	$request = substr($request, 1);
		
	foreach($tmp_array as $value) {
		if ($value != '') {
			$in_array = explode(":", $value);
			$_GET[$in_array[0]] = $in_array[1];
			if($in_array[0] == 'page')
				$cstart = $in_array[1];
		}
	}
}

$url_page = $config['http_home_url'] . $request;

if($_GET['page'] > 1) {
	$url_page = preg_replace( "/(page:[0-9]+\\/|cstart:[0-9]+)/", "", $url_page );
} else {
	$url_page = preg_replace( "/(\\/page:[0-9]+\\/|\\/cstart:[0-9]+)/", "", $url_page );
}

if(strpos($query_string, 'cstart') !== false) {
	$query_string = preg_replace( '/cstart=[0-9]+/', "", $query_string );
	$query_string = substr($query_string, 1);
}

$user_query = str_replace("&","&amp;",$query_string);

if ($cstart) {
	$cstart = $cstart - 1;
	$cstart = $cstart * $config['news_number'];
}

$newsmodule = true;

if (isset($_GET['news_sort_by'])) $news_sort_by = $_GET['news_sort_by'];
else $news_sort_by = 'date';
	
if (isset($_GET['news_direction_by'])) $news_direction_by = $_GET['news_direction_by'];
else $news_direction_by = 'DESC';

if ($news_sort_by != 'date') $select = ", SUBSTRING_INDEX( SUBSTRING_INDEX( xfields,  '{$news_sort_by}|', -1 ) ,  '||', 1 ) as $news_sort_by";

$searchQuery = array();
$searchEngine = $_GET;

foreach ($searchEngine as $key => $value) {
	if (!in_array($key, $disables) and $value !="" and $value != NULL) {
		preg_match_all('#int_(.*)_(.*)#', $key, $matches, PREG_SET_ORDER);
		if($matches) {
			if($matches[0][1]=='start') {
				$searchQuery[] = "SUBSTRING_INDEX( SUBSTRING_INDEX( xfields,  '{$matches[0][2]}|', -1 ) ,  '||', 1 )>=$value";	
			} else {
				$searchQuery[] = "SUBSTRING_INDEX( SUBSTRING_INDEX( xfields,  '{$matches[0][2]}|', -1 ) ,  '||', 1 )<=$value";
			}
		} else {
			if ($key != 'category') {
				if(strpos($value, ";") === false) {
					$searchArr = array($key, $value);
					$searchQuery[] = "xfields LIKE '%" . implode('|', $searchArr) . "%'";
				} else {
					$searchRes = explode(";", $value); 
					foreach($searchRes as $kent) {
						$searchArr = array($key, $kent); 
						$searchLS[] = "xfields LIKE '%" . implode('|', $searchArr) . "%'";
					}
					$searchRes = implode(" OR ", $searchLS);
					$searchQuery[] = '(' .$searchRes. ')';
				}
			} else {
				if ($config['allow_multi_category']) {
					$searchQuery[] = "category regexp '[[:<:]]( $value  )[[:>:]]'";
				} else {
					$searchQuery[] = "category = '$value'";
				}
			}
		}
	}
}

unset($matches);
if($searchQuery) $where .= implode(' AND ',$searchQuery)." AND";

$sql_select = "SELECT id, autor, date, short_story, full_story, xfields, title, category, alt_name, comm_num, allow_comm, allow_rate, rating, vote_num, news_read, approve, editdate, editor, reason, view_edit, tags $select FROM " . PREFIX . "_post LEFT JOIN " . PREFIX . "_post_extras ON  " . PREFIX . "_post_extras.news_id =  " . PREFIX . "_post.id  WHERE {$where} approve" . $where_date . " ORDER BY " . $news_sort_by . "+0 " . $news_direction_by . " LIMIT " . $cstart . "," . $config['news_number'];$sql_count = "SELECT COUNT(*) as count FROM " . PREFIX . "_post where {$where} approve" . $where_date;

$allow_active_news = true;
?>
