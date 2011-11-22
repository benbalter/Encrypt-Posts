jQuery( document ).ready( function( $ ){
	
	//decrypt prompt
	if ( ( pagenow == 'post' || pagenow == 'page' ) && encrypt_posts.prompt == 1 ) {
		tb_show( 'Password', '?TB_inline=true&inlineId=ep_password_prompt&modal=true' );
	}
	
	//password submit
	$("#ep_password_submit").click( function() {
		$("form#ep_password_form").submit();
		return false;
	});
	
	//require password prior to submitting
	$("#publish").click( function( e ) {
	
		if ( $('#ep_toggle').is(':checked') && $('#ep_password').val() == '' ) {
		
			e.preventDefault();
			e.stopPropagation();
			
			alert( encrypt_posts.noPassWarning );
			
			$('#ajax-loading').hide();
			setTimeout( "jQuery('#publish').removeClass('button-primary-disabled')", 1);

			return false;
		}
	});
	
	if ( !$('#ep_toggle').is(':checked') )
		$('#ep_password_div').hide();
	
	$('#ep_toggle').change( function(){
		$('#ep_password_div').toggle('slow');
	});
	
	//disable autosave to prevent storing unencrypted
	autosave = function() {};

});