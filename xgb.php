<?php
/*
Plugin Name: xGB
Description: xGB Core.
Version: 1.10.0
Author: Arun Basil Lal
Author URI: http://millionclues.com/

Notes: 
- Code used from other sources is credited wherever relevant.
- Thanks to Binny VA (https://twitter.com/binnyva) for sharing his plugin development framework wpframe.php and guidance. 
- Search for :TODO: to find ideas for features that can be incorporated in the future. 
*/

include('wpframe.php');
wpframe_stop_direct_call(__FILE__);



/*--------------------------------------------------------------------------*/
/*			Set Global Live Article Publish Limits For User Roles			*/
/*				Role: basic_user 	= 3									*/
/*				Role: pro_user		= 20									*/
/*--------------------------------------------------------------------------*/
function set_global_live_article_publish_limit () {
	if ( current_user_can('basic_user') ) {
			$GLOBALS['live_article_limit'] = 3;
		}
		else if ( current_user_can('have_pro_user_features') ) {		// have_pro_user_features is for pro_user and administrator role.
			$GLOBALS['live_article_limit'] = 20;
		}
}
add_action( 'admin_init', 'set_global_live_article_publish_limit');



/*----------------------------------*/
/*			Add Menu Links			*/
/*----------------------------------*/
add_action( 'admin_menu', 'xgb_add_menu_links' );
function xgb_add_menu_links() {
	global $_registered_pages;

	// Manage Requests(Originally Manage Offers in offers.php)
	function xgb_manage_articles_main_menu_page() {
		require_once( plugin_dir_path(__FILE__) . "manage-requests-incoming.php" );
	}
	add_menu_page(__('Incoming Requests', 'xgb'), __('XGB Admin', 'xgb'), 'edit_posts', 'manage-requests', 'xgb_manage_articles_main_menu_page');
	
	// Manage Requests -> Incoming Requests
	function xgb_incoming_requests_submenu_page() {
		require_once( plugin_dir_path(__FILE__) . "manage-requests-incoming.php" );
	}
	add_submenu_page('manage-requests',__('Incoming Requests', 'xgb'), __('Incoming Requests', 'xgb'), 'edit_posts', 'manage-requests', 'xgb_incoming_requests_submenu_page');
	
	// Manage Requests -> Approved Requests
	function xgb_approved_requests_submenu_page() {
		require_once( plugin_dir_path(__FILE__) . "manage-requests-approved.php" );
	}
	add_submenu_page('manage-requests',__('Approved Requests', 'xgb'), __('Approved Requests', 'xgb'), 'edit_posts', 'approved-requests', 'xgb_approved_requests_submenu_page');
	
	// Manage Requests -> Pending Requests
	function xgb_pending_requests_submenu_page() {
		require_once( plugin_dir_path(__FILE__) . "manage-requests-pending.php" );
	}
	add_submenu_page('manage-requests',__('Pending Requests', 'xgb'), __('Pending Requests', 'xgb'), 'edit_posts', 'pending-requests', 'xgb_pending_requests_submenu_page');
	
	// Manage Requests -> Your Articles Published By Others
	function xgb_published_articles_by_others_submenu_page() {
		require_once( plugin_dir_path(__FILE__) . "published-articles-by-others.php" );
	}
	add_submenu_page('manage-requests',__('Articles Published By Others', 'xgb'), __('Articles Published By Others', 'xgb'), 'edit_posts', 'published-by-others', 'xgb_published_articles_by_others_submenu_page');
	
	// Manage Requests -> Articles You Published From Others
	function xgb_published_articles_by_you_submenu_page() {
		require_once( plugin_dir_path(__FILE__) . "published-articles-by-you.php" );
	}
	add_submenu_page('manage-requests',__('Articles Published By You', 'xgb'), __('Articles Published By You', 'xgb'), 'edit_posts', 'published-by-you', 'xgb_published_articles_by_you_submenu_page');
	
	// Manage Requests -> Notifications
	function xgb_notifications_submenu_page() {
		require_once( plugin_dir_path(__FILE__) . "notifications.php" );
	}
	add_submenu_page('manage-requests',__('Notifications', 'xgb'), __('Notifications', 'xgb'), 'edit_posts', 'notifications', 'xgb_notifications_submenu_page');
}



