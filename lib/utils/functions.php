<?php
function quoteArray(&$item, $key, $user_data="")
{
  $item = "'".$item."'";
}
function KMG(&$umf)
{
  if (strpos($umf,"M")!==FALSE) {
	str_replace("M","", $umf);
	$umf = (int)$umf * 1024 * 1024;
  }
  else if (strpos($umf,"K")!==FALSE) {
	str_replace("K","", $umf);
	$umf = (int)$umf * 1024;
  }
}

function file_size($size)
{
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
}

function startsWith($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function renderExceptionDetails($error)
{
  
  $details= "<a class='exception_link' onClick='javascript:showExceptonDetails(this)'>";
  $details.= tr("Error Details");
  $details.= "</a>";
  $details.= "<div class='exception_details'>";
  $details.= $error;
  $details.= "</div>";
  return $details;
  
}
function constructSiteTitle($path)
{
    $path = array_reverse($path);
    $title = array();
    foreach($path as $key=>$item) {
      if ($item instanceof MenuItem) {
	$title[]=tr(strip_tags($item->getTitle()));
      }
      else {
	$title[]=tr($item);
      }
    }
    return implode(TITLE_PATH_SEPARATOR, $title);
}
function debugArray($str, array $arr)
{
  debug($str);
  foreach($arr as $key=>$val) {
    if ($val instanceof StorageObject) {
      if ($val instanceof ImageStorageObject) {
	$val = get_class($val).":UID:".$val->getUID()." | Dimension: (".$val->getWidth().",".$val->getHeight().")"; 
      }
      else {
	$val = get_class($val).":UID:".$val->getUID();
      }
    }
    if (is_array($val)) $val = "Array(".implode(",",$val).")";
    debug("[$key]=>$val");
  }
}
function debug($str)
{
  if (defined("DEBUG_OUTPUT") && strcmp(DEBUG_OUTPUT, "1")==0) {
    error_log($str);
  }
}

function keywordFilterSQL($keywords_text, $search_fields, $inner_glue=" OR ", $outer_glue=" AND ", $split_string="/[,;]/")
{
  $db = DBDriver::get();
  
  if (strlen($keywords_text)<1) throw new Exception("keywords_text parameter empty");
  if (!is_array($search_fields) && strlen($search_fields)<1) throw new Exception("search_fields parameter empty");
  if (is_array($search_fields) && count($search_fields)<1) throw new Exception("search_fields parameter empty");
  
  $keywords = preg_split($split_string, $keywords_text);
  $kwsearch = array();
  foreach($keywords as $key=>$word) {
      $word = $db->escapeString(trim($word));
      if (!is_array($search_fields)) {
	$search_fields = array($search_fields);
      }
      $sfields = array();
      for ($a=0;$a<count($search_fields);$a++) {
	$field_name = $search_fields[$a];
	$sfields[] = " ( $field_name LIKE '%$word%' ) ";
      }
      $kwsearch[] = " ( ".implode($inner_glue, $sfields)." ) ";
      
//       $kwsearch[] = " ((summary LIKE '%$word%') OR ( item_title LIKE '%$word%' ) OR ( location LIKE '%$word%' ) OR ( venue LIKE '%$word%' ) OR (tags LIKE '%$word%')";
  }
  $kwsearch = implode ( $outer_glue, $kwsearch );
  
  return $kwsearch;
  
}


function queryArray()
{
  return $_GET;
}

//take query array and return string
function queryString($qryarr=0, $append="")
{
	$ret = "";
	$workarr=$_GET;
	if (is_array($qryarr))
	{
		$workarr=$qryarr;
	}
	if (count($workarr)>0){
		$qrypair = array();
		foreach($workarr as $key=>$val){
			if (strlen($val)>0){
				$qrypair[] = $key."=".$val;
			}
			else {
				$qrypair[] = $key;
			}
		}
		$ret = "?".implode("&",$qrypair);
		if (strlen($append)>0) {
		  $append = str_replace("?","",$append);
		  $ret.="&".$append;
		}
	}
	else if (strlen($append)>0) {
	  $append = str_replace("?","",$append);
	  $ret.="?".$append;
	}


	return $ret;

}
function urlString($str)
{
	$pos = strpos("?", $str);
	if ($pos>0) {
		$str = substr(0,$pos);
	}
	return $str;
}
function stringQuery($str)
{
	$pos = strpos("?", $str);
	if ($pos>0) {
		$str = substr($str,$pos+1);
	}
	$pairs = explode("&", $str);
	$ret = array();
	foreach ($pairs as $pos=>$val)
	{
		$vals = explode("=", $val);
		$keyval = "";
		if (isset($vals[1]))$keyval=$vals[1];
		$ret[$vals[0]]=$keyval;
	}
	return $ret;
}

function isEmptyPassword($password)
{
	return (strcmp($password, "d41d8cd98f00b204e9800998ecf8427e") == 0);
}
//TODO: move field2columns to parsing class
function fields2columns(InputForm $form, TableView $view, IDataBean $bean = NULL)
{
  foreach($form->getFields() as $field_name=>$field)
  {
	  if ($bean) {
		if (!$bean->haveField($field_name))continue;
	  }

	  $view->addColumn(new TableColumn($field_name, $field_name));

  }
}
function reorderArray(array $values_array)
{
	$values_ordered = array();

	$idx=-1;
	foreach ($values_array as $key=>$value) {

		  $idx++;
		  $values_ordered[$idx]=$value;
	}

	return $values_ordered;
}

function DefaultAcceptedTags()
{

  return "<br><p><a><ul><ol><li><b><u><i><h1><h2><h3><h4><center><sub><sup><hr><img><object><video><embed><iframe><strong><em><span>";

}
function sanitizeInput($value, $accepted_tags=NULL)
{
  if (is_array($value)) return safeArray($value, $accepted_tags);
  return safeVal($value, $accepted_tags);
}

function safeArray($arr, $accepted_tags=NULL)
{
	if (!$accepted_tags)$accepted_tags = DefaultAcceptedTags();

	$safe_ret = array();
	foreach ($arr as $key=>$val) {
		if (is_array($val)) {
			$safe_ret[$key] = safeArray($val, $accepted_tags);
		}
		else {

			$safe_ret[$key] = safeVal($val, $accepted_tags);

		}

	}

	return $safe_ret;

}

//if get_magic_quotes_gpc();         // 1 then post data is already escaped
function safeVal($val, $accepted_tags=NULL)
{
	if (!$accepted_tags)$accepted_tags = DefaultAcceptedTags();

	$ret = strip_tags(html_entity_decode(stripslashes(trim($val))), $accepted_tags);

	return DBDriver::get()->escapeString($ret);

}
function attributeValue($value)
{
  return htmlentities(mysql_real_unescape_string($value),ENT_QUOTES,"UTF-8");
}
function json_string($text)
{
  if (defined("JSON_UNESCAPED_UNICODE")) {
    return json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
  }
  else {
    return json_encode(utf8_encode($text));
  }
}
function listFiles($dir, $callback)
{
//   if (is_dir($dir)) {
// 	  if ($dh = opendir($dir)) {
// 		  while (($file = readdir($dh)) !== false) {
//
// 			  if (strcmp($file,".")==0)continue;
// 			  if (strcmp($file,"..")==0)continue;
// 			  if (is_dir($file))continue;
// 			  call_user_func($callback, $file);
// 		  }
// 		  closedir($dh);
// 	  }
//   }
	$all_files = scandir($dir);
	foreach($all_files as $pos=>$file) {
		if (strcmp($file,".")==0)continue;
		if (strcmp($file,"..")==0)continue;
		if (is_dir($file))continue;
		call_user_func($callback, $file);
	}
}
function parse_signed_request($signed_request, $secret) {
	list($encoded_sig, $payload) = explode('.', $signed_request, 2);

	// decode the data
	$sig = base64_url_decode($encoded_sig);
	$data = json_decode(base64_url_decode($payload), true);

	if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
// 	  error_log('Unknown algorithm. Expected HMAC-SHA256');
	  throw new Exception('Unknown algorithm. Expected HMAC-SHA256');
// 	  return null;
	}

	// check sig
	$expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
	if ($sig !== $expected_sig) {
  //     error_log('Bad Signed JSON signature!');
	  throw new Exception("Bad Signed JSNO signature!");
  //     return null;
	}

	return $data;
}

