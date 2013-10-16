<?php
$op="";

if(isset($_GET['op']))
	$op=$_GET['op'];

if($op == "getnav")
{
	echo file_get_contents("core/nav.inc");
	exit;
}
?>
