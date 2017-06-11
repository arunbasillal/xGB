<?php

/*--------------------------------------------------------------------------*/
/*					Code To Check The Post Count At The Time Of				*/
/*					Publishing The Article. Now Depreciated 				*/
/*					Was Used In: plugins/xgb/xgb.php						*/
/*--------------------------------------------------------------------------*/

include('../../../wp-blog-header.php');
include('wpframe.php');
header("HTTP/1.1 200 OK"); // Not sure why - but this url gives a 404 when called

$author_id = $_REQUEST['author_id'];
if($author_id) {
	$published_post_count = $wpdb->get_var("SELECT COUNT(ID) FROM wp_posts WHERE post_status='publish' AND post_type='post' AND post_author=$author_id");
	print $published_post_count;
	exit;
}
print "0";