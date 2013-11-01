<?php
/**   function sets **/

/**
 * return a array contain post list
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
				$ret[$article] = filemtime($article);
			}
		  }
		}
	}
	arsort($ret);
	return $ret;
}

/**
 * return a string contain post list table
 */
function build_post_list($path, $start, $num)
{
  $posts=get_post_list($path);
  $postpath=array_keys($posts);
  $postnum=count($postpath);
	if($postnum == 0) return;
	else if($start > $postnum) return;

	$end = $start + $num;
	if($end > $postnum) $end=$postnum;

	$retstr='<table class="table table-hover table condensed"><caption>Posts Lists</caption><thead><tr><td id="post_time">MTIME</td><td>TITLE</td></tr></thead><tbody>';
	for($i=$start; $i<$end; $i++ )
	{
		$tem ='<tr><td id="post_time">' . date("Y-m-d", $posts[$postpath[$i]]);
		$tem .= '</td><td><div id="post_title"><a href="' . $postpath[$i];
		$tem .= '">' . basename($postpath[$i], ".html");
		$tem .= '</a><div id="stat"><span class="badge badge-info"><i class="icon-eye-open"></i>108</span>&nbsp;&nbsp;<span class="badge"><i class="icon-comment"></i>23</span></div></div></td></tr>';
		$retstr .= $tem;
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
 *
 * [footer]
 * get page footer content
 */
// default value
$op = "";
$start = 0;
$len = 14;

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

switch ($op)
{
case "gnav":
  echo getnavhtml();
  break;
case "alist":
  echo build_post_list($postpath, $start, $len);
  break;
case "footer":
  echo getfooterhtml();
  break;
default:
  echo "";
}
?>