/*------------------------------------------*/
/*			Set Positing Limits				*/
/*		LIMIT to 3 For Basic User 			*/
/*			and 20 For Pro User				*/
/*------------------------------------------*/
function xgb_check_post_count($post_id) {
	global $wpdb;
	
	$post = get_post($post_id);
	$author_id = $post->post_author;
	$user_info = get_userdata(get_current_user_id());
	
		$published_post_count = $wpdb->get_var("SELECT COUNT(ID) FROM wp_posts WHERE post_status='publish' AND post_type='post' AND post_author=$author_id");
		if($published_post_count > $GLOBALS['live_article_limit'] ) {
			$wpdb->query("UPDATE wp_posts SET post_status='pending' WHERE ID=$post_id");
			// Set the transient for admin notice
			set_transient( get_current_user_id().'publisherror', $GLOBALS['live_article_limit'] ); 
		}
}
add_action('publish_post','xgb_check_post_count', 1);

/* The Admin Notice for xgb_check_post_count */
/* http://wordpress.stackexchange.com/a/136992/90061 */
function show_admin_notice($post_id) {
    if($out = get_transient( get_current_user_id().'publisherror' ) ) {
        delete_transient( get_current_user_id().'publisherror' );
		$post = get_post($post_id);
        echo '<div class="updated notice notice-success"><p>Article was saved as a Pending because you reached your limit. <a href="'.get_permalink($post).'">Preview Article</a> <br>You can have a maximum of '.$out.' live articles at a time. <br>You can wait till one of you existing articles is published or move one of them into trash and publish this again.</p></div>';
    }
}
add_action( 'admin_notices', 'show_admin_notice' );

/* Remove 'Post Published' Notice for xgb_check_post_count */
/* http://ryanwelcher.com/2014/10/change-wordpress-post-updated-messages/ */
function rw_post_updated_messages( $messages ) {

		$post = get_post();
		$post_type = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );
		
		if ( !get_transient( get_current_user_id().'publisherror' ) ){
			return $messages;
		}
		else {
			$messages['post'][6] = '';
		}
		
		return $messages;
}
add_filter( 'post_updated_messages', 'rw_post_updated_messages' );



/*------------------------------------------------------------------------------*/
/*				Shows a warning in the off chance that someone edits			*/ 
/*			an article assigned to them ie edit an "Approved Article"			*/
/*					Approved Articles = Private Post Status						*/
/*------------------------------------------------------------------------------*/

/* :TODO: Need to look into this if this turns out to be a problem and many people edit articles assigned to them, i.e. APPROVED Articles */
/* Right now the Update button is hidden via CSS using hide_publishing_actions_for_private_posts() in functions.php */
/* For now it just shows an error message from warning_admin_notice() */
function try_to_edit_private_post($post_id) {
	/*global $wpdb;
	
	$post = get_post($post_id);
	$author_id = $post->post_author;
	
	// need to check if this happens only for accepted posts and not just every save.
	
	//if post is from another user it means that it is a post that was given to the author from another author and is in his Accepted Articles
	$check_if_its_from_another_author = $wpdb->get_var("SELECT original_post_author_id FROM wp_xgb_offer WHERE post_id=$author_id");
	if (($check_if_its_from_another_author != $author_id) || ($check_if_its_from_another_author == '') {
		$wpdb->query("UPDATE wp_posts SET post_status='private' WHERE ID=$post_id");
		return;
	}
	
	$us = wp_is_post_revision( $post_id );
	if ($us == false)
	$us=1;
	
	if ( $parent_id != wp_is_post_revision( $post_id ) ) 
		$post_id = $parent_id;
		
	// unhook this function so it doesn't loop infinitely
		remove_action( 'private_post', 'check_original_post_author' );
		
	// update the post, which calls save_post again
		wp_restore_post_revision($revisions[1]->post_id);
		
	// re-hook this function
		add_action( 'private_post', 'check_original_post_author' );
	*/
	
	set_transient( get_current_user_id().'testnotice', 20 ); 
}
add_action('private_post','try_to_edit_private_post', 1);

