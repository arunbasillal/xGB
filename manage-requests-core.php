<?php 
/*------------------------------------------------------------------*/
/*				The Core Functionality of Manage Requests			*/
/*					  And Thereby The Core of xGB					*/
/* 				    Originially Names As: offers.php				*/
/*------------------------------------------------------------------*/

// Binny's Plugin Framework
include('wpframe.php');

wpframe_stop_direct_call(__FILE__);
global $wpdb;

// Retrieve The User
global $current_user;
get_currentuserinfo();

$action = 'idle';


/*--------------------------------------------------*/
/*			_GET The 'action' From Page URL			*/
/*--------------------------------------------------*/

if( isset ($_GET['action']) ) {
		$action = $_GET['action'];
	}



/*----------------------------------*/
/*			Accept An Offer			*/
/*----------------------------------*/
if( $action == 'accept' ) {

	$offer_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}xgb_offer WHERE ID='$_GET[offer]'");
	
	// Security Check
	if (($offer_info->original_post_author_id == $current_user->ID) && ($offer_info->status != 'published')) {
	
		// Nonce Security Check
		$nonce = $_GET['accept_request_'];
		
		if ( !wp_verify_nonce( $nonce, 'accept_this_offer' ) ) {
			xgb_request_nonce_error();
		}
		else {
	
			// Change img urls to relative urls
			// http://wordpress.stackexchange.com/a/67255/90061
			// :TODO: For pro users, Remember the Media settings for uploading files and use it while changing image links. Show a meta box in Post Editor
			$content_of_post = get_post($offer_info->post_id);
			$content = $content_of_post->post_content;
			$content = apply_filters('the_content', $content);
			$content = str_replace('http://www.millionclues.net', '', $content);
			
			// Update the post into the database with the relative urls
			$my_post = array(
			  'ID'           => $offer_info->post_id,
			  'post_content' => $content,
			);
			wp_update_post( $my_post );
			
			// Accept Offer Actions
			$wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status='private' WHERE ID={$offer_info->post_id}");									// Make The Post Private
			$wpdb->query("UPDATE {$wpdb->prefix}posts SET post_author={$offer_info->user_id} WHERE ID={$offer_info->post_id}");						// Change Post Author To The User Who Requested To Publish It in wp_posts
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET post_author_id={$offer_info->user_id} WHERE ID='$_GET[offer]'");						// Change Post Author To The User Who Requested To Publish It in wp_xgb_offer
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET status='accepted' WHERE ID='$_GET[offer]'");											// Change Offer Status To 'accepted'
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET status='rejected' WHERE post_id={$offer_info->post_id} AND ID!='$_GET[offer]'");		// Change Offer Status Of All Other Users Who Requested To 'rejected'
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET note='Rejection Reason: Article sent to a different user.' WHERE post_id={$offer_info->post_id} AND ID!='$_GET[offer]'");	// The Rejection Notice For All Other Users
			
			// if (current user has pending articles)
			if(($wpdb->get_var("SELECT COUNT(ID) FROM wp_posts WHERE post_author={$current_user->ID} AND post_status='pending'") > 0)) {
				if(current_user_can('basic_user')) {
					// :TODO: Nudge to upgrade
					// if current user does not have 5 live articles
					if ($wpdb->get_var("SELECT COUNT(ID) FROM wp_posts WHERE post_author={$current_user->ID} AND post_status='publish'") < $GLOBALS['live_article_limit']) {
						$offer_accepted_while_user_on_limit = ' <br>You can publish one of your <a href="http://www.millionclues.net/wp-admin/edit.php?post_status=pending&post_type=post">pending articles</a> now.';
					}
				}
				else if (current_user_can('have_pro_user_features')) {
				// if current user does not have 20 live articles
					if ($wpdb->get_var("SELECT COUNT(ID) FROM wp_posts WHERE post_author={$current_user->ID} AND post_status='publish'") < $GLOBALS['live_article_limit']) {
						$offer_accepted_while_user_on_limit = ' <br>Your oldest pending article has been automatically published.';
					}
				}
			}
			
			// :TODO: [PRO Feature]: Publish oldest pending post of current author automatically.
			// something like $wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status='publish' WHERE post_author={$current_user->ID} ORDER BY post_date LIMIT 1");
			
			// Email The User Who Requested
			$article_request_user_data = get_userdata($offer_info->user_id);
				
			$subject = $current_user->display_name." just accepted your request.";

			$message = $article_request_user_data->first_name.",\n\n".$current_user->display_name." just accepted your request to publish their article titled: '".$content_of_post->post_title."'";
			$message.= "\n\nHere is the full article: http://www.millionclues.net/wp-admin/post.php?post=".$offer_info->post_id."&action=edit";
			$message.= "\n\nRemember to: \n\n- Upload the images on your server. \n- Submit the live link back to the author once you have published the article.";
			
			// Email Function Defined in xgb.php
			xgb_notify_user( $article_request_user_data->user_email, $subject, $message, null );
			update_user_meta( $offer_info->user_id, 'xgb_has_notification', 1 );
			
			print '<div id="message" class="updated fade"><p>'. t("Request accepted."). $offer_accepted_while_user_on_limit . '</p></div>';
			
		}
	} // End of Security Check Pass
	else {
		print '<div id="message" class="notice error"><p>'. t('You are not authorized to accept that request <br>Hint: Log out and login and try again or if this persists, <a class="dottedunderline" href="http://www.millionclues.net/support/">contact support</a>') . '</p></div>'; 
	}
}