function base64_url_decode($input) {
  return base64_decode(strtr($input, '-_', '+/'));
}

function keywordSplit($str, $pattern = '/[; ,]/')
{

  $kwall = preg_split( $pattern, $str );
  $search_words = array();
  foreach($kwall as $key=>$val) {
	if (strlen(trim($val))>0) $search_words[] = $val;
  }
  return $search_words;
}
function array2object($array) {

    if (is_array($array)) {
        $obj = new StdClass();

        foreach ($array as $key => $val){
            $obj->$key = $val;
        }
    }
    else { $obj = $array; }

    return $obj;
}

function object2array($object) {
    if (is_object($object)) {
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
    }
    else {
        $array = $object;
    }
    return $array;
}


function refkeyPageCheck(DBTableBean $ref_bean, $redirect_fail, &$ref_key, &$ref_id, &$ref_row=array())
{
  include_once("lib/beans/DBTableBean.php");
  try {
	$ref_key = $ref_bean->getPrKey();

	if (!isset($_GET[$ref_key]))throw new Exception($ref_key." not passed");
	$ref_id = (int)$_GET[$ref_key];

	$ref_row = $ref_bean->getByID($ref_id);
  }
  catch (Exception $e) {
	Session::set("alert", $ref_key.":$ref_id Not Found");
	header("Location: $redirect_fail");
	exit;
  }

  $arr=$_GET;

  if (isset($arr[$ref_key]))unset($arr[$ref_key]);
  $qrystr = queryString($arr, $ref_key."=".$ref_id);
  return $qrystr;
}


