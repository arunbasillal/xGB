<?php
/*------------------------------------------------------------------*/
/*			Manage Requests Admin Page / Incoming Requests			*/
/* 			 	Originially Contained In: offers.php				*/
/*------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once('manage-requests-core.php');
?>

<div class="wrap manage-requests incoming-requests">

	<script type="text/javascript">
		function getchangemymindreason(id,concex){
			var reason = prompt("Did you change your mind? Please provide a reason: ", "");
			if (reason == '') { reason = "No reason was provided."; }
			if (reason) {window.location="admin.php?page=manage-requests&action=changemymind&offer="+id+"&reason="+reason+"&undo_="+concex;}
			}
		function getrejectreason(id,nonce){
			var reason = prompt("Please provide a reason for your rejection:", "");
			if (reason == '') { reason = "No reason was provided."; }
			if (reason) {window.location="admin.php?page=manage-requests&action=reject&offer="+id+"&reason="+reason+"&reject_="+nonce;}
			}
	</script>

	<h1><?php echo t("Manage Requests"); ?></h1>

	<?php
	wp_enqueue_script( 'listman' );
	wp_print_scripts();
	?>



	<?php
	/*--------------------------------------*/
	/*			Incoming Requests			*/
	/*--------------------------------------*/
	?>

	<h2 class="nav-tab-wrapper">
			<a class="nav-tab nav-tab-active" href="http://www.millionclues.net/wp-admin/admin.php?page=manage-requests">Incoming Requests</a>
			<a class="nav-tab" href="http://www.millionclues.net/wp-admin/admin.php?page=pending-requests">Pending Requests</a>
	</h2>
				
	<h3><?php echo t("Incoming Requests For Your Articles"); ?></h3>
	<p>Manage requests from users who are interested in publishing your articles on their website.</p>

	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" width="2%"><input style="margin-left: 0px;" type="checkbox" id="cb-select-all"></th>
				<th scope="col" width="20%"><?php e('Title') ?></th>
				<th scope="col" width="12%"><?php e('From') ?></th>
				<th scope="col" width="23%"><?php e('Details / Notes') ?></th>
				<th scope="col" width="15%"><?php e('Request Date') ?></th>
				<th scope="col" width="28%"><?php e('Action') ?></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<?php
			$all_offer = $wpdb->get_results("SELECT wp_xgb_offer.ID, wp_xgb_offer.user_id, wp_posts.post_title, wp_xgb_offer.post_id, wp_xgb_offer.post_author_id, wp_xgb_offer.original_post_author_id, wp_xgb_offer.site, wp_xgb_offer.note, wp_xgb_offer.offer_made_on, wp_xgb_offer.status 
											FROM wp_xgb_offer INNER JOIN wp_posts ON wp_xgb_offer.post_id=wp_posts.ID
											WHERE wp_xgb_offer.original_post_author_id={$current_user->ID} AND wp_xgb_offer.status!='published' AND wp_xgb_offer.status!='rejected' 
											ORDER BY wp_xgb_offer.offer_made_on 
											DESC");

			if (count($all_offer)) {
				$class = 'alternate';
				
				foreach($all_offer as $offer) {
					$class = ('alternate' == $class) ? '' : 'alternate'; ?>

					<tr id='offer-<?php echo $offer->ID;?>' class='<?php echo $class; ?>'>
						<td>
							<input type="checkbox" id="cb-select">
						</td>
						<td>
							<?php if( get_post_status( $offer->post_id ) == 'private') { 
										echo '<strong><span style="text-decoration: line-through">'.$offer->post_title.'</span></strong><br>Article Sent To Publish And No Longer Live.';
									} 
									else { ?>
										<strong>
											<a class="row-title" href="<?php echo get_permalink($offer->post_id);  ?>">
												<?php echo $offer->post_title; ?>
												</a>
											</strong><?php 
									} ?>
							</td>
						<td>
							<?php $user_info = get_userdata($offer->user_id); ?><a href="<?php echo get_site_url().'/author/'.$user_info->user_nicename; ?>"><?php echo $user_info->display_name; ?></a>
							</td>
						<td>
							Users Website: <a href="<?php echo $offer->site ?>">
												<?php echo $offer->site ?>
											</a>
											<?php if($offer->note) { 
													echo '<br><br>'.$offer->note; 
											} ?>
							</td>
						<td class="date column-date">
							<?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($offer->offer_made_on)); ?>
							</td>
						<td>
							<?php if($offer->status == 'accepted') { 
										$undo_nonce = wp_create_nonce( 'undo_url_nonce' ); ?>
										<span class="status-of-post pending">Request Accepted</span> &nbsp; &nbsp;<a href='#' class="rejectbutton" onclick=getchangemymindreason(<?php echo '\''.$offer->ID.'\',\''.$undo_nonce.'\''; ?>)><?php echo t('Undo?')?></a> <?php 
									} 
									else if (($offer->post_author_id == $offer->original_post_author_id) && ($offer->status == 'posted') ) { 
										
										// Nouces
										$accept_request_url = 'http://www.millionclues.net/wp-admin/admin.php?page=manage-requests&amp;action=accept&offer='.$offer->ID;
										$accept_request_url_nonce = wp_nonce_url( $accept_request_url, 'accept_this_offer', 'accept_request_' );
										$reject_nonce = wp_create_nonce( 'reject_url_nonce' ); ?>	
										
										<a class ="acceptbutton" href="<?php echo $accept_request_url_nonce; ?>">
											<?php echo t('Accept Request')?>
										</a> &nbsp; &nbsp; 
										<a class="rejectbutton" href='#' onclick=getrejectreason(<?php echo '\''.$offer->ID.'\',\''.$reject_nonce.'\''; ?>)>
											<?php echo t('Reject Request')?>
										</a><?php 
									} ?>
							</td>
					</tr>
			<?php
					}
			} 
			
			else {
			?>
				<tr>
					<td colspan="6"><?php e('There are no active requests for your articles at the moment.') ?></td>
				</tr>
			<?php
			}?>
			
		</tbody>
	</table>

</div>