/*------------------------------------------------------*/
/*			Ajax For Use With notifications.php			*/
/*	  		  Source: http://bit.ly/1LmE4dF				*/
/*------------------------------------------------------*/

jQuery(document).ready(function($) {
	$('.notificationdismiss').click(function() {
	
		var type = ($(this).data('type'));
		
		if ( type == 'request' ) {
		
			var oid = ($(this).data('offerid'));
			$('#unread-notification-' + oid).slideUp();
			
			data = {
				action: 'xgb_change_request_read_status',
				offer_id: oid,
				xgb_nonce_for_request: xgb_nonce_object.xgb_nonce
			};
			
			$.post(ajaxurl, data, function () {
			
			});
		
		}
		
		else if ( type == 'message' ) {
		
			// Use data-variable="value" in html to pass data here.
			var cid = ($(this).data('commentid'));
			$('#unread-comment-' + cid).slideUp();
			
			// This data is sent to the function. This is where data goes to the function xgb_mark_comments_as_read in xgb
			data = {
				action: 'xgb_change_comment_read_status',
				comment_id: cid,
				xgb_nonce_for_comment: xgb_nonce_object.xgb_nonce
			};
			
			$.post(ajaxurl, data, function () {
			
			});
			
			/* To get response from the function
			$.post(ajaxurl, data, function (response) {
				alert(response);
			});*/
		
		}
	});
});