function mysql_real_unescape_string($input,$checkbr=0)
{

// mysql_real_escape_string() calls MySQL's library function mysql_real_escape_string, which prepends backslashes to the following characters: \x00, \n, \r, \, ', " and \x1a.

// $output = $input;
// $output = str_replace("\\\\", "\\", $output);
// $output = str_replace("\'", "'", $output);
// $output = str_replace('\"', '"', $output);

$search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
  $replace=array("\\","\0","\n","\r","\x1a","'",'"');
  return str_replace($search,$replace,$input);

// return $output;

}
function text4div(&$text)
{
  return str_replace("\n", "<BR>", $text);
}
function strcmp_isset($key, $val, $arr=false)
{
  if (!$arr)$arr=$_GET;
  return ( isset($arr[$key]) && (strcmp($arr[$key], $val) == 0) ) ? true : false;
}



function CalculateAge($date)
{
	$unix_date = strtotime($date);
	$year = date("Y");
	$year_birth = date("Y",$unix_date);

	$adjust=0;

	$thatyear  = strtotime(date("Y",$unix_date)."-".date("m")."-".date("d"));
	if ($thatyear<$unix_date){
		$adjust=1;
	}


	return (($year-$year_birth) - $adjust);

}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function isOnline($last_seen_date)
{
  if (strcmp($last_seen_date, "0000-00-00 00:00:00")==0) {
		return false;
  }
  $last_seen = strtotime($last_seen_date);
  $now = strtotime("now");
  $diff = $now - $last_seen;

  $session_lifetime = ini_get("session.gc_maxlifetime");

  if ($diff>=$session_lifetime) {
	  return false;
  }
  else {
	  return true;
  }

}
function NiceTime($date)
{
	if(empty($date)) {
		return "No date provided";
	}
	if (strcmp($date, "0000-00-00 00:00:00")==0) {
		return "Never";
	}
	$periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths         = array("60","60","24","7","4.35","12","10");

	$now             = time();
	$unix_date         = strtotime($date);

	// check validity of date
	if(empty($unix_date)) {
		return "Bad date";
	}

	// is it future date or past date
	if($now > $unix_date) {
		$difference     = $now - $unix_date;
		$tense         = "ago";

	} else {
		$difference     = $unix_date - $now;
		$tense         = "from now";
	}

	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}

	$difference = round($difference);

	if ($periods[$j]=="second" && $difference==0){
		return "Now";
	}

	if($difference != 1) {
		$periods[$j].= "s";
	}

	return "$difference $periods[$j] {$tense}";
}



function StarsForValue($val,$min=1, $max=5)
{
	$on_star = "<img class='star star_on'  src='".SITE_ROOT."images/icon_star_on.png'>";
	$half_star = "<img class='star star_half' src='".SITE_ROOT."images/icon_star_half.png'>";
	$off_star = "<img class='star star_off' src='".SITE_ROOT."images/icon_star_off.png'>";

// 	$val = ($val/10);

	for ($a=$min;$a<=$max;$a++){
	  if ($val>=$a){
		  echo $on_star;
	  }
	  else if ($val<$a && $val > ($a-1)){
		  echo $half_star;
	  }
	  else {
		  echo $off_star;
	  }
	}
}



function Cloud($artists, $sort_field, $top=50)
{
	echo "<center>";
	$artists->startIterator(" WHERE 1 ORDER BY view_counter DESC limit $top");
	$list=array();
	while ($artists->fetchNext($cntrow)){

		$listid[]=$cntrow[$artists->getPrKey()];
		$listcount[]=$cntrow["view_counter"];
	}

	$list_filter = implode(",",$listid);

	$list_max = $listcount[0] + 1;
	$list_min = $listcount[count($listcount)-1] + 1;

	$artists->startIterator(" WHERE ".$artists->getPrKey()." in ($list_filter) order by $sort_field ASC limit $top");
	while ($artists->fetchNext($row)){
		$artistID=$row[$artists->getPrKey()];
		$cnt = $row["view_counter"] + 1;

		$fs =  ( $cnt / $list_max );
		$fs = $fs * 14;
		$fs = 12 + $fs;

		echo "<a class=tag_cloud style='font-size:{$fs}px' href='profile.php?id=$artistID'>".$row[$sort_field]."</a>";
	}
	echo "</center>";
}
function timestamp2mysqldate($timestamp)
{
  return date("Y-m-d H:i:s", $timestamp);

}
function dateFormat($date, $time=true)
{
		$tm = "H:i";
		if (!$time)$tm="";
		return date("M j, Y $tm",strtotime($date));
}

