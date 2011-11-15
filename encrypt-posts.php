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

		//load cryptography class and init			
		$this->init_cryptography();
		
		add_filter( 'content_save_pre', array( &$this, 'pre_save_filter' ), 100, 1 );
		add_filter( 'content_edit_pre', array( &$this, 'pre_content_edit_filter' ), 1, 1 );
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
		add_action( 'admin_head', array( &$this, 'enqueue_js' ) );
		add_action( 'admin_footer', array( &$this, 'password_prompt' ) );
		add_filter( 'the_content', array( &$this, 'content_filter' ), 1 );
	}
	
	/**
	 * Loads cryptography class and init's; sets up salt
	 */
	function init_cryptography() {
		
		$crypt_path = dirname( __file__ ) . '/' . 'class.cryptastic.php';

		if ( !file_exists( $crypt_path ) )
			die( 'Can\'t load Cryptasic class. Tried ' . $crypt_path );
			
		require_once( $crypt_path );
		
		$this->crypt = new Cryptastic();
		
		if ( !defined( 'SECURE_AUTH_SALT' ) )
			die( 'Need salts added to wp-config' );
			
		$this->salt = SECURE_AUTH_SALT;	
	}
	
	/**
	 * Encrypt a string
	 * @param string $data the string to encrypt
	 * @param string $pass the password
	 * @return string the encrypted string
	 */
	function encrypt( $data, $pass = null ) {
	
		//note: base64 encoded to prevent encoding problems in the DB
		$key = $this->get_key( $pass );	
		return $this->crypt->encrypt( $data, $key, true );
	
	}
	
	/**
	 * Decrypt a string
	 * @param string $data encrypted string
	 * @param string $pass the password
	 * @return string|bool unencrypted string or false on failure
	 */
	function decrypt( $data, $pass = null ) {
	
		$key = $this->get_key( $pass );
		return $this->crypt->decrypt( $data, $key, true );			
	
	}
	
	/** 
	 * Builds a key from a password
	 * @param string $pass the password
	 * @return string the key
	 */
	function get_key( $pass = null ) {
		
		if ( $pass == null && isset( $_POST['ep_password'] ) )
			$pass = $_POST['ep_password'];
		
		return $this->crypt->pbkdf2( $pass, $this->salt, 1000, 32 );
	
	}
	
	/**
	 * Encrypts post prior to hitting database
	 * @param string $content the raw content
	 * @return string the encrypted content
	 */
	function pre_save_filter( $content ) {

		global $post;
	
		//verify user wants encryption, otherwise remove flag
		if ( !isset( $_POST['ep_toggle'] ) || !$_POST['ep_toggle'] ) {
			delete_post_meta( $post->ID, '_encrypt_post' );
			return $content;
		}
		
		//make sure we have a password
		if ( $_POST['ep_toggle'] && empty( $_POST['ep_password'] ) )
			die( 'Cannot encrypt without password' );
		
		//this filter is actually fired twice… only encrypt once per save
		if ( in_array( $post->ID, $this->encrypts ) )
			return $content;
					
		$content = $this->encrypt( $content, $_POST['ep_password'] );

		if ( !$content )
			die( 'Encryption error' );
			
		//set flags
		add_post_meta( $post->ID, '_encrypt_post', true, true );
		$this->encrypts[] = $post->ID;
		
		return $content;
	}
	
	/**
	 * Register metaboxes
	 */
	function add_meta_box( ) {
	
		foreach ( array( 'post', 'page')  as $post_type )
			add_meta_box( 'encrypt_posts', 'Encryption', array( &$this, 'metabox' ), $post_type, 'side', 'high' );	
	
	}
	
	/**
	 * Checks whether a given post is encrypted
	 * @param int $postID the post to check
	 * @return bool true if encrypted
	 */
	function encrypted_post( $postID = null) {
		global $post;
		
		if ( $postID == null )
			$postID = $post->ID;
		
		return get_post_meta( $postID, '_encrypt_post', true );
	}
	
	/**
	 * Callback to generate encryption metabox
	 */
	function metabox( $post ) {
	?>
	<p>
		<label for="ep_toggle">Encrypt?</label> <input type="checkbox" name="ep_toggle" id="ep_toggle" <?php checked( $this->encrypted_post( $post->ID ), true ); ?>/> 
		<div id="ep_password_div">
			<label for="ep_password"><?php _e( 'Password', 'encrypt_posts' ); ?></label>: 
			<input type="password" name="ep_password" id="ep_password" />
		</div>
	</p>
	<?php 
	}
	
	/**
	 * Callback to generate password prompt
	 */
	function password_prompt() { 
		global $pagenow;
		global $post;
		
		//get the post from the DB so we have the unencrypted content
		$original_post = get_post( $post->ID );
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
	</div>
	<input id="original_post_title" type="hidden" value="<?php echo esc_attr( $original_post->post_title ); ?>" ?>
	<input id="original_post_content" type="hidden" value="<?php echo esc_attr( $original_post->post_content ); ?>" ?>
	<?php
	}
	
	/**
	 * Decrypt post prior to editing
	 */
	function pre_content_edit_filter( $content ) {
	
		global $post;
		if ( !$this->encrypted_post( $post->ID ) )
			return $content;
		
		if ( !isset( $_POST['ep_password'] ) ) 
			return $content; //do something here to prompt for pw
			
		if ( $decrypted = $this->decrypt( $content, $_POST['ep_password'] ) );
			return $decrypted;
		
		//pass did not work, pretend it never happened
		unset( $_POST['ep_password'] );
		
		return $content;
		
	}
	
	/**
	 * Register JavaScript file
	 */
	function enqueue_js() {
	
		$suffix = ( WP_DEBUG ) ? 'dev.' : '';
		wp_enqueue_script( 'encrypt_posts', plugins_url( 'encrypt-posts.' . $suffix . 'js', __FILE__ ), array( 'jquery' ) );
	
		$prompt = ( $this->encrypted_post() && !isset( $_POST['ep_password'] ) );
		 
		$data = array( 
			'encrypted_post' => $this->encrypted_post(),
			'prompt' => $prompt,
			'noPassWarning' => __( 'If you would like to encrypt the post, you must enter a password', 'encrypt_posts' ),
		);
		
		wp_localize_script( 'encrypt_posts', 'encrypt_posts', $data ); 
	}
	
	/**
	 * front-end content filter
	 */
	function content_filter( $content ) {
		global $post;
		
		if ( !$this->encrypted_post( $post->ID ) )
			return $content;
			
		if ( isset( $_POST['ep_password'] ) ) {
		
			if ( $decrypted = $this->decrypt( $post->post_content, $_POST['ep_password'] ) ) 			
				return $decrypted;			
		
		}
g		
		//pass did not work, pretend it never happened
		unset( $_POST['ep_password'] );
		
		$content = '
		<form method="post" id="ep_password_form">
		    <p>
		    	<label for="ep_password">Password</label>: 
		    	<input type="password" name="ep_password" />
		    </p>
		    <p><input type="submit" id="ep_password_submit" value="Decrypt" /></p>
		</form>';
		
		return $content;

	}

}

$encrypt_posts = new Encrypt_Posts();