/* The warning message to show if try_to_edit_private_post() happens */
/* :TODO: Send Admin an email as well */
function warning_admin_notice() {
    if($out = get_transient( get_current_user_id().'testnotice' ) ) {
        delete_transient( get_current_user_id().'testnotice' );
        echo '<div class="notice error"><p>Admin Warning: Please do not edit articles assigned to you.</p></div>';
    }
}
add_action('admin_notices', "warning_admin_notice");



/*--------------------------------------------------------------*/
/*			Meta Box in Post Editor For Private Posts			*/
/* 				To Submit The Live Link Once The 				*/
/*					Article Is Published						*/
/*--------------------------------------------------------------*/

/* https://developer.wordpress.org/plugins/metadata/creating-custom-meta-boxes */
/* https://www.smashingmagazine.com/2011/10/create-custom-post-meta-boxes-wordpress/ */
/* http://themefoundation.com/wordpress-meta-boxes-guide/ */

/* Create one or more meta boxes to be displayed on the post editor screen. */
function xgb_submit_live_link_meta_box() {
        add_meta_box(
            'xgb_live_link_id',            			// Unique ID
            esc_html__('Publishing Instructions','xgb'),   // Box title
            'xgb_submit_live_link_custom_box',  	// Content callback
            'post',		                  			// Post type
			'side',									// Context
			'high'									// Priority
        );
}

/* Display the post meta box. */
function xgb_submit_live_link_custom_box( $object, $box ) {
		
		global $wpdb;
		$post_id = $_GET['post'];
		$post = get_post($post_id);
		$author_id = $post->post_author;
		
		$offer_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}xgb_offer WHERE post_id={$post_id} AND post_author_id={$author_id} AND user_id={$author_id} AND status='accepted'")
		
		?>
		<script type="text/javascript">
		function geturl(id,nonce){
			var liveURL = prompt("Enter the link to the LIVE article : ", "http://");
			var reurl = /^(http[s]?:\/\/){1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/;
			if (!reurl.test(liveURL)) {
				alert("Please Enter A Valid URL. Remember To Include http://");
				return false;
			}
			else if (liveURL != null) {
				window.location="admin.php?page=approved-requests&action=published&offer="+id+"&url="+ liveURL+"&submit_="+nonce;
			}
		}
		</script>
		
		<ul>
			<li>1. Download all the images and upload it to your website.</li>
			<li>2. Switch to 'Text' view and copy the content to your editor.</li>
			<li>3. If images are broken, change the image urls with the images on your server.</li>
			<li>4. After you publish this article, submit the live link here to send it back to the author.</li>
		</ul>
		
		<?php $submit_nonce = wp_create_nonce( 'submit_url_nonce' ); ?>	
		<div id="submitlink"><a href='#' class='submitbutton' onclick=geturl(<?php echo '\''.$offer_id.'\',\''.$submit_nonce.'\''; ?>)><?php echo t('Submit Live Link')?></a></div>
		
<?php }

/* Meta box setup function. */
function xgb_submit_live_link_meta_box_setup() {

	// Add meta boxes on the 'add_meta_boxes' hook only for private posts
	if (get_post_status($_GET['post']) == 'private'){
		add_action( 'add_meta_boxes', 'xgb_submit_live_link_meta_box' );
	}
}
add_action( 'load-post.php', 'xgb_submit_live_link_meta_box_setup' );