function dateFormatFromUnix($unixtime, $time=true)
{
	$tm = "H:i";
	if (!$time)$tm="";
	return date("M j, Y $tm",$unixtime);
}

function date2time($date, $format='%a, %d %b %Y %H:%M:%S %z')
{


				$arr = strptime($date, $format);

				$rhour = (int)$arr["tm_hour"];
				$rminute = (int)$arr["tm_min"];
				$rsecond = (int)$arr["tm_sec"];
				$rmonth = (1+(int)$arr["tm_mon"]);
				$rday = (int)$arr["tm_mday"];
				$ryear = (1900+(int)$arr["tm_year"]);
				return mktime($rhour, $rminute, $rsecond, $rmonth, $rday, $ryear );
}

function outputCSV($sql_query,$filename = 'export.csv') {


header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

    $outstream = fopen("php://output", "w");
    function __outputCSV(&$vals, $key, $filehandler) {


        fputcsv($filehandler, $vals); // add parameters if you want
    }

	$db = DBDriver::get();
	// Gets the data from the database
	$result = $db->query($sql_query);
	$fields_cnt = $db->numFields($result);

	$fields = array();
	for ($i = 0; $i < $fields_cnt; $i++)
	{
		$fields[] = $db->fieldName($result, $i);
	} // end for

	$data = array($fields);
	array_walk($data, "__outputCSV", $outstream);

	while ($row = $db->fetchRow($result)) {
		$data = array($row);
		array_walk($data, "__outputCSV", $outstream);
	}

    fclose($outstream);

}

function exportMysqlToCsv($sql_query,$filename = 'export.csv')
{
	$csv_terminated = "\n";
	$csv_separator = ",";
	$csv_enclosed = '"';
	$csv_escaped = "\\";
//     $sql_query = "select * from $table";

	$db = DBDriver::get();
	// Gets the data from the database
	$result = $db->query($sql_query);
	$fields_cnt = $db->numFields($result);


	$schema_insert = '';

	for ($i = 0; $i < $fields_cnt; $i++)
	{
	$l = $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed,
		stripslashes($db->fieldName($result, $i))) . $csv_enclosed;
	$schema_insert .= $l;
	$schema_insert .= $csv_separator;
	} // end for

	$out = trim(substr($schema_insert, 0, -1));
	$out .= $csv_terminated;

	// Format the data
	while ($row = $db->fetchRow($result))
	{
	  $schema_insert = '';
	  for ($j = 0; $j < $fields_cnt; $j++)
	  {
		  if ($row[$j] == '0' || $row[$j] != '')
		  {

			  if ($csv_enclosed == '')
			  {
				  $schema_insert .= $row[$j];
			  }
			  else
			  {
				  $schema_insert .= $csv_enclosed .
						  str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $row[$j]) . $csv_enclosed;
			  }
		  }
		  else
		  {
			  $schema_insert .= '';
		  }

		  if ($j < $fields_cnt - 1)
		  {
			  $schema_insert .= $csv_separator;
		  }
	  } // end for

	  $out .= $schema_insert;
	  $out .= $csv_terminated;
	} // end while

	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Length: " . strlen($out));
	// Output to browser with appropriate mime type, you choose ;)
	header("Content-type: text/x-csv");
	//header("Content-type: text/csv");
	//header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=$filename");
	echo $out;
	exit;

}





function dumpVal($val)
{
	$order   = array("\r\n", "\n", "\r");
	$replace = '<br />';
	// Processes \r\n's first so they aren't converted twice.
	$newstr = str_replace($order, $replace, $val);
	return $newstr;
}

function filterText(&$content, $length)
{

	if (strlen($content)<=$length)return $content;
// return $content;

	$finished=0;
	$chars_so_far=0;
	while ($finished==0){
		$pos = strpos($content," ", ($chars_so_far+1));
		if ($pos>-1) {
			$chars_so_far=$pos;
		}
		else {
			$chars_so_far=$length;
			break;
		}
		if ($chars_so_far >= $length){
			$finished=1;
		}
	}
	$cnt_small = substr($content, 0, ($chars_so_far))." ...";
	return $cnt_small;
}
function unset_multi(&$arr, $fields)
{
  foreach($fields as $key=>$val) {

	if (isset($arr[$val]))unset($arr[$val]);
  }
}
?>