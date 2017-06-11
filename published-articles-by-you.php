<?php
/*------------------------------------------------------------------*/
/*			List Of Published Articles Authored By Others			*/
/*						And Published By You						*/
/*------------------------------------------------------------------*/

include('wpframe.php');
wpframe_stop_direct_call(__FILE__);

// Retrieve the offers
global $current_user;
get_currentuserinfo();

global $wpdb;

?>

<div class="wrap published-articles by-you">

	<h1><?php echo t("Published Articles"); ?></h1>

	<?php
	wp_enqueue_script( 'listman' );
	wp_print_scripts();
	?>

	<?php
	/*----------------------------------------------*/
	/*			Table For Articles That Are			*/
	/* 				Published By You				*/
	/*----------------------------------------------*/
	?>
	
	<h2 class="nav-tab-wrapper">
			<a class="nav-tab" href="http://www.millionclues.net/wp-admin/admin.php?page=published-by-others">Published By Others</a>
			<a class="nav-tab nav-tab-active" href="http://www.millionclues.net/wp-admin/admin.php?page=published-by-you">Published By You</a>
	</h2>

	<h3><?php echo t("Articles You Published From Others"); ?></h3>
	<p><?php echo t("Articles written by other authors that you published on your website will appear here."); ?></p>
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" width="2%"><input style="margin-left: 0px;" type="checkbox" id="cb-select-all"></th>
				<th scope="col" width="30%"><?php e('Title') ?></th>
				<th scope="col" width="17%"><?php e('Site') ?></th>
				<th scope="col" width="17%"><?php e('Published On') ?></th>
				<th scope="col" width="17%"><?php e('Offer Made On') ?></th>
				<th scope="col" width="17%"><?php e('Article Author') ?></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<?php
			$all_offer = $wpdb->get_results("SELECT wp_xgb_offer.ID, wp_posts.post_title, wp_xgb_offer.post_id, wp_xgb_offer.original_post_author_id, wp_xgb_offer.site, wp_xgb_offer.live_url, wp_xgb_offer.offer_made_on, wp_xgb_offer.offer_published_on, wp_xgb_offer.status 
												FROM wp_xgb_offer INNER JOIN wp_posts ON wp_xgb_offer.post_id=wp_posts.ID
												WHERE wp_xgb_offer.post_author_id={$current_user->ID} AND wp_xgb_offer.status='published' 
												ORDER BY wp_xgb_offer.offer_published_on
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
							<strong>
								<a class="row-title" href="<?php echo $offer->live_url; ?>">
									<?php echo $offer->post_title; ?>
									</a>
								</strong>
							</td>
						<td>
							<a href="<?php echo $offer->site; ?>">
								<?php echo $offer->site; ?>
								</a>
							</td>
						<td>
							<?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($offer->offer_published_on)); ?>
							</td>
						<td>
							<?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($offer->offer_made_on)); ?>
							</td>
						<td>
							<?php $user_info = get_userdata($offer->original_post_author_id); ?><a href="<?php echo get_site_url().'/author/'.$user_info->user_nicename; ?>"><?php echo $user_info->display_name; ?></a>
							</td>
					</tr>
			<?php
				}
			} else {
			?>
				<tr>
					<td colspan="4"><?php e('You have not published an article yet.') ?></td>
				</tr>
			<?php
			} ?>
			
		</tbody>
	</table>

</div>