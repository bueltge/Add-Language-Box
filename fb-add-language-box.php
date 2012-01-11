<?php
/**
 * @package Add Language Box
 * @author Frank B&uuml;ltge
 */

/**
 * Plugin Name:   Add Language Title and Content
 * Plugin URI:    http://bueltge.de/
 * Text Domain:   add-language-box
 * Domain Path:   /languages
 * Description:   Add language meta box for title and content to posts and pages
 * Author:        Frank Bültge
 * Version:       0.0.4
 * License:       GPLv3
 * Author URI:    http://bueltge.de
 * Upgrade Check: none
 */

/**
 * Usage
 * 
 * use template tag: get_language_facts() for outside the strings
 * get_language_facts( 'title' ); - echo the title of additional area
 * 
 * @param  $type    String  - title, content  default is content
 * @param  $post_id Integer - Post_ID
 * @param  $values  String
 * @param  $args    Array   - echo, before, after
 * @return $return  String
 * the default: get_language_facts( $type = 'content', $post_id = FALSE, $values = FALSE, $args = array() )
 */

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( ! class_exists( 'Fb_Add_Language_Box' ) ) {
	
	class Fb_Add_Language_Box {
		
		static private $classobj = NULL;
		
		// constructor
		public function __construct() {
			
			if ( ! is_admin() )
				return;
			
			add_action( 'admin_init', array( $this, 'on_admin_init' ) );
			add_action( 'save_post',  array( $this, 'on_wp_insert_post' ) );
			add_action( 'init',       array( $this, 'load_textdomain' ) );
			register_uninstall_hook( __FILE__ , array( 'Fb_Add_Language_Box', 'uninstall' ) );
			
			add_action( 'admin_print_scripts-post.php',     array( $this, 'enqueue_script' ) );
			add_action( 'admin_print_scripts-post-new.php', array( $this, 'enqueue_script' ) );
			add_action( 'admin_print_scripts-page.php',     array( $this, 'enqueue_script' ) );
			add_action( 'admin_print_scripts-page-new.php', array( $this, 'enqueue_script' ) );
			
			add_action( 'admin_print_styles-post.php',      array( $this, 'enqueue_style' ) );
			add_action( 'admin_print_styles-post-new.php',  array( $this, 'enqueue_style' ) );
			add_action( 'admin_print_styles-page.php',      array( $this, 'enqueue_style' ) );
			add_action( 'admin_print_styles-page-new.php',  array( $this, 'enqueue_style' ) );
		}
		
		/**
		 * Handler for the action 'init'. Instantiates this class.
		 * 
		 * @since   0.0.4
		 * @return  object $classobj
		 */
		public function get_object() {

			if ( NULL === self :: $classobj ) {
				self :: $classobj = new self;
			}

			return self :: $classobj;
		}
		
		/**
		 * return plugin comment data
		 * 
		 * @uses   get_plugin_data
		 * @access public
		 * @since  0.0.1
		 * @param  $value string, default = 'Version'
		 *         Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
		 * @return string
		 */
		private static function get_plugin_data( $value = 'Version' ) {
			
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			
			$plugin_data  = get_plugin_data ( __FILE__ );
			$plugin_value = $plugin_data[$value];
			
			return $plugin_value;
		}
		
		/**
		 * Return string for translation
		 * 
		 * @since  0.0.1
		 * @return string translation string from plugin comments on top
		 */
		public static function get_textdomain() {
			
			return self ::  get_plugin_data( 'TextDomain' );
		}
		
		/**
		 * Active for multilanguage, load translation files, if WPLANG was not empty
		 * 
		 * @since   0.0.1
		 * @return  void
		 */
		public function load_textdomain() {
			
			if ( function_exists('load_plugin_textdomain') )
				load_plugin_textdomain( 
					self ::  get_textdomain(), 
					FALSE, 
					dirname( plugin_basename(__FILE__) ) . '/languages'
				);
		}
		
		/**
		 *  Unsintall all postmetadata, if plugin was uninstalled via WP backend
		 * 
		 * @since   0.0.1
		 * @return  void
		 */
		public function uninstall() {
			
			$all_posts = get_posts( 'numberposts=0&post_type=post&post_status=' );
			
			foreach( $all_posts as $postinfo ) {
				delete_post_meta( $postinfo -> ID, '_fb_language_data' );
			}
		}
		
		/**
		 * Add script for init TinyMCE
		 * 
		 * @since   0.0.1
		 * @return  void
		 */
		public function enqueue_script() {
			
			wp_enqueue_script( 
				'tinymce4langbox', 
				WP_PLUGIN_URL . '/' . dirname( plugin_basename(__FILE__) ) . '/js/script.js', 
				array( 'jquery', 'post' )
			);
		}
		
		/**
		 * Add stylesheet for custom TinyMCE
		 * 
		 * @since   0.0.1
		 * @return  void
		 */
		public function enqueue_style() {
			
			wp_enqueue_style( 
				'tinymce4langbox', 
				WP_PLUGIN_URL . '/' . dirname( plugin_basename(__FILE__) ) . '/css/style.css'
			);
		}
		
		/**
		 * On admin init; add meta boxes to posts and page
		 * 
		 * @since   0.0.1
		 * @return  void
		 */
		public function on_admin_init() {
			// checj capabilities
			if ( ! current_user_can( 'publish_posts' ) )
				return;
			// add meta box for posts
			add_meta_box( 'language_box',
				__( 'English Content', self :: get_textdomain() ),
				array( &$this, 'meta_box' ),
				'post', 'normal', 'high'
			);
			// add meta box for pages
			add_meta_box( 'language_box',
				__( 'English Content', self :: get_textdomain() ),
				array( &$this, 'meta_box' ),
				'page', 'normal', 'high'
			);
		}
		
		/**
		 * Check for preview of post, page
		 * 
		 * @since   0.0.1
		 * @return  boolean true, if is preview
		 */
		public function is_page_preview() {
			
			if ( isset($_GET['preview_id']) )
				$post_id = (int)$_GET['preview_id'];
			if ( isset($post_id) && 0 == $post_id)
				$post_id = (int)$_GET['post_id'];
			if ( isset($_GET['preview']) )
				$preview = $_GET['preview'];
			if ( isset($post_id) && $post_id > 0 && $preview == 'true') {
				global $wpdb;
				$type = $wpdb -> get_results("SELECT post_type FROM $wpdb->posts WHERE ID = $post_id");
				if ( count($type) && ($type[0] -> post_type == 'page') && current_user_can('edit_page') )
					return TRUE;
			}
			
			return FALSE;
		}
		
		/**
		 * After save post, save meta data for plugin
		 * 
		 * @since   0.0.1
		 * @param   $post_id  integer  post ID
		 * @return  void
		 */
		public function on_wp_insert_post( $post_id ) {
			
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				return;
			
			if ( ! isset($_POST['fb_language_box_nonce']) || 
				 ! wp_verify_nonce( $_POST['fb_language_box_nonce'], plugin_basename( __FILE__ ) )
				)
				return;
			
			if ( ! isset($post_id) && isset($_REQUEST['post_ID']) )
				$post_id = (int) $_REQUEST['post_ID'];
			if ( $this -> is_page_preview() && ! isset($post_id) )
				$post_id = (int) $_GET['preview_id'];
			
			// Check permissions
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) )
					return;
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) )
					return;
			}
			
			if ( isset($_POST['fb-language-box']) )
				$this->data['language-box'] = $_POST['fb-language-box'];
			if ( isset($_POST['fb-language-title']) )
				$this->data['language-title'] = $_POST['fb-language-title'];
			
			if ( $this->data )
				update_post_meta( $post_id, '_fb_language_data', $this->data );
			// delete if no values
			if ( empty($_POST['fb-language-box']) && empty($_POST['fb-language-title']) )
				delete_post_meta( $post_id, '_fb_language_data' );
		}
		
		/**
		 * Load post_meta_data
		 * 
		 * @since   0.0.1
		 * @param   $post_id  integer  post ID
		 * @return  array
		 */
		public function load_post_meta ( $post_id ) {
			
			return get_post_meta( $post_id, '_fb_language_data', TRUE );
		}
		
		/**
		 * Get meta box for post/page
		 * 
		 * @since   0.0.1
		 * @param   array $data
		 * @return  void
		 */
		public function meta_box ( $data ) {
			
			wp_nonce_field( plugin_basename( __FILE__ ), 'fb_language_box_nonce' );
			
			$value = $this -> load_post_meta( $data->ID );
			?>
			<input name="fb-language-title" id="fb-language-title" type="text" value="<?php if ( isset($value['language-title']) ) echo apply_filters( 'the_title', $value['language-title'] ); ?>" autocomplete="off" tabindex="3" />
			<br />
			<p align="right">
				<a class="button toggleVisual">Visual</a>
				<a class="button toggleHTML">HTML</a>
			</p>
			<textarea cols="16" rows="5" id="fb-language-box" name="fb-language-box" 
			class="language-box form-input-tip" size="20" autocomplete="off" 
			style="width:100%" tabindex="4" />
			<?php if ( isset($value['language-box']) ) echo apply_filters( 'the_content', $value['language-box'] ); ?>
			</textarea>
			<?php
		}

		/**
		 * Get the values of postmeta data
		 * 
		 * @param  $type    String  - title, content  default is content
		 * @param  $post_id Integer - Post_ID
		 * @param  $values  String
		 * @param  $args    Array   - echo, before, after
		 * @return $return  String
		 */
		public function get_language_facts( $type, $post_id, $values, $args = array() ) {
			
			if ( ! $post_id )
				return;
			if ( ! $type )
				return;
			
			$values = self :: load_post_meta($post_id);
			if ( ! $values)
				return;
			$return = NULL;
			
			if ( ! $args )
				$args = array( 'echo' => TRUE, 'before' => FALSE, 'after' => FALSE );
			
			if ( 'title' == $type && '' != $values['language-title'] )
				$return = $args['before'] . apply_filters( 'the_title', $values['language-title'] ) . $args['after'];
			if ( 'content' == $type && '' != $values['language-box'] )
				$return = $args['before'] . apply_filters( 'the_content', $values['language-box'] ) . $args['after'];
			
			if ( $args['echo'] && ! empty($return) )
				echo $return;
			elseif ( ! empty($return) )
				return $return;
		}
		
	} // End class
	
	// instance class in WP
	add_action( 'plugins_loaded', array( 'fb_add_language_box', 'get_object' ) );
	
	/**
	 * Get the values of postmeta data outside the class as template tag
	 * 
	 * @param  $type    String  - title, content  default is content
	 * @param  $post_id Integer - Post_ID
	 * @param  $values  String
	 * @param  $args    Array   - echo, before, after
	 * @return $return  String
	 */
	function get_language_facts( $type = 'content', $post_id = FALSE, $values = FALSE, $args = array() ) {
		
		if ( ! $post_id )
			$post_id = get_the_ID();
		
		if ( $post_id )
			Fb_Add_Language_Box :: get_language_facts( $type, $post_id, $values, $args );
		
	}
	
} // End if class exists statement
