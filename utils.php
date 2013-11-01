<?php
/**   function sets **/

/**
 * return a array contain post list
 */
function get_post_list($path)
{
	if(!realpath($path)) return;
	//else $path=realpath($path);
	$posts=scandir($path, 0);
	$ret=array();
	foreach($posts as $value)
	{
		if($value != "." && $value != ".." && is_dir("$path/$value"))
		{
			$post_file=glob("$path/$value/*.html");
			if(count($post_file) >=1 ) array_push($ret, $post_file[0]);
		}
	}
	return $ret;
}

/**
 * return a string contain post list table
 */
function build_post_list($path, $start, $num)
{
	$posts=get_post_list($path);
	if(count($posts) == 0) return;
	else if($start > count($posts)) return;

	$end = $start + $num;
	if($end > count($posts)) $end=count($posts);

	$retstr='<table class="table table-hover table condensed"><caption>Posts Lists</caption><thead><tr><td id="post_no">No.</td><td>Title</td></tr></thead><tbody>';
	for($i=$start; $i<$end; $i++ )
	{
		$tem ='<tr><td id="post_no">' . $i;
		$tem .= '</td><td><div id="post_title"><a href="' . $posts[$i];
		$tem .= '">' . basename($posts[$i], ".html");
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
$len = 20;

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
  if($len <= 0) $len = 20;
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

