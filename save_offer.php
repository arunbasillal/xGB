<?php
/*--------------------------------------------------------------*/
/*			Pushes The Offer Request Into The Database			*/
/*						Used In: single.php	  					*/
/*--------------------------------------------------------------*/

include('../../../wp-blog-header.php');
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
header("HTTP/1.1 200 OK"); // Not sure why - but this url gives a 404 when called - verify this later

if(!empty($_POST['action'])) {
	
		// Retrieve The URL
		$url = $_POST[site];

		// Remove all illegal characters from url
		$url = filter_var($url, FILTER_SANITIZE_URL);

		// Validate url. Note that the return value is either false or the url, it will not return === true
		if (filter_var($url, FILTER_VALIDATE_URL) === false) {
			header("Location: http://www.millionclues.net/?p=$_POST[post_id]&status=invalid_url");
			exit;
		}
		else {
			global $current_user;
			get_currentuserinfo();
			
			// Sanitize The Note
			$sanitized_note = sanitize_text_field($_POST['note']);
			
			// Verify Nonce
			if ( !isset( $_POST['new_request_nonce'] ) || ! wp_verify_nonce( $_POST['new_request_nonce'], 'new_offer_request_' ) ) {
				header("Location: http://www.millionclues.net/?p=$_POST[post_id]&status=invalid_nonce");
			}
			else {
				$wpdb->insert(
					'wp_xgb_offer',
					array(
							'user_id' => $current_user->ID,
							'post_id' => $_POST['post_id'],
							'post_author_id' => $_POST['author_id'],
							'original_post_author_id' => $_POST['author_id'],
							'site' => $url,
							'note' => 'Notes: '.$sanitized_note,
							'offer_made_on' => current_time(mysql),
						));
					
				// Email The Article Author
				$post_author_data = get_userdata($_POST['author_id']);
				
				$subject = $current_user->display_name." wants to publish your article.";
				
				$message = $post_author_data->first_name.", \n\n".$current_user->display_name." is requesting to publish your article: ".get_permalink($_POST['post_id']);
				$message.= "\n\nYou can read their notes and approve or reject the request here: http://www.millionclues.net/wp-admin/admin.php?page=manage-requests";
				
				// Defined in xgb.php
				xgb_notify_user( $post_author_data->user_email, $subject, $message, null );
				update_user_meta( $_POST['author_id'], 'xgb_has_notification', 1 );
				
				header("Location: http://www.millionclues.net/?p=$_POST[post_id]&status=request_sent");
				exit;
			}
		}
}
else {
	header("Location: http://www.millionclues.net/"); 
}
