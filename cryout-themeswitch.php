<?php
/*
Plugin Name: Cryout Theme Switch
Plugin URI: http://www.cryoutcreations.eu/wordpress-plugins/cryout-themeswitch
Description: Quickly and easily swap between themes when developing. Adds a theme switcher to the WordPress Admin Bar with all parent/child themes, favorites list and search support.
Author: Cryout Creations
Author URI: http://www.cryoutcreations.eu/
Version: 1.0.4
License: GPLv3 - http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Cryout_ThemeSwitch {

	private $_version = '1.0.4';
	private $last_url = '';
	private $page_url = 'tools.php?page=cryout-themeswitch';
	private $the_page = NULL;
	private $options = NULL; 			// initialized on run
	
	private $themes_array = array(); 	// themes list, used by both admin menu and options page
	private $themes_list = array();     // theme names list, used to identify duplicate names
	
	private $favorites = array();		// favorite themes list
	private $title = ''; 				// translatable, initialized in the constructor
	private $current_theme = ''; 		// initialized after detection

	function __construct(){

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'actions_links' ), -10 );
		add_filter( 'plugin_row_meta', array( $this, 'meta_links' ), 10, 2 );

		// only activate when the current user has permission to manage themes
		if (! current_user_can('switch_themes') ) return;

		add_action( 'switch_theme', array($this, 'before_theme_switch') );
		add_action( 'after_switch_theme', array($this, 'after_theme_switch'), 9 ); // lower priority to ensure redirect isn't hijacked by theme
		
		$this->title = __('Cryout ThemeSwitch', 'cryout-themeswitch');
		
		$this->get_themes_list();
		$this->options = $this->load_settings();
		if (!empty($this->options['favorites']) && is_array($this->options['favorites'])) $this->favorites = $this->options['favorites'];
		
		add_action( 'admin_bar_menu', array($this, 'admin_menu'), 90 );
		add_action( 'admin_menu', array( $this, 'register_menu_page' ) );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );

		add_action( 'admin_enqueue_scripts', array($this, 'styling'), 10 );
		add_action( 'admin_enqueue_scripts', array($this, 'scripting'), 10 );
		add_action( 'wp_enqueue_scripts', array($this, 'styling'), 10 );
		add_action( 'wp_enqueue_scripts', array($this, 'scripting'), 10 );
		
	} // __construct()
	
	/**
	 * Master themes list retrieval function
	 */
	private function get_themes_list( $include_child = true ) {
		
		$this->current_theme = wp_get_theme();
		
		// themes list
		$this->themes_array = array();
		$count = 0;
		
		$themes = wp_get_themes();

		if ( ! isset( $themes  ) || ! is_array( $themes ) ) { return; }

		foreach ( $themes as $k => $v ) {
			
			// do some extra filtering on multisite
			if ( is_multisite() ) {
				$ms_enabled_themes = get_site_option('allowedthemes');
				if ( empty( $ms_enabled_themes[$v->get_template()] ) ) continue;
			}
			
			$count++;

			$template = $v->get_template();
			$stylesheet = $v->get_stylesheet();
			$screenshot = $v->get_screenshot();

			$data = array( 
				'id' => urlencode( str_replace( '/', '-', strtolower( $stylesheet ) ) ), 
				'name' => $v['Name'], 
				'link' => admin_url( wp_nonce_url( "themes.php?action=activate&amp;template=" . urlencode( $template ) . "&amp;stylesheet=" . urlencode( $stylesheet ) . '&amp;return=/', 'switch-theme_' . $stylesheet) ),
				'template' => $v->get_template(),
				'stylesheet' => $v->get_stylesheet(),
				// 'img' => $screenshot, // TO DO
				// 'type' is set later
				// 'active' is set later
			);
	
			// parent or child
			if ($template == $stylesheet) {
				$data['type'] = 'parent';
			} else {
				$data['type'] = 'child'; 
			}
			
			// is active theme?
			if ( $stylesheet == $this->current_theme->get_stylesheet() ) { $data['active'] = true; }

			// skip child themes when not requested
			if ($data['type'] == 'child' && !($include_child)) continue;
			
			// add the theme to the lists
			$this->themes_array[] = $data;
			$this->themes_list[] = $data['name'];

		} // foreach 
		
		// sort themes in alphabetical order
		asort( $this->themes_list, SORT_NATURAL );
		uksort( $this->themes_array, array( $this, 'cmp') );
		
		// check for duplicates in themes list
		$this->uniquely_identify();
		
	} // get_themes_list()
	
	
	/*** SETTINGS MANAGEMENT ***/

	/**
	 * Register the plugin's settings
	 */
	public function register_settings() {
		register_setting( 'cryout_themeswitch_group', 'cryout_favorite_themes' );
	} // register_settings()
	
	/**
	 * Load plugin options
	 */ 
	public function load_settings() {
		$options = get_option('cryout_themeswitch');
		$options = wp_parse_args( (array)$options, array() );
		return $options;
	} // load_settings()
	
	/**
	 * Save plugin options
	 */ 
	public function save_settings() {
		if ( isset( $_POST['settings_submit'] ) && check_admin_referer( 'cryout_themeswitch', '_wpnonce' ) ):
			if (! current_user_can( 'manage_options' ) ) wp_die( __('You do not have the necessary permissions to perform this action.', 'cryout-themeswitch') );
			$saved_options = $_POST['cryout_themeswitch'];

			foreach ($saved_options as $option => $value):
				$saved_options[$option] = array_map( 'esc_attr', $value );;
			endforeach;

			update_option( 'cryout_themeswitch', $saved_options );
			wp_redirect( admin_url( $this->page_url . '&updated=true') );
		endif;
	} // save_settings()
	
	
	/*** PLUGIN PAGES AND MENUS ***/
	
	/**
	 * Register the plugin's page.
	 */
	public function register_menu_page() {
		$this->the_page = add_submenu_page( 
			'tools.php', 
			$this->title, 
			$this->title,
			'manage_options', 
			'cryout-themeswitch', 
			array( &$this, 'main_page' ) 
		);
	} // register_menu_page()

	/**
	 * Add the theme switcher menu to the WordPress Toolbar.
	 */
	function admin_menu() {
		global $wp_admin_bar;

		if ( ! current_user_can( 'switch_themes' ) ) { return; }
		if ( empty( $this->themes_array ) ) { return; }

		$child_themes = array();
		$parent_themes = array();

		$menu_label = $this->current_theme->display( 'Name' );

		$menu_label_ex = '<span class="ab-icon dashicons-admin-appearance"></span><span class="ab-label">' .
						sprintf( __( 'Theme: %s','cryout-themeswitch' ) , '<strong>' . $menu_label . '</strong>' ) . '</span>';

		$has_child_themes = false;

		$menu_id = 'cryout-themeswitch';

		foreach ( $this->themes_array as $k => $v ) {
			if ( $v['template'] != $v['stylesheet'] ) {
				$child_themes[] = $v;
			} else {
				$parent_themes[] = $v;
			}
		}

		// Main Menu Item
		$wp_admin_bar->add_node( array(
			'id'    => $menu_id,
			'title' => $menu_label_ex,
			'href'  => admin_url('themes.php'),
		));
		
		// Favorite themes placeholder
		$wp_admin_bar->add_node( array(
			'id'    => 'heading-favorite-themes',
			'parent'    => $menu_id,
			'title' => __( 'Favorites', 'cryout-themeswitch' ),
			'href'  => '',
			'meta'  => array( 'tabindex' => 0 ),
		));	
		if ( empty($this->favorites) ) {
			$wp_admin_bar->add_node( array(
				'id'    => 'define-favs-link',
				'parent'    => 'heading-favorite-themes',
				'title' => __( 'Choose favorite themes...', 'cryout-themeswitch' ),
				'href'  => $this->page_url,
			));				
		}

		// Parent themes placeholder
		$wp_admin_bar->add_node( array(
			'id'    => 'heading-parent-themes',
			'parent'    => $menu_id,
			'title' => __( 'Parent Themes', 'cryout-themeswitch' ),
			'href'  => '',
			'meta'  => array( 'tabindex' => 1 ),
		));

		if ( count( $child_themes ) > 0 ) {
			$has_child_themes = true;
		}

		// Child themes placeholder
		if ( $has_child_themes ) {
			// child themes placeholder
			$wp_admin_bar->add_node( array(
				'id'    => 'heading-child-themes',
				'parent'    => $menu_id,
				'title' => __( 'Child Themes', 'cryout-themeswitch' ),
				'href'  => '',
				'meta'  => array( 'tabindex' => 2 ),
			));
		}

		$themes = array_merge( $child_themes, $parent_themes );
		
		// Process all themes and add them to the appropriate menus
		foreach ($this->themes_array as $thm) {
			if ( 'parent' != $thm['type'] ) {
					$thm['name'] = sprintf( '%s <span class="parent">(%s)</span>', $thm['name'], $thm['template'] );
			}
			
			// Favorites
			if ( !empty($this->favorites) && in_array($thm['id'], $this->favorites) ) {
				$classes = array();
				if ( !empty($thm['active']) ) $classes[] = 'active-theme';
				if ( 'parent' != $thm['type'] ) $classes[] = 'child-theme'; 
				$wp_admin_bar->add_node( array(
					'id'    => 'fav_' . $thm['id'],
					'parent'  => 'heading-favorite-themes',
					'title' => $thm['name'],
					'href'  => $thm['link'],
					'meta'  => array( 'class'=>implode( '', $classes ) ),
				));				
			}
			
			// Parent/Child lists
			$wp_admin_bar->add_node( array(
				'id'    => $thm['id'],
				'parent'  => ($thm['type']=='parent'?'heading-parent-themes':'heading-child-themes'),
				'title' => $thm['name'],
				'href'  => $thm['link'],
				'meta'  => (!empty($thm['active'])?array('class'=>'active-theme'):array()),
			));
			
			// Searchable list
			$wp_admin_bar->add_node( array(
				'id'    => 'list_'.$thm['id'],
				'parent'  => $menu_id,
				'title' => $thm['name'],
				'href'  => $thm['link'],
				'meta'  => array('class'=>'the_list hide-theme' . (!empty($thm['active'])?' active-theme':'') ),
			));
			
		}
	} // admin_menu()
	
	/**
	 * Generates the plugin's main settings page
	 */
	public function main_page() { ?>
		
		<style>
			input[type='text'], textarea, select {
				min-width: 200px;
				width: 80%;
				max-width: 600px;
			}
			input.short, select.short { width: 50px; }
		</style>	
		<div class="wrap" id="cryout-plugin-page">
			<h2><?php echo $this->title ?></h2>
						
			<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
				<form method="post">
				
					<?php settings_fields( 'cryout_themeswitch_group' ); ?>
					<div class="postbox">
						<div class="inner" style="padding: 1em 2em;">
							<h3><?php _e( 'Favorite Themes', 'cryout-themeswitch' ); ?></h3>
							<p><?php _e( 'Select your favorite themes from the list below to have them always displayed in a dedicated submenu.' , 'cryout-themeswitch' ) ?></p>
							<select multiple size="20" name="cryout_themeswitch[favorites][]" id="cryout_themeswitch[favorites]">
								<?php foreach ($this->themes_array as $thm) { 
									if ( 'parent' != $thm['type'] ) {
										$thm['name'] = sprintf( '%s * <span class="parent">(%s)</span>', $thm['name'], $thm['template'] );
									} ?>
									<option value="<?php echo $thm['id'] ?>" <?php $this->selected( $thm['id'], $this->favorites ) ?>> <?php echo $thm['name'] ?> </option>
								<?php } ?>
							</select>
							<p><em><?php _e( 'Child themes are indicated with an asterisk and their parent theme name. Multiple themes using the same slug are separated by the inclusion of the folder names.' , 'cryout-themeswitch' ) ?></em></p>
							<p class="submit">
								<?php wp_nonce_field( 'cryout_themeswitch' ); ?>
								<input type="submit" class="button-primary" name="settings_submit" value="<?php _e('Save','cryout-themeswitch') ?>" />
							</p>
						</div> 
					</div>			

				</form>
				</div> <!-- post-body-content-->

				<div id="postbox-container-1" class="postbox-container">

							<div class="meta-box-sortables">

								<div class="postbox">
									<h3 style="text-align: center;" class="hndle">
										<span><strong><?php _e( 'Cryout ThemeSwitch', 'cryout-themeswitch') ?></strong></span>
									</h3>

									<div class="inside">
										<div style="text-align: center; margin: auto">
											<strong><?php printf( __('version: %s','cryout-themeswitch'), $this->_version ); ?></strong><br>
											<?php _e('by','cryout-themeswitch') ?> <a target="_blank" href="https://www.cryoutcreations.eu/">Cryout Creations</a>
										</div>
									</div>
								</div>

								<div class="postbox">
									<h3 style="text-align: center;" class="hndle">
										<span><?php _e('Support','cryout-themeswitch') ?></span>
									</h3><div class="inside">
										<div style="text-align: center; margin: auto">
											<?php printf ( '%1$s <a target="_blank" href="https://www.cryoutcreations.eu/forums/f/wordpress/plugins/themeswitch">%2$s</a>.',
												__('For support questions please use', 'cryout-themeswitch'),
												__('our forum', 'cryout-themeswitch')
												);
											?>
										</div>
									</div>
								</div>
								
							</div>
				</div> <!-- postbox-container -->

			</div> <!-- post-body -->
			<br class="clear">
			</div> <!-- poststuff -->

		</div><!--end wrap-->
	
	<?php
	} // main_page()
	
	
	/*** SWITCHING HELPERS ***/
	
	/**
	 * Saves the current browsed location before switch
	 */
	function before_theme_switch() {
		$url = esc_url_raw($_SERVER['HTTP_REFERER']);
		$url = parse_url($url);
		//global $pagenow, $menu, $submenu, $_wp_menu_nopriv, $_wp_submenu_nopriv, $plugin_page, $_registered_pages;

		if (!empty($url['path'])) set_transient( 'cryout_themeswitch_lasturl', $url['path'] . ( !empty($url['query']) ? '?' . $url['query'] : '' ), 60 );
	} // before_theme_switch()

	/**
	 * Redirects to the saved browsed location
	 */	
	function after_theme_switch() {
		if ( ( false !== ( $last_url = get_transient( 'cryout_themeswitch_lasturl' ) ) ) && !empty($last_url) ) {
			delete_transient( 'cryout_themeswitch_lasturl' );
			$home_url = parse_url( home_url() );
			if (empty($home_url['path'])) $home_url['path'] = $home_url['host']; // failsafe to redirect to home in case current path is empty
			$path = preg_quote( $home_url['path'], '/' ); // prepare $path for regex
			$last_url = preg_replace( "/^($path)/", "", $last_url ); // clean up the subfolder from the url path
			wp_redirect( home_url( $last_url ) );
		}
	} // after_theme_switch()
	
	
	/*** STYLES AND SCRIPTS ***/

	/**
	 * Load CSS for the plugin.
	 */
	function styling() {
		if ( ! current_user_can( 'switch_themes' ) ) { return; }

		$plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		wp_register_style( 'cryout-themeswitch', $plugin_url . 'resources/style.css', 'screen', $this->_version );
		wp_enqueue_style( 'cryout-themeswitch' );
	} // styling()

	/**
	 * Load JavaScript for the plugin.
	 */
	function scripting() {
		if ( ! current_user_can( 'switch_themes' ) ) { return; }

		$plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		wp_register_script( 'cryout-themeswitch', $plugin_url . 'resources/code.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( 'cryout-themeswitch' );
	} // scripting()
	
	
	/*** META LINKS ***/
	
	/**
	 * Action Links
	 */
	public function actions_links( $links ) {
		array_unshift( $links, '<a href="' . admin_url( $this->page_url ) . '">' . __( 'Settings', 'cryout-themeswitch' ) . '</a>' );
		return $links;
	} // actions_links()

	/**
	 * Meta Links
	 */
	public function meta_links( $links, $file ) {
		// Check plugin
		if ( $file === plugin_basename( __FILE__ ) ) {
			array_splice( $links, 2, 0 , '<a href="https://www.cryoutcreations.eu/wordpress-plugins/cryout-themeswitch" target="_blank">' . __( 'Visit plugin site', 'cryout-themeswitch' ) . '</a>' );
		}
		return $links;
	} // meta_links()
	
	
	/*** HELPER FUNCTIONS ***/
	
	/**
	 * Checks and outputs 'selected' attribute
	 */
	private function selected( $needle, $haystack, $echo = true ) {
		if ( in_array( $needle, $haystack ) ) 
			$value = 'selected="selected"';
		else 
			$value = '';
		
		if ($echo) echo $value;
			else return $value;
	} // selected()
	
	/**
	 * Checks for and processes themes array for duplicate names
	 */
	private function uniquely_identify() {
		$total = count( $this->themes_array );
		$unique = count( array_unique( $this->themes_list ) );
		if ($total != $unique) {
			// there are themes with duplicate names, process list and add id
			$counters = array_count_values( $this->themes_list );
			foreach ($counters as $theme_name => $count) {
				if ($count>1) {
					foreach( $this->themes_array as &$thm ) {
						if ($thm['name'] == $theme_name) $thm['name'] .= ' &ndash;' . $thm['id'] . '';
					} // foreach
				} // if count
			} // foreach
		} // if
	} // uniquely_identify()
	
	function cmp($a, $b) {
		
		$order = array_values( $this->themes_list );

		$posA = $order[$a];
		$posB = $order[$b];

		/* if ($posA == $posB) {
			return 0;
		}
		return ($posA < $posB) ? -1 : 1; */
			
		return strnatcmp( $posA, $posB );
	}

} // class Cryout_ThemeSwitch

function cryout_themeswitch_init(){
	new Cryout_ThemeSwitch;
}
add_action( 'init', 'cryout_themeswitch_init' );

// FIN
