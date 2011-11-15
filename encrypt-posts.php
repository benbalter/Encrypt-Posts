<?php
/*
Plugin Name: Encrypt Posts
Plugin URI: 
Description: 
Version: 0.1
Author: Benjamin J. Balter
Author URI: http://ben.balter.com/
License: GPL2
*/

class Encrypt_Posts {

	private $crypt;
	private $salt;
	private $encrypts = array();
	
	function __construct() {
		
		$crypt_path = dirname( __file__ ) . '/' . 'class.cryptastic.php';
		
		if ( !file_exists( $crypt_path ) )
			die( 'Can\'t load Cryptasic class. Tried ' . $crypt_path );
			
		require_once( $crypt_path );
		
		$this->crypt = new Cryptastic();
		
		if ( !defined( 'SECURE_AUTH_SALT' ) )
			die( 'Need salts added to wp-config' );
			
		$this->salt = SECURE_AUTH_SALT;	
		
		add_filter( 'content_save_pre', array( &$this, 'pre_save_filter' ), 10, 1 );
		add_filter( 'content_edit_pre', array( &$this, 'pre_content_edit_filter' ), 10, 1 );
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
		add_action( 'admin_head', array( &$this, 'enqueue_js' ) );
		add_action( 'admin_footer', array( &$this, 'password_prompt' ) );
		add_filter( 'the_content', array( &$this, 'content_filter' ) );
	}
	
	function encrypt( $data, $pass = null ) {
	
		//note: base64 encoded to prevent encoding problems in the DB
		$key = $this->get_key( $pass );	
		return $this->crypt->encrypt( $data, $key, true );
	
	}
	
	function decrypt( $data, $pass = null ) {
	
		$key = $this->get_key( $pass );
		return $this->crypt->decrypt( $data, $key, true );			
	
	}
	
	function get_key( $pass = null ) {
		
		if ( $pass == null && isset( $_POST['ep_password'] ) )
			$pass = $_POST['ep_password'];
		
		return $this->crypt->pbkdf2( $pass, $this->salt, 1000, 32 );
	
	}
	
	function pre_save_filter( $content ) {

		global $post;
	
		if ( !isset( $_POST['ep_toggle'] ) || !$_POST['ep_toggle'] )
			return $content;
			
		if ( $_POST['ep_toggle'] && empty( $_POST['ep_password'] ) )
			die( 'Cannot encrypt without password' );
		
		//this filter is actually fired twice… only encrypt once per save
		if ( in_array( $post->ID, $this->encrypts ) )
			return $content;
					
		$content = $this->encrypt( $content, $_POST['ep_password'] );

		if ( !$content )
			die( 'Encryption error' );
			
		add_post_meta( $post->ID, '_encrypt_post', true, true );
		$this->encrypts[] = $post->ID;
		
		return $content;
	}
	
	function add_meta_box( ) {
		add_meta_box( 'encrypt_posts', 'Encryption', array( &$this, 'metabox' ), 'post' );	
		add_meta_box( 'encrypt_posts', 'Encryption', array( &$this, 'metabox' ), 'page' ); 	
	}
	
	function encrypted_post( $postID = null) {
		global $post;
		
		if ( $postID == null )
			$postID = $post->ID;
		
		return get_post_meta( $postID, '_encrypt_post', true );
	}
	
	function metabox( $post ) {
	?>
	<p>
		<label for="ep_toggle">Encrypt?</label> <input type="checkbox" name="ep_toggle" <?php checked( $this->encrypted_post( $post->ID ), true ); ?>/> 
		<label for="ep_password"><?php _e( 'Password', 'encrypt_posts' ); ?></label>: 
		<input type="password" name="ep_password" />
	</p>
	<?php 
	}
	
	function password_prompt() { 
		global $pagenow;
		if ( $pagenow != 'post.php' )
			return;
	?>
	<div id="ep_password_prompt" style="display:none;">
		<p><?php _e( 'This post is encrypted. Please enter the password:', 'encrypt_password' ); ?></p>
		<form method="post" id="ep_password_form">
			<p>
				<label for="ep_password"><?php _e( 'Password', 'encrypt_posts' ); ?></label>: 
				<input type="password" name="ep_password" />
			</p>
			<p><a class="button-primary" id="ep_password_submit" href="#">Decrypt</a></p>
		</form>
	</div><?php
	}
	
	function pre_content_edit_filter( $content ) {
		global $post;
		if ( !$this->encrypted_post( $post->ID ) )
			return $content;
		
		if ( !isset( $_POST['ep_password'] ) ) 
			return $content; //do something here to prompt for pw
			
		$content = $this->decrypt( $content, $_POST['ep_password'] );
		
		//do something here if auth failed
		
		return $content;
		
	}
	
	function enqueue_js() {
	
		$suffix = ( WP_DEBUG ) ? 'dev.' : '';
		wp_enqueue_script( 'encrypt_posts', plugins_url( 'encrypt-posts.' . $suffix . 'js', __FILE__ ), array( 'jquery' ) );
	
		$prompt = ( $this->encrypted_post() && !isset( $_POST['ep_password'] ) );
		 
		$data = array( 
			'encrypted_post' => $this->encrypted_post(),
			'prompt' => $prompt,
		);
		
		wp_localize_script( 'encrypt_posts', 'encrypt_posts', $data ); 
	}
	
	function content_filter( $content ) {
		
		if ( !$this->encrypted_post( $post->ID ) )
			return $content;
			
		if ( !isset( $_POST['ep_password'] ) ) {
			
			$content = '
			<form method="post" id="ep_password_form">
				<p>
					<label for="ep_password">Password</label>: 
					<input type="password" name="ep_password" />
				</p>
				<p><a class="button-primary" id="ep_password_submit" href="#">Decrypt</a></p>
			</form>';
			
			return $content;
		
		}
		
		$content = $this->decrypt( $content, $_POST['ep_password'] );

		//do something here if auth failed
		
		return $content;

	}

}

$encrypt_posts = new Encrypt_Posts();