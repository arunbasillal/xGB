<?php
/*------------------------------------------------------------------*/
/*						Approved Requests							*/
/* 			 	Originially Contained In: offers.php				*/
/*------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once('manage-requests-core.php');
?>

<div class="wrap manage-requests approved-requests">

	<script type="text/javascript">
		function geturl(id,nonce){
			var liveURL = prompt("Please Enter A Valid URL. Remember To Include http://", "http://");
			var reurl = /^(http[s]?:\/\/){1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/;
			if (!reurl.test(liveURL)) {
				alert("Please Enter A Valid URL. Remember To Include http://");
				return false;
			}
			else if (liveURL != null) {
				window.location="admin.php?page=approved-requests&action=published&offer="+id+"&url="+liveURL+"&submit_="+nonce;
			}
		}
	</script>

	<h1><?php echo t("Approved Requests"); ?></h1>

	<?php
	wp_enqueue_script( 'listman' );
	wp_print_scripts();
	?>



	<?php
	/*--------------------------------------*/
	/*			Approved Requests			*/
	/*--------------------------------------*/
	?>
	<div id="SubmitLink"></div>
	<h3><?php echo t("Articles Ready For You To Publish"); ?></h3>
	<p><?php echo t("Articles of other authors approved and ready for you to publish on your website are listed here. <br>After you publish the article, remember to click 'Submit Link' to send the LIVE link back to the author."); ?></p>
	
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" width="2%"><input style="margin-left: 0px;" type="checkbox" id="cb-select-all"></th>
				<th scope="col" width="30%"><?php e('Title') ?></th>
				<th scope="col" width="14%"><?php e('Site') ?></th>
				<th scope="col" width="20%"><?php e('Date') ?></th>
				<th scope="col" width="17%"><?php e('Status') ?></th>
				<th scope="col" width="17%"><?php e('Action') ?></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<?php
			$all_offer = $wpdb->get_results("SELECT wp_xgb_offer.ID, wp_posts.post_title, wp_xgb_offer.post_id, wp_xgb_offer.site, wp_xgb_offer.note, wp_xgb_offer.offer_made_on, wp_xgb_offer.status 
												FROM wp_xgb_offer INNER JOIN wp_posts ON wp_xgb_offer.post_id=wp_posts.ID
												WHERE wp_xgb_offer.user_id={$current_user->ID} AND wp_xgb_offer.status='accepted' 
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
							<strong>
								<a class="row-title" href="<?php echo get_edit_post_link($offer->post_id);  ?>">
									<?php echo $offer->post_title; ?>
								</a>
							</strong>
						</td>
						<td>
							<a href="<?php echo $offer->site; ?>">
								<?php echo $offer->site; ?>
							</a>
						</td>
						<td class="date column-date">
							Request Made On<br><?php echo date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($offer->offer_made_on)); ?>
						</td>
						<td>
							<?php if($offer->status == 'accepted') { ?>
										<span class="status-of-post pending">Request Approved</span>
							<?php } ?>
						</td>
						<td><?php
							$submit_nonce = wp_create_nonce( 'submit_url_nonce' ); ?>	
							<a href='#' class='submitbutton' onclick=geturl(<?php echo '\''.$offer->ID.'\',\''.$submit_nonce.'\''; ?>)>
								<?php echo t('Submit Link');?>
							</a>
						</td>
					</tr>
			<?php
				}
			} else { ?>
				<tr>
					<td colspan="6">
						<?php e('When an author accepts your offer it will be listed here. You will have full access to the article and you can publish them on your website.'); ?>
					</td>
				</tr>
			<?php
			} ?>
			
		</tbody>
	</table>

</div>