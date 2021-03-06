<?php
/**   function sets **/

/**
 * return a array contain catalog
 */
function get_catalog($path)
{
  if(!realpath($path)) return;
  $catalog=scandir($path, 0);
  $ret=array();
  foreach($catalog as $value)
	if($value != "." && $value != ".." && is_dir("$path/$value"))
	  array_push($ret, $value);
  return $ret;
}

/**
 * return catalog dropmenu
 */
function build_catalog($path)
{
  $catalog = get_catalog($path);
  //too few catalog to create dropmenu, return null
  if(count($catalog) < 2 ) return;
  $ret = '<li class="dropdown">';
  $ret .= '<a class="dropdown-toggle" data-toggle="dropdown" href="javascript:;"><i class="icon-folder-open icon-white"></i> Catalog<b class="caret"></b></a>';
  $ret .= '<ul class="dropdown-menu pull-right">';
  foreach($catalog as $value)
	$ret .= '<li class="dropdown_item"><a tabindex="-1" href="javascript:;">' . $value . '</a></li>';
  $ret .= '</ul></li>';
  return $ret;
}

/**
 * return a string of a list item title from post
 * NOTICE: the list title must in FIRST LINE
 */
function get_post_list_title($post)
{
  $fp = fopen($post, "r");
  $line = fgets($fp);
  fclose($fp);
  $line = mb_substr($line, 4);
  $len = mb_strlen($line);
  $line = mb_substr($line, 0, $len - 4);
  return $line;
}

/**
 * return a array contain post list
 * array structure: 
 * {"post path" => {"mtime", "list item title"}}
 */
function get_post_list($path)
{
	if(!realpath($path)) return;
	$catalog=scandir($path, 0);
	$ret=array();
	foreach($catalog as $value)
	{
		if($value != "." && $value != ".." && is_dir("$path/$value"))
		{
		  foreach(scandir("$path/$value", 0) as $post)
		  {
			if($post != "." && $post != ".." && is_dir("$path/$value/$post"))
			{
			  $article=glob("$path/$value/$post/*.html");
			  if(count($article) >=1 )
			  {
				$ret[$article[0]][0] = filemtime($article[0]);
				$ret[$article[0]][1] = get_post_list_title($article[0]);
			  }
			}
		  }
		}
	}
	arsort($ret);
	return $ret;
}

/**
 * return a string contain post list table of a catalog, if catalog is null, 
 * then return a certain length of all article
 */
function build_post_list($path, $catalog, $start, $num)
{
  $posts=get_post_list($path);
  $postpath=array_keys($posts);
  $postnum=count($postpath);
	if($postnum == 0) return;
	else if($start > $postnum) return;

	$retstr='<table class="table table-hover table condensed"><caption>';
	if($catalog != "") $retstr .= "$catalog";
	else $retstr .= 'ALL POSTS';
	$retstr .= '</caption><thead><tr><td id="post_time">MTIME</td><td>TITLE</td></tr></thead><tbody>';
	$i = $start;$cout=0;
	while($i < $postnum && $cout < $num)
	{
	  $pos = strpos($postpath[$i], "$path/$catalog");
	  if($pos !== false && $pos == 0)
	  {
		$tem ='<tr><td id="post_time">' . date("Y-m-d", $posts[$postpath[$i]][0]);
		$tem .= '</td><td><div id="post_title"><a href="' . $postpath[$i];
		$tem .= '">' . $posts[$postpath[$i]][1];
		$tem .= '</a><div class="stat"><span class="badge badge-info"><i class="icon-eye-open"></i>108</span>&nbsp;&nbsp;<span class="badge"><i class="icon-comment"></i>23</span></div></div></td></tr>';
		$retstr .= $tem;
		$cout++;
	  }
	  $i++;
	}
	$retstr .='</tbody></table>';
	return $retstr;
}

function getnavhtml()
{
  return file_get_contents("core/nav.inc");
}

function getfooterhtml()
{
  return file_get_contents("core/footer.inc");
}

function getcommenthtml()
{
  return file_get_contents("core/comment.inc");
}

function getannouncementhtml()
{
  return file_get_contents("core/announcement.inc");
}

function gettopcommenthtml()
{
  return file_get_contents("core/topcomment.inc");
}

/** Configure Value **/
$postpath = "article";

/** GET Check **/
/**
 * $op action specify
 * [gnav]
 * get nav content
 *
 * [alist]
 * get article table
 * @start, number, start position, default 0
 * @len, number, return table length, default 20
 * @catalog, string, article catalog, default null
 *
 * [footer]
 * get page footer content
 *
 * [gcatalog]
 * get article catalog
 *
 * [comment]
 * get social comment plugin
 *
 * [notice]
 * get blog announcement
 *
 * [tcomment]
 * get top commment plugin
 */
// default value
$op = "";
$start = 0;
$len = 14;
$catalog = "";

//check get value
//$op
if(isset($_GET['op']))
	$op=$_GET['op'];
//$start
if(isset($_GET['start']) && is_numeric($_GET['start']))
{
  $start = $_GET['start'];
  if($start < 0) $start = 0;
}
//$len
if(isset($_GET['len']) && is_numeric($_GET['len']))
{
  $len = $_GET['len'];
  if($len <= 0) $len = 14;
}
//$catalog
if(isset($_GET['catalog']))
{
  $catalog = $_GET['catalog'];
}

switch ($op)
{
case "gnav":
  echo getnavhtml();
  break;
case "alist":
  echo build_post_list($postpath, $catalog, $start, $len);
  break;
case "footer":
  echo getfooterhtml();
  break;
case "gcatalog":
  echo build_catalog($postpath);
  break;
case "comment":
  echo getcommenthtml();
  break;
case "notice":
  echo getannouncementhtml();
  break;
case "tcomment":
  echo gettopcommenthtml();
default:
  echo "";
}
?>