/*----------------------------------------------------------*/
/*			Function That Marks Comments As Read			*/
/*	Used In: notifications.php And xgb-mark-as-read-ajax.js	*/
/*----------------------------------------------------------*/
function xgb_mark_comments_as_read() {
	
	// Security Check
	if( !isset( $_POST['xgb_nonce_for_comment'] ) || !wp_verify_nonce( $_POST['xgb_nonce_for_comment'], 'xgb-nonce-x' ) )
		die();
	
	update_comment_meta($_POST['comment_id'], 'xgb_comment_read_status', 1);
	die();	// Always die() While Working With Ajax
}
add_action('wp_ajax_xgb_change_comment_read_status','xgb_mark_comments_as_read');



/*----------------------------------------------------------------------*/
/*			Function That Marks Request Notifications As Read			*/
/*		Used In: notifications.php and xgb-mark-as-read-ajax.js			*/
/*----------------------------------------------------------------------*/
function xgb_mark_requests_as_read() {
	
	// Security Check
	if( !isset( $_POST['xgb_nonce_for_request'] ) || !wp_verify_nonce( $_POST['xgb_nonce_for_request'], 'xgb-nonce-x' ) )
		die();
	
	global $wpdb;
	$wpdb->query("UPDATE {$wpdb->prefix}xgb_offer SET notification_read_status=1 WHERE ID={$_POST['offer_id']}");
	
	die();	// Always die() While Working With Ajax
}
add_action('wp_ajax_xgb_change_request_read_status','xgb_mark_requests_as_read');



/*--------------------------------------------------------------------------------*/
/*						Custom New User Registration Email						  */
/* Codex: https://codex.wordpress.org/Function_Reference/wp_new_user_notification */
/*--------------------------------------------------------------------------------*/
if ( !function_exists('wp_new_user_notification') ) {
    function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
	if ( $deprecated !== null ) {
		_deprecated_argument( __FUNCTION__, '4.3.1' );
	}

	global $wpdb, $wp_hasher;
	$user = get_userdata( $user_id );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$subject  = 'New User Registration';
	$message  = 'New user info:' . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n";
	$message .= 'Profile: http://www.millionclues.net/author/' . $user->user_nicename . "\r\n";
	$message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";

	// Notify Admin
	@xgb_notify_user(get_option('admin_email'), $subject, $message, 1);

	if ( 'admin' === $notify || empty( $notify ) ) {
		return;
	}

	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	/** This action is documented in wp-login.php */
	do_action( 'retrieve_password_key', $user->user_login, $key );

	// Now insert the key, hashed, into the DB.
	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	$subject = "Welcome! Your activation link and login info";
	
	$message = "Hey ".$user->display_name."\r\n\r\nThank you for registering. You are one step away from activating your account.\r\n\r\n";
	$message .= sprintf(__('Your username is: %s'), $user->user_login) . "\r\n\r\n";
	$message .= __('To set your password and activate your account, visit the following address:') . "\r\n";
	$message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "\r\n\r\n";
    $message .= "Login troubles? Contact me:\r\nhttp://millionclues.com/contact/\r\n\r\n";
	$message .= "See you inside!\r\n\r\nArun Basil Lal\r\n- MillionClues.NET\r\n\r\n---\r\n".xgb_generate_quote();

	xgb_notify_user($user->user_email, $subject, $message, 1);
    }
}



