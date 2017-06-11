<?php
/*------------------------------------------------------------------*/
/*				Private Messages Admin Page							*/
/* 		To Display Messages In A Better Format						*/
/* 		Messages Are WordPress Comments	Are Not Public				*/
/*		Only Comment Author And Article Author Can See Them			*/
/*------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div id="notifications-page" class="wrap private-messages">

	<script type="text/javascript">
	</script>

	<h1 style="margin-bottom: 20px;"><?php echo t("Notifications"); ?></h1>

	<?php
	wp_enqueue_script( 'listman' );
	wp_print_scripts();
	
	wp_enqueue_script('xgb-mark-as-read-ajax', plugin_dir_url( __FILE__ ) . 'xgb-mark-as-read-ajax.js', array('jquery'));
	wp_localize_script('xgb-mark-as-read-ajax','xgb_nonce_object', array (
			'xgb_nonce' => wp_create_nonce('xgb-nonce-x')
		)
	);
	?>

	<?php 
	
	// Retrieve The User
	global $current_user;
	get_currentuserinfo();
	
	global $wpdb;
	
	// A Variable To To See If User Has At Least One Notification
	$on_top_of_everything = 1;
	
	// Clear The Has Notification Tag When This Page Is Loaded
	update_user_meta( $current_user->ID, 'xgb_has_notification', 0 );
	
	// Single Notification For Approved Articles
	$number_of_approved_articles = $wpdb->get_var("SELECT COUNT(ID) FROM wp_xgb_offer WHERE user_id={$current_user->ID} AND status='accepted'");
	
	if($number_of_approved_articles > 0) { 
		$on_top_of_everything = 0; ?>
		<div class="notice notification-message notice-warning">
			<p>You have <?php echo $number_of_approved_articles ?> articles waiting to be published. &nbsp; <strong><a href="http://www.millionclues.net/wp-admin/admin.php?page=approved-requests">View Approved Articles</a></strong></p>
		</div>
	<?php }
	
	// Single Notificaion For Incoming Requests 
	$number_of_new_requests = $wpdb->get_var("SELECT COUNT(ID) FROM wp_xgb_offer WHERE original_post_author_id={$current_user->ID} AND status='posted'");
	
	if($number_of_new_requests > 0) { 
		$on_top_of_everything = 0; ?>
		<div class="notice notification-message notice-warning">
			<p>You have <?php echo $number_of_new_requests ?> new request for your articles. &nbsp; <strong><a href="http://www.millionclues.net/wp-admin/admin.php?page=manage-requests">View Incoming Requests</a></strong></p>
		</div>
	<?php }
	
	// Notifications For Post Published Or Request Denied
	$all_unread_notifications_from_xgb_offer = $wpdb->get_results("SELECT * 
											FROM wp_xgb_offer 
											WHERE notification_read_status=0 AND (status='rejected' OR status='published')
											ORDER BY offer_published_on  
											DESC");
											
				foreach($all_unread_notifications_from_xgb_offer as $notification) {
					
					switch ($notification->status) {
						
						// Request Rejected
						case 'rejected':
							if( $notification->user_id == $current_user->ID ) { ?>
								<div id="unread-notification-<?php echo $notification->ID; ?>" class="notice notification-message notice-error">
									<div class="authoravatar">
										<?php if (function_exists('get_avatar')) { 
												$the_user_who_rejected = get_userdata($notification->original_post_author_id);
												echo get_avatar( $the_user_who_rejected->user_email, '35' ); 
											}?>
									</div>
									<div class="notificationcontent">
										<p><?
											if( get_post_status( $notification->post_id ) == 'publish') {
												echo $the_user_who_rejected->display_name .' rejected your request for <a href="'.get_permalink($notification->post_id).'">his article</a> &nbsp; 
												<strong>
													<a href="http://www.millionclues.net/wp-admin/admin.php?page=pending-requests">
														View Reason
													</a>
												</strong>
													<a data-offerid="'.$notification->ID.'" data-type="request" id="mark-notification-as-read-'.$notification->ID.'" class="notificationdismiss" href="#">Mark As Read</a>';
											}
											else {
												echo $the_user_who_rejected->display_name .' rejected your request for <abbr title="Article no longer live. It was sent to another member or deleted." style="border-bottom: medium none; color: #7d8055; text-decoration: line-through;">his article</abbr> &nbsp; 
												<strong>
													<a href="http://www.millionclues.net/wp-admin/admin.php?page=pending-requests">
														View Reason
													</a>
												</strong>
													<a data-offerid="'.$notification->ID.'" data-type="request" id="mark-notification-as-read-'.$notification->ID.'" class="notificationdismiss" href="#">Mark As Read</a>'; 	
											}?>
										</p>
									</div>
								</div><?php
								$on_top_of_everything = 0;
							}
						break;
						
						// Article Published
						case 'published': 
							if( $notification->original_post_author_id == $current_user->ID ) { ?>
								<div id="unread-notification-<?php echo $notification->ID; ?>" class="notice notification-message notice-success">
									<div class="authoravatar">
										<?php if (function_exists('get_avatar')) { 
												$the_user_who_published = get_userdata($notification->post_author_id);
												echo get_avatar( $the_user_who_published->user_email, '35' ); 
											}?>
									</div>
									<div class="notificationcontent">
										<p>
											<?php echo $the_user_who_published->display_name .' published your article on '.date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->offer_published_on)).' &nbsp; 
											<strong>
												<a href="'.$notification->live_url.'">
													View Live Article
												</a>
											</strong>
												<a data-offerid="'.$notification->ID.'" data-type="request" id="mark-notification-as-read-'.$notification->ID.'" class="notificationdismiss" href="#">Mark As Read</a>'; ?>
										</p>
									</div>
								</div><?php
								$on_top_of_everything = 0;
							}
						break;
					}
				}
	
	// Notifications For Comments
	$all_unread_received_comments = $wpdb->get_results("SELECT DISTINCT wp_comments.comment_ID 
											FROM wp_comments
											INNER JOIN wp_commentmeta AS wcmeta1 ON (wcmeta1.comment_id = wp_comments.comment_ID) 
											INNER JOIN wp_commentmeta AS wcmeta2 ON (wcmeta2.comment_id = wp_comments.comment_ID) 
											INNER JOIN wp_posts ON wp_posts.ID = wp_comments.comment_post_ID 
											WHERE wp_comments.comment_approved=1 AND (wp_posts.post_author={$current_user->ID} AND wp_comments.user_id!={$current_user->ID} AND wcmeta2.meta_key='xgb_comment_read_status' AND wcmeta2.meta_value=0)
											OR (wcmeta1.meta_key='xgb_comment_parent_user_id' AND wcmeta1.meta_value={$current_user->ID} AND wcmeta2.meta_key='xgb_comment_read_status' AND wcmeta2.meta_value=0)
											ORDER BY wp_comments.comment_date 
											DESC");
				
				foreach($all_unread_received_comments as $comment) {
					
					// Get The Single Comment
					$comment_data = get_comment($comment->comment_ID);
					
					if( $comment_data->user_id != $current_user->ID ) {
						
						$on_top_of_everything = 0; ?>
						
						<div id="unread-comment-<?php echo $comment_data->comment_ID; ?>" class="notice notification-message notice-new-message">
							<div class="authoravatar">
								<?php if (function_exists('get_avatar')) { 
										echo get_avatar( $comment_data->comment_author_email, '35' ); 
									}?>
							</div>
							<div class="notificationcontent">
								<p>
									<?php echo $comment_data->comment_author .' sent you a message on '.  date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($comment_data->comment_date)) .'&nbsp;
									<strong>
										<a href="'. get_permalink($comment_data->comment_post_ID) .'#comment-'. $comment_data->comment_ID .'">
											View Message
										</a>
									</strong>
										<a data-commentid="'.$comment_data->comment_ID.'" data-type="message" id="mark-as-read-for-'.$comment_data->comment_ID.'" class="notificationdismiss" href="#">Mark As Read</a>'; ?>
								</p>
							</div>
						</div>
					<?php }
				}
	
	// Sample Notifications For New User For The First 48 Hours While There Are No Other Notifications
	if( (strtotime( $current_user->user_registered ) > ( time() - 86400 )) && ($on_top_of_everything == 1) ) { ?>
		
		<?php if (get_user_meta($current_user->ID, 'description',true) == '') { ?>
			<div class="notice notification-message notice-success">
				<?php echo '<p>Remember to update your bio. <strong><a href="http://www.millionclues.net/wp-admin/profile.php#description">Write A Bio</a></strong></p>' ; ?>
			</div><?php
		}
		else { ?>
			<div class="notice notification-message notice-success">
				<?php echo '<p>You updated your bio. Good job!</p>' ; ?>
			</div><?php
		} ?>
		
		<div class="notice notification-message notice-success" style="opacity: 0.75;">
			<?php echo '<p>'.date("d M Y", strtotime($current_user->user_registered)).': '.$current_user->display_name.' became a member.</p>' ; ?>
		</div>
		<div class="notice notification-message notice-success" style="opacity: 0.5;">
			<?php echo '<p>02 March 2012: First line of code was written for MillionClues.NET</p>' ; ?>
		</div>	
	<?php }
	else if ($on_top_of_everything == 1) { ?>
		<div class="notice notification-message notice-success">
			<?php echo '<p>You seem to have taken care of everything. Like a boss.</p>' ; ?>
		</div><?php
	}
	
?>
</div>