<?php
/*------------------------------------------------------------------*/
/*							Pending Requests						*/
/* 			 	Originially Contained In: offers.php				*/
/*------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once('manage-requests-core.php');
?>

<div class="wrap manage-requests pending-requests">

	<h1><?php echo t("Manage Requests"); ?></h1>

	<?php
	wp_enqueue_script( 'listman' );
	wp_print_scripts();
	?>

	
	
	<?php
	/*--------------------------------------*/
	/*			Pending Requests			*/
	/*--------------------------------------*/
	?>

	<h2 class="nav-tab-wrapper">
			<a class="nav-tab" href="http://www.millionclues.net/wp-admin/admin.php?page=manage-requests">Incoming Requests</a>
			<a class="nav-tab nav-tab-active" href="http://www.millionclues.net/wp-admin/admin.php?page=pending-requests">Pending Requests</a>
	</h2>

	<h3><?php echo t("Pending Requests Waiting For Author Approval"); ?></h3>
	<p>Requests you have made on articles of other authors that are pending are listed here.</p>
	
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" width="2%"><input style="margin-left: 0px;" type="checkbox" id="cb-select-all"></th>
				<th scope="col" width="30%"><?php e('Title') ?></th>
				<th scope="col" width="12%"><?php e('Author') ?></th>
				<th scope="col" width="22%"><?php e('Details / Notes') ?></th>
				<th scope="col" width="17%"><?php e('Request Date') ?></th>
				<th scope="col" width="17%"><?php e('Status') ?></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<?php
			$all_offer = $wpdb->get_results("SELECT wp_xgb_offer.ID, wp_posts.post_title, wp_xgb_offer.post_id, wp_xgb_offer.site, wp_xgb_offer.note, wp_xgb_offer.offer_made_on, wp_xgb_offer.status, wp_xgb_offer.original_post_author_id 
												FROM wp_xgb_offer INNER JOIN wp_posts ON wp_xgb_offer.post_id=wp_posts.ID
												WHERE wp_xgb_offer.user_id={$current_user->ID} AND (wp_xgb_offer.status='posted' OR wp_xgb_offer.status='rejected') AND wp_xgb_offer.notification_read_status=0 
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
								<?php if( get_post_status( $offer->post_id ) != 'publish') { 
										echo '<strong><span style="text-decoration: line-through">'.$offer->post_title.'</span></strong><br>Article No Longer Live.';
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
								<?php $user_info = get_userdata($offer->original_post_author_id); ?><a href="<?php echo get_site_url().'/author/'.$user_info->user_nicename; ?>"><?php echo $user_info->display_name; ?></a>
								</td>
							<td>
								Website: <a href="<?php echo $offer->site; ?>">
									<?php echo $offer->site; ?>
								</a>
								<?php echo '<br><br>'.$offer->note; ?>
								</td>
							<td class="date column-date">
								<?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($offer->offer_made_on)); ?>
								</td>
							<td>
								<?php if($offer->status == 'posted') { ?><span class="status-of-post pending">Request Pending Approval</span><?php }
								else if($offer->status == 'rejected') { ?><span class="status-of-post status-of-post-submit-link" style="color:#fff;">Request Was Rejected</span><?php } ?>
								</td>
						</tr>
			<?php
				}
			} else {?>
				<tr>
					<td colspan="6"><?php e('You do not have any pending requests. Go check the live articles and make some.') ?></td>
				</tr>
			<?php
			}?>
			
		</tbody>
	</table>

</div>