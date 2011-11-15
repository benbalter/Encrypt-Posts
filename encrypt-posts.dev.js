jQuery( document ).ready( function( $ ){
	
	if ( pagenow == 'post' && encrypt_posts.prompt == 1 ) {
		tb_show( 'Password', '?TB_inline=true&inlineId=ep_password_prompt&modal=true' );
	}
	
	$("#ep_password_submit").click( function() {
		$("form#ep_password_form").submit();
		return false;
	});

});