/*----------------------------------------------------------*/
/*			Function To Send Notification Emails			*/
/*	Email Functions Beside Here: 							*/
/*	- xgb_custom_comment_text() functions.php for comments	*/
/*	- contact.php in gbx-theme/contact.php					*/
/*----------------------------------------------------------*/
function xgb_notify_user( $email_of_user, $subject, $message, $no_footer  ) {

	// Add [MillionClues.NET] To The Subject
	$subject = '[MillionClues.NET] '.$subject;

	// Headers
	$headers   = array();
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/plain; charset=iso-8859-1";
	$headers[] = "Message-id: " .sprintf( "<%s.%s@%s>", base_convert(microtime(), 10, 36), base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36), $_SERVER['SERVER_NAME'] );
	$headers[] = "From: MillionClues.NET <notbot@millionclues.net>";
	$headers[] = "Reply-To: MillionClues.NET <notbot@millionclues.net>";
	$headers[] = "X-Mailer: PHP/" .phpversion();
	
	if( $no_footer === null ) {
		// Add Footer For Email
		$message .= "\r\n\r\nSee all pending notifications: http://www.millionclues.net/wp-admin/admin.php?page=notifications \r\n\r\n- MillionClues.NET\r\n\r\n---\r\n".xgb_generate_quote();
	}
	
	$emailSent = wp_mail( $email_of_user, $subject, $message, $headers );
	return $emailSent;
}



/*--------------------------------------------------------------------------------------------------*/
/*			Admin Notice To Be Called When Nonce Check Fails In manage-requests-core.php			*/
/*					This function is called directly when nonce check fails.						*/
/*--------------------------------------------------------------------------------------------------*/
function xgb_request_nonce_error() { ?>
		<div class="notice notice-error">
			<p><?php _e( 'That request did not pass the necessary security checks. <br>Hint: Log out and login and try again or if this persists, <a class="dottedunderline" href="http://www.millionclues.net/support/">contact support</a>.', 'xgb' ); ?></p>
		</div> <?php
}



/*------------------------------------------------------*/
/*			Random Quote For Email Generator			*/
/*------------------------------------------------------*/
function xgb_generate_quote() {
	$quote_collection = array(
			'A pipe gives a wise man time to think and a fool something to put in his mouth.',
			'Never underestimate the power of human stupidity.',
			'Whatever goes around, comes around.',
			'Gold is for the mistress - silver for the maid -   Copper for the craftsman, cunning at his trade. But Iron - Cold Iron - is master of them all.',
			'The number of people who agree or disagree with you has absolutely no bearing on whether youre *right*.  The universe has a way of deciding that for itself.',
			'The truth of any proposition has nothing to do with its credibility...and vice versa.',
			'Money is a powerful aphrodisiac.  But flowers work almost as well.',
			'It may be better to be a live jackal than a dead lion, but it is better still to be a live lion.  And usually easier.',
			'Place your clothes and weapons where you can find them in the dark.',
			'An Elephant;  A Mouse built to government specifications.',
			'Democracy is based on the assumption that a million men are wiser than one man. Hows that again?  I missed something.',
			'Autocracy is based on the assumption that one man is wiser than a million men. Lets play that over again too.  Who decides?',
			'Taxes are not levied for the benefit of the taxed.',
			'Money is the sincerest form of flattery.',
			'Women love to be flattered. So do men.',
			'You live and learn.  Or you dont live long.',
			'Only a sadistic scoundrel - or a fool - tells the bald truth on social occasions.',
			'Be wary of strong drink.  It can make you shoot at tax collectors - and miss.',
			'Natural laws have no pity.',
			'Sin lies only in hurting other people unnecessarily.  All other sins are invented nonsense.',
			'Certainly the game is rigged.  Dont let that stop you.  If you dont bet, you cant win.',
			'Never appeal to a mans better nature.  He may not have one. Invoking his self-interest gives you more leverage.',
			'A woman is not a property. Husbands who think otherwise are living in a dreamworld.',
			'Formal courtesy between husband and wife is even more important than it is between strangers.',
			'Your friends will know you better in the first minute you meet then your acquaintances will know you in a thousand years.',
			'Argue for your limitations and, sure enough, theyre yours.',
			'You are never given a wish without also being given the power to make it come true. You may have to work for it though.',
			'Here is a test to find out whether your mission on earth is finished;  If youre alive, it isnt.',
			'In order to live free and happily, you must sacrifice boredom. It is not always an easy sacrifice.',
			'Believe it and you are half way there. ~Theodore Roosevelt',
			'There is no limit to how gently you can apply a big hammer, but there definitely is to how hard you can hit with a small one.',
			'Every man is born as many men and dies as a single one. ~Martin Heidegger ',
			'Language is the house of the truth of Being. ~Martin Heidegger ',
			'Man acts as though he were the shaper and master of language, while in fact language remains the master of man. ~Martin Heidegger',
			'The most thought-provoking thing in our thought-provoking time is that we are still not thinking. ~Martin Heidegger ',
			'The possible ranks higher than the actual. ~Martin Heidegger',
			'Unless you change how you are, you will always have what you have got. ~Jim Rohn ',
			'A stumble may prevent a fall. ~English Proverb',
			'There is no limit to what a man can achieve, if he doesnt care who gets the credit. ~Laing Burns, Jr.',
			'Dont waste yourself in rejection, nor bark against the bad, but chant the beauty of the good. ~Ralph Waldo Emerson',
			'The most practical, beautiful, workable philosophy in the world wont work - if you wont. ~Zig Ziglar ',
			'I believe the greater the handicap, the greater the triumph. ~John H. Johnson ',
			'Life shrinks or expands in proportion to ones courage. ~Anais Nin',
			'We dont see things as they are we see them as we are. ~Anais Nin',
			'You cant build a reputation on what you are going to do. ~Henry Ford',
			'The greatest form of maturity is at harvest time. That is when we must learn how to reap without complaint if the amounts are small and how to to reap without apology if the amounts are big. ~Jim Rohn ',
			'People seem not to see that their opinion of the world is also a confession of character. ~Ralph Waldo Emerson',
			'The most successful people are those who are good at plan B. ~James Yorke',
			'Opportunity is missed by most because it is dressed in overalls and looks like work. ~Thomas Alva Edison',
			'The universe is full of magical things, patiently waiting for our wits to grow sharper. ~Eden Phillpotts',
			'Experience is not what happens to a man, it is what a man does with what happens to him. ~Aldous Huxley',
			'Imagination rules the world. ~Napoleon Bonaparte',
			'Adversity has the effect of eliciting talents which, in prosperous circumstances, would have lain dormant. ~Horace',
			'It isnt that they cant see the solution, its that they cant see the problem. ~G.K. Chesterton',
			'Facts are stubborn, but statistics are more pliable. ~Mark Twain ',
			'All truth goes through three steps:  First, it is ridiculed. Second, it is violently opposed.  Finally, it is accepted as self-evident. ~Arthur Schopenhauer ',
			'An invasion of armies can be resisted; an invasion of ideas cannot be resisted. ~Victor Hugo ',
			'Pain is inevitable but misery is optional. ~Barbara Johnson ',
			'Beware of defining as intelligent only those who share your opinions. ~Ugo Ojetti ',
			'If we knew what it was we were doing, it would not be called research, would it? ~Albert Einstein ',
			'To believe a thing is impossible is to make it so. ~French proverb',
			'Simplicity is the ultimate sophistication. ~Leonardo da Vinci ',
			'To be simple is to be great. ~Ralph Waldo Emerson ',
			'The trouble about man is twofold.  He cannot learn truths which are too complicated; he forgets truths which are too simple. ~Dame Rebecca West ',
			'Everything should be as simple as it is, but not simpler. ~Albert Einstein ',
			'That you may retain your self-respect, it is better to displease the people by doing what you know is right, than to temporarily please them by doing what you know is wrong. ~William J. H. Boetcker ',
			'Many of lifes failures are people who did not realize how close they were to success when they gave up. ~Thomas Edison',
			'Hitch your wagon to a star. ~Ralph Waldo Emerson ',
			'If you knew how much work went into it, you wouldnt call it genius. ~Michelangelo',
			'I know God will not give me anything I cant handle. I just wish that He didnt trust me so much. ~Mother Teresa ',
			'If we did the things we are capable of, we would astound ourselves. ~Thomas Edison ',
			);
	
	$quote = $quote_collection[array_rand($quote_collection)];
	return $quote;
}
?>