/*----------------------------------*/
/*			Reject An Offer			*/
/*----------------------------------*/
else if( $action == 'reject' ) {

	$offer_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}xgb_offer WHERE ID='$_GET[offer]'");
	
	// Security Check
	if (($offer_info->original_post_author_id == $current_user->ID) && ($offer_info->status != 'published')) {
		
		// Nonce Security Check
		$nonce = $_GET['reject_'];
		
		if ( !wp_verify_nonce( $nonce, 'reject_url_nonce' ) ) {
			xgb_request_nonce_error();
		}
		else {
			$sanitized_reason = sanitize_text_field($_GET[reason]);
			$sanitized_reason = 'Rejection Reason: '.$sanitized_reason;
			
			// Reject An Offer Actions
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET status='rejected' WHERE ID='$_GET[offer]'");					// Set Status As 'rejected'
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET note='{$sanitized_reason}' WHERE ID='$_GET[offer]'");			// Set Reason As Obtained From The User Via The JS Prompt Box getrejectreason()
			
			update_user_meta( $offer_info->user_id, 'xgb_has_notification', 1 );
			
			print '<div id="message" class="updated fade"><p>'. t("Request rejected") . '</p></div>';
		}
	}

	else { print '<div id="message" class="notice error"><p>'. t('You are not authorized to reject that request <br>Hint: Log out and login and try again or if this persists, <a class="dottedunderline" href="http://www.millionclues.net/support/">contact support</a>') . '</p></div>'; }
	
}



/*--------------------------------------------------------------*/
/*			Reject An Offer After Accepting It First			*/
/*--------------------------------------------------------------*/
else if( $action == 'changemymind' ) {

	$offer_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}xgb_offer WHERE ID='$_GET[offer]'");
	
	// Security Check
	if (($offer_info->original_post_author_id == $current_user->ID) && ($offer_info->status != 'published')) {
		
		// Nonce Security Check
		$nonce = $_GET['undo_'];
		
		if ( !wp_verify_nonce( $nonce, 'undo_url_nonce' ) ) {
			xgb_request_nonce_error();
		}
		else {
	
			$sanitized_reason = sanitize_text_field($_GET[reason]);
			$sanitized_reason = 'Rejection Reason: '.$sanitized_reason;
			
			// Reject An Offer After Accepting It First Actions
			$wpdb->query("UPDATE {$wpdb->prefix}posts SET post_author={$offer_info->original_post_author_id} WHERE ID={$offer_info->post_id}");			// Revert post_author To The Originial Author wp_posts
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET post_author_id={$offer_info->original_post_author_id} WHERE ID='$_GET[offer]'");			// Revert To The Original Author In wp_xgb_offer
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET status='rejected' WHERE ID='$_GET[offer]'");												// Set status as 'rejected'
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET note='{$sanitized_reason}' WHERE ID='$_GET[offer]'");										// Save reason From JS Prompt getchangemymindreason() As Note
			$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET status='posted', note='' WHERE post_id={$offer_info->post_id} AND ID!='$_GET[offer]'");	// Clear The Rejected Status Of All Other Requests For The Same Offer And Set Their Status As 'posted'
			
			// If the person already have 5 published post (or 20 for pro user), make one of them pending
			// :TODO: Nag user to upgrade if its a basic user.
			if($wpdb->get_var("SELECT COUNT(ID) FROM wp_posts WHERE post_author={$current_user->ID} AND post_status='publish'") >= $GLOBALS['live_article_limit']) {
				$wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status='pending' WHERE ID={$offer_info->post_id}");			// Save The Post As Pending.
				$person_exeeded_published_posts_limit = ' <br>Since you already have '. $GLOBALS['live_article_limit'] .' live articles, it is now saved as <a href="http://www.millionclues.net/wp-admin/edit.php?post_status=pending&post_type=post">pending</a>.';
			} 
			else {
				$wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status='publish' WHERE ID={$offer_info->post_id}");			// Save The Post As Published
				$person_exeeded_published_posts_limit = ' <br>The article is live again.';
			}

			update_user_meta( $offer_info->user_id, 'xgb_has_notification', 1 );
			
			print '<div id="message" class="updated fade"><p>'. t("Request rejected") . $person_exeeded_published_posts_limit. '</p></div>';
		}
	}
	
	else { print '<div id="message" class="notice error"><p>'. t('You are not authorized to reject that request <br>Hint: Log out and login and try again or if this persists, <a class="dottedunderline" href="http://www.millionclues.net/support/">contact support</a>') . '</p></div>'; }
	
}



/*------------------------------------------------------------------*/
/*			Article Is Published And The URL Is Submitted			*/
/*------------------------------------------------------------------*/
else if( $action == 'published' ) {

	$offer_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}xgb_offer WHERE ID='$_GET[offer]'");

	// Security Check
	if (($offer_info->post_author_id == $current_user->ID) && ($offer_info->status != 'published')) {
	
		// Nonce Security Check
		$nonce = $_GET['submit_'];
		
		if ( !wp_verify_nonce( $nonce, 'submit_url_nonce' ) ) {
			xgb_request_nonce_error();
		}
		else {
		
			$url = $_GET[url];

			// Remove all illegal characters from url
			$url = filter_var($url, FILTER_SANITIZE_URL);

			// Validate url. Note that the return value is either false or the url, it will not return === true
			if (filter_var($url, FILTER_VALIDATE_URL) === false) {
				print '<div id="message" class="notice error"><p>'. t("The link you entered is invalid. Please try again.") . '</p></div>';
			} else {
				
				// Article Is Published And The URL Is Submitted Actions
				$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET status='published' WHERE ID='$_GET[offer]'");						// status Set As 'published'
				$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET live_url='{$url}' WHERE ID='$_GET[offer]'");						// Save The Received url
				$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET offer_published_on=NOW() WHERE ID='$_GET[offer]'");				// Save The Time When The Article Is Made Live
				$wpdb->query("DELETE FROM {$wpdb->prefix}xgb_offer WHERE post_id={$offer_info->post_id} AND status='rejected'");	// Delete All The Other Offers For The Same Article
				$wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status='closed' WHERE ID={$offer_info->post_id}");				// Change The Status Of the Post/Article To closed
				
				// Email The Article Author
				$article_author_user_data = get_userdata($offer_info->original_post_author_id);
				
				// Get The Post Title And Remove The Word "Private: "
				$title_of_post = get_the_title($offer_info->post_id);
				$title_of_post = str_replace('Private: ', '', $title_of_post);
				
				$subject = $current_user->display_name." published your article.";

				$message = $article_author_user_data->first_name.",\n\n".$current_user->display_name." have published your article titled: '".$title_of_post."'";
				$message.= "\n\nHere is the live url: ".$url;
				$message.= "\n\nYou can see all your published articles here: http://www.millionclues.net/wp-admin/admin.php?page=published-by-others";
				
				// Email Function Defined in xgb.php
				xgb_notify_user( $article_author_user_data->user_email, $subject, $message, null );
				update_user_meta( $offer_info->original_post_author_id, 'xgb_has_notification', 1 );
				
				print '<div id="message" class="updated fade"><p>'. t("Thank you for publishing the article.") . '</p></div>';
				
			}
		}
	}
	
	else { print '<div id="message" class="notice error"><p>'. t('You are not authorized to do that <br>Hint: Log out and login and try again or if this persists, <a class="dottedunderline" href="http://www.millionclues.net/support/">contact support</a>') . '</p></div>'; }
}



/*--------------------------------------*/
/*			Offer Is Deleted			*/
/*--------------------------------------*/
// :TODO: Delete an offer - No deletion for now
/*else if( $action == 'delete' ) {
	$offer_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}xgb_offer WHERE ID='$_GET[offer]'");
	
	// Security Check
	if ((($offer_info->original_post_author_id == $current_user->ID) || ($offer_info->user_id == $current_user->ID)) && ($offer_info->status != 'published') ) {
	$wpdb->query("DELETE FROM {$wpdb->prefix}xgb_offer WHERE ID='$_GET[offer]'");
	print '<div id="message" class="updated fade"><p>'. t("Offer Deleted") . '</p></div>';
	}
	
	else { print '<div id="message" class="notice error"><p>'. t("You are not authorized to delete that.") . '</p></div>'; }
	
}*/

?>