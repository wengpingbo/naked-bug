<?php

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
	if(count($posts) == 0) return "No post found in $path";
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
?>
<!DOCTYPE html>
<html>
  <head>
	<title>Pingbo Wen's Blog</title>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="css/main.css" rel="stylesheet">
  </head>
  <body>
	<!-- nav bar -->
<?php require 'core/nav.inc'?>
	<!-- main area -->
<div class="container">
	<div class="row-fluid">
	  <div class="span9">
<div id="post_toc">
	<?php echo build_post_list("article", 0, 20); ?>
</div>
<div id="page">
<ul class="pager">
  <li><a href="#">Previous</a></li>
  <li><a href="#">Next</a></li>
</ul>
</div>
	  </div>
	  <div class="span3">
		<div class="row-fluid">
Top Article
		</div>
		<div class="row-fluid">
Recent Comment
		</div>
	  </div>
	</div>
</div>
	<!-- footer -->
<?php require 'core/footer.inc'?>
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>

