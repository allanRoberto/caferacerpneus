<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wordpress.org/plugins/fg-magento-to-woocommerce/
 * @since      1.0.0
 *
 * @package    FG_Magento_to_WooCommerce
 * @subpackage FG_Magento_to_WooCommerce/admin
 */

if ( !class_exists('FG_Magento_to_WooCommerce_Admin', false) ) {

	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * @package    FG_Magento_to_WooCommerce
	 * @subpackage FG_Magento_to_WooCommerce/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Magento_to_WooCommerce_Admin extends WP_Importer {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;

		public $plugin_options;					// Plug-in options
		public $default_language = 0;			// Default language ID
		public $progressbar;
		public $imported_categories = array();	// Imported product categories
		public $chunks_size = 10;
		public $media_count = 0;				// Number of imported medias
		public $magento_version = '';			// Magento DB version
		public $product_types = array();
		public $global_tax_rate = 0;
		public $attribute_types = array();
		public $store_id = 0;
		
		protected $faq_url;						// URL of the FAQ page
		protected $post_type = 'post';			// post or page
		protected $notices = array();		// Error or success messages
		
		private $log_file;
		private $log_file_url;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param    string    $plugin_name       The name of this plugin.
		 * @param    string    $version           The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version = $version;
			$this->faq_url = 'https://wordpress.org/plugins/fg-magento-to-woocommerce/faq/';
			$upload_dir = wp_upload_dir();
			$this->log_file = $upload_dir['basedir'] . '/' . $this->plugin_name . '.log';
			$this->log_file_url = $upload_dir['baseurl'] . '/' . $this->plugin_name . '.log';

			// Progress bar
			$this->progressbar = new FG_Magento_to_Woocommerce_ProgressBar($this);

		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fg-magento-to-woocommerce-admin.css', array(), $this->version, 'all' );

		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fg-magento-to-woocommerce-admin.js', array( 'jquery', 'jquery-ui-progressbar' ), $this->version, false );
			wp_localize_script( $this->plugin_name, 'objectL10n', array(
				'delete_new_posts_confirmation_message' => __( 'All new imported posts or pages and their comments will be deleted from WordPress.', 'fg-magento-to-woocommerce' ),
				'delete_all_confirmation_message' => __( 'All content will be deleted from WordPress.', 'fg-magento-to-woocommerce' ),
				'delete_no_answer_message' => __( 'Please select a remove option.', 'fg-magento-to-woocommerce' ),
				'import_complete' => __( 'IMPORT COMPLETE', 'fg-magento-to-woocommerce' ),
				'content_removed_from_wordpress' => __( 'Content removed from WordPress', 'fg-magento-to-woocommerce' ),
				'settings_saved' => __( 'Settings saved', 'fg-magento-to-woocommerce' ),
				'importing' => __( 'Importing…', 'fg-magento-to-woocommerce' ),
				'import_stopped_by_user' => __( 'IMPORT STOPPED BY USER', 'fg-magento-to-woocommerce' ),
			) );
			wp_localize_script( $this->plugin_name, 'objectPlugin', array(
				'log_file_url' => $this->log_file_url,
				'progress_url' => $this->progressbar->get_url(),
			));

		}

		/**
		 * Initialize the plugin
		 */
		public function init() {
			register_importer('fgm2wc', __('Magento', 'fg-magento-to-woocommerce'), __('Import Magento e-commerce solution to WooCommerce', 'fg-magento-to-woocommerce'), array($this, 'importer'));
		}

		/**
		 * Display the stored notices
		 * 
		 * @since 2.0.0
		 */
		public function display_notices() {
			foreach ( $this->notices as $notice ) {
				echo '<div class="' . $notice['level'] . '"><p>[' . $this->plugin_name . '] ' . $notice['message'] . "</p></div>\n";
			}
		}
		
		/**
		 * Write a message in the log file
		 * 
		 * @since 2.0.0
		 * 
		 * @param string $message
		 */
		public function log($message) {
			file_put_contents($this->log_file, "$message\n", FILE_APPEND);
		}
		
		/**
		 * Store an admin notice
		 */
		public function display_admin_notice( $message )	{
			$this->notices[] = array('level' => 'updated', 'message' => $message);
			error_log('[INFO] [' . $this->plugin_name . '] ' . $message);
			$this->log($message);
		}

		/**
		 * Store an admin error
		 */
		public function display_admin_error( $message )	{
			$this->notices[] = array('level' => 'error', 'message' => $message);
			error_log('[ERROR] [' . $this->plugin_name . '] ' . $message);
			$this->log('[ERROR] ' . $message);
		}

		/**
		 * Run the importer
		 *
		 * @since    2.0.0
		 */
		public function importer() {
			$feasible_actions = array(
				'empty',
				'save',
				'test_database',
				'import',
			);
			$action = '';
			foreach ( $feasible_actions as $potential_action ) {
				if ( isset($_POST[$potential_action]) ) {
					$action = $potential_action;
					break;
				}
			}
			$this->dispatch($action);
			$this->display_admin_page(); // Display the admin page
		}
		
		/**
		 * Import triggered by AJAX
		 *
		 * @since    2.0.0
		 */
		public function ajax_importer() {
			$action = filter_input(INPUT_POST, 'plugin_action', FILTER_SANITIZE_STRING);
			
			if ( $action == 'update_wordpress_info') {
				// Update the WordPress database info
				echo $this->get_database_info();
				
			} else {
				ini_set('display_errors', true); // Display the errors that may happen (ex: Allowed memory size exhausted)
				
				// Empty the log file if we empty the WordPress content
				if ( ($action == 'empty') || (($action == 'import') && filter_input(INPUT_POST, 'automatic_empty', FILTER_VALIDATE_BOOLEAN)) ) {
					file_put_contents($this->log_file, '');
				}

				$time_start = date('Y-m-d H:i:s');
				$this->display_admin_notice("=== START $action $time_start ===");
				$result = $this->dispatch($action);
				if ( !empty($result) ) {
					echo json_encode($result); // Send the result to the AJAX caller
				}
				$time_end = date('Y-m-d H:i:s');
				$this->display_admin_notice("=== END $action $time_end ===\n");
			}
			wp_die();
		}
		
		/**
		 * Dispatch the actions
		 * 
		 * @param string $action Action
		 * @return object Result to return to the caller
		 */
		public function dispatch($action) {
			set_time_limit(7200); // Timeout = 2 hours

			// Suspend the cache during the migration to avoid exhausted memory problem
			wp_suspend_cache_addition(true);
			wp_suspend_cache_invalidation(true);

			// Default values
			$this->plugin_options = array(
				'automatic_empty'				=> 0,
				'url'							=> null,
				'hostname'						=> 'localhost',
				'port'							=> 3306,
				'database'						=> null,
				'username'						=> 'root',
				'password'						=> '',
				'prefix'						=> '',
				'skip_media'					=> 0,
				'first_image'					=> 'as_is_and_featured',
				'import_external'				=> 0,
				'import_duplicates'				=> 0,
				'force_media_import'			=> 0,
				'first_image_not_in_gallery'	=> false,
				'timeout'						=> 5,
				'price'							=> 'without_tax',
				'import_as_pages'				=> 0,
				'logger_autorefresh'			=> 1,
			);
			$options = get_option('fgm2wc_options');
			if ( is_array($options) ) {
				$this->plugin_options = array_merge($this->plugin_options, $options);
			}

			// Check if the upload directory is writable
			$upload_dir = wp_upload_dir();
			if ( !is_writable($upload_dir['basedir']) ) {
				$this->display_admin_error(__('The wp-content directory must be writable.', 'fg-magento-to-woocommerce'));
			}

			// Requires at least WordPress 4.4
			if ( version_compare(get_bloginfo('version'), '4.4', '<') ) {
				$this->display_admin_error(sprintf(__('WordPress 4.4+ is required. Please <a href="%s">update WordPress</a>.', 'fg-magento-to-woocommerce'), admin_url('update-core.php')));
			}
			
			elseif ( !empty($action) ) {
				switch($action) {
					
					// Delete content
					case 'empty':
						if ( check_admin_referer( 'empty', 'fgm2wc_nonce' ) ) { // Security check
							if ($this->empty_database($_POST['empty_action'])) { // Empty WP database
								$this->display_admin_notice(__('WordPress content removed', 'fg-magento-to-woocommerce'));
							} else {
								$this->display_admin_error(__('Couldn\'t remove content', 'fg-magento-to-woocommerce'));
							}
							wp_cache_flush();
						}
						break;
					
					// Save database options
					case 'save':
						$this->save_plugin_options();
						$this->display_admin_notice(__('Settings saved', 'fg-magento-to-woocommerce'));
						break;
					
					// Test the database connection
					case 'test_database':
						// Save database options
						$this->save_plugin_options();

						if ( check_admin_referer( 'parameters_form', 'fgm2wc_nonce' ) ) { // Security check
							if ( $this->test_database_connection() ) {
								return array('status' => 'OK', 'message' => __('Connection successful', 'fg-magento-to-woocommerce'));
							} else {
								return array('status' => 'Error', 'message' => __('Connection failed', 'fg-magento-to-woocommerce'));
							}
						}
						break;
					
					// Run the import
					case 'import':
						// Save database options
						$this->save_plugin_options();

						// Automatic empty
						if ( $this->plugin_options['automatic_empty'] ) {
							if ($this->empty_database('all')) {
								$this->display_admin_notice(__('WordPress content removed', 'fg-magento-to-woocommerce'));
							} else {
								$this->display_admin_error(__('Couldn\'t remove content', 'fg-magento-to-woocommerce'));
							}
							wp_cache_flush();
						}

						// Import content
						if ( check_admin_referer( 'parameters_form', 'fgm2wc_nonce' ) ) { // Security check
							$this->import();
						}
						break;
					
					// Stop the import
					case 'stop_import':
						$this->stop_import();
						break;
					
					default:
						// Do other actions
						do_action('fgm2wc_dispatch', $action);
				}
			}
		}

		/**
		 * Display the admin page
		 * 
		 */
		private function display_admin_page() {
			$data = $this->plugin_options;

			$data['title'] = __('Import Magento', 'fg-magento-to-woocommerce');
			$data['description'] = __('This plugin will import product categories, products, images and CMS from Magento to WooCommerce.<br />Compatible with Magento versions 1.3 to 1.9.', 'fg-magento-to-woocommerce');
			$data['description'] .= "<br />\n" . sprintf(__('For any issue, please read the <a href="%s" target="_blank">FAQ</a> first.', 'fg-magento-to-woocommerce'), $this->faq_url);
			$data['database_info'] = $this->get_database_info();
			
			// Hook for modifying the admin page
			$data = apply_filters('fgm2wc_pre_display_admin_page', $data);

			// Load the CSS and Javascript
			$this->enqueue_styles();
			$this->enqueue_scripts();
			
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display.php';

			// Hook for doing other actions after displaying the admin page
			do_action('fgm2wc_post_display_admin_page');
		}

		/**
		 * Get the WordPress database info
		 * 
		 * @since 2.0.0
		 * 
		 * @return string Database info
		 */
		private function get_database_info() {
			$posts_count = $this->count_posts('post');
			$pages_count = $this->count_posts('page');
			$products_count = $this->count_posts('product');
			$media_count = $this->count_posts('attachment');
			$product_cat_count = wp_count_terms('product_cat', array('hide_empty' => false));
			if ( !is_int($product_cat_count) ) {
				$product_cat_count = 0;
			}

			$database_info =
				sprintf(_n('%d post', '%d posts', $posts_count, 'fg-magento-to-woocommerce'), $posts_count) . "<br />" .
				sprintf(_n('%d page', '%d pages', $pages_count, 'fg-magento-to-woocommerce'), $pages_count) . "<br />" .
				sprintf(_n('%d product category', '%d product categories', $product_cat_count, 'fg-magento-to-woocommerce'), $product_cat_count) . "<br />" .
				sprintf(_n('%d product', '%d products', $products_count, 'fg-magento-to-woocommerce'), $products_count) . "<br />" .
				sprintf(_n('%d media', '%d medias', $media_count, 'fg-magento-to-woocommerce'), $media_count) . "<br />" ;
			$database_info = apply_filters('fgm2wc_get_database_info', $database_info);
			return $database_info;
		}
		
		/**
		 * Count the number of posts for a post type
		 * @param string $post_type
		 */
		public function count_posts($post_type) {
			$count = 0;
			$excluded_status = array('trash', 'auto-draft');
			$tab_count = wp_count_posts($post_type);
			foreach ( $tab_count as $key => $value ) {
				if ( !in_array($key, $excluded_status) ) {
					$count += $value;
				}
			}
			return $count;
		}

		/**
		 * Add an help tab
		 * 
		 */
		public function add_help_tab() {
			$screen = get_current_screen();
			$screen->add_help_tab(array(
				'id'	=> 'fgm2wc_help_instructions',
				'title'	=> __('Instructions'),
				'content'	=> '',
				'callback' => array($this, 'help_instructions'),
			));
			$screen->add_help_tab(array(
				'id'	=> 'fgm2wc_help_options',
				'title'	=> __('Options'),
				'content'	=> '',
				'callback' => array($this, 'help_options'),
			));
			$screen->set_help_sidebar('<a href="' . $this->faq_url . '" target="_blank">' . __('FAQ', 'fg-magento-to-woocommerce') . '</a>');
		}

		/**
		 * Instructions help screen
		 * 
		 * @return string Help content
		 */
		public function help_instructions() {
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/help-instructions.tpl.php';
		}

		/**
		 * Options help screen
		 * 
		 * @return string Help content
		 */
		public function help_options() {
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/help-options.tpl.php';
		}

		/**
		 * Open the connection on Magento database
		 *
		 * return boolean Connection successful or not
		 */
		protected function magento_connect() {
			global $magento_db;

			if ( !class_exists('PDO') ) {
				$this->display_admin_error(__('PDO is required. Please enable it.', 'fg-magento-to-woocommerce'));
				return false;
			}
			try {
				$magento_db = new PDO('mysql:host=' . $this->plugin_options['hostname'] . ';port=' . $this->plugin_options['port'] . ';dbname=' . $this->plugin_options['database'], $this->plugin_options['username'], $this->plugin_options['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
				if ( defined('WP_DEBUG') && WP_DEBUG ) {
					$magento_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Display SQL errors
				}
			} catch ( PDOException $e ) {
				$this->display_admin_error(__('Couldn\'t connect to the Magento database. Please check your parameters. And be sure the WordPress server can access the Magento database.', 'fg-magento-to-woocommerce') . '<br />' . $e->getMessage() . '<br />' . sprintf(__('Please read the <a href="%s" target="_blank">FAQ for the solution</a>.', 'fg-magento-to-woocommerce'), $this->faq_url));
				return false;
			}
			$this->plugin_options['version'] = $this->magento_version();
			return true;
		}

		/**
		 * Execute a SQL query on the Magento database
		 * 
		 * @param string $sql SQL query
		 * @return array Query result
		 */
		public function magento_query($sql) {
			global $magento_db;
			$result = array();

			try {
				$query = $magento_db->query($sql, PDO::FETCH_ASSOC);
				if ( is_object($query) ) {
					foreach ( $query as $row ) {
						$result[] = $row;
					}
				}

			} catch ( PDOException $e ) {
				$this->display_admin_error(__('Error:', 'fg-magento-to-woocommerce') . $e->getMessage());
			}
			return $result;
		}

		/**
		 * Delete all posts, medias and categories from the database
		 *
		 * @param string $action	newposts = removes only new imported posts
		 * 							all = removes all
		 * @return boolean
		 */
		private function empty_database($action) {
			global $wpdb;
			$result = true;

			$wpdb->show_errors();

			// Hook for doing other actions before emptying the database
			do_action('fgm2wc_pre_empty_database', $action);

			$sql_queries = array();

			if ( $action == 'all' ) {
				// Remove all content
				$start_id = 1;
				update_option('fgm2wc_cms_start_id', $start_id);

				$sql_queries[] = "TRUNCATE $wpdb->commentmeta";
				$sql_queries[] = "TRUNCATE $wpdb->comments";
				$sql_queries[] = "TRUNCATE $wpdb->term_relationships";
				$sql_queries[] = "TRUNCATE $wpdb->termmeta";
				$sql_queries[] = "TRUNCATE $wpdb->postmeta";
				$sql_queries[] = "TRUNCATE $wpdb->posts";
				$sql_queries[] = <<<SQL
-- Delete Terms
DELETE FROM $wpdb->terms
WHERE term_id > 1 -- non-classe
SQL;
				$sql_queries[] = <<<SQL
-- Delete Terms taxonomies
DELETE FROM $wpdb->term_taxonomy
WHERE term_id > 1 -- non-classe
SQL;
				$sql_queries[] = "ALTER TABLE $wpdb->terms AUTO_INCREMENT = 2";
				$sql_queries[] = "ALTER TABLE $wpdb->term_taxonomy AUTO_INCREMENT = 2";
			} else {
				// Remove only new imported posts
				// WordPress post ID to start the deletion
				$start_id = intval(get_option('fgm2wc_cms_start_id'));
				if ( $start_id != 0) {

					$sql_queries[] = <<<SQL
-- Delete Comments meta
DELETE FROM $wpdb->commentmeta
WHERE comment_id IN
(
SELECT comment_ID FROM $wpdb->comments
WHERE comment_post_ID IN
	(
	SELECT ID FROM $wpdb->posts
	WHERE (post_type IN ('post', 'page', 'attachment', 'revision')
	OR post_status = 'trash'
	OR post_title = 'Brouillon auto')
	AND ID >= $start_id
	)
);
SQL;

					$sql_queries[] = <<<SQL
-- Delete Comments
DELETE FROM $wpdb->comments
WHERE comment_post_ID IN
(
SELECT ID FROM $wpdb->posts
WHERE (post_type IN ('post', 'page', 'attachment', 'revision')
OR post_status = 'trash'
OR post_title = 'Brouillon auto')
AND ID >= $start_id
);
SQL;

					$sql_queries[] = <<<SQL
-- Delete Term relashionships
DELETE FROM $wpdb->term_relationships
WHERE `object_id` IN
(
SELECT ID FROM $wpdb->posts
WHERE (post_type IN ('post', 'page', 'attachment', 'revision')
OR post_status = 'trash'
OR post_title = 'Brouillon auto')
AND ID >= $start_id
);
SQL;

					$sql_queries[] = <<<SQL
-- Delete Post meta
DELETE FROM $wpdb->postmeta
WHERE post_id IN
(
SELECT ID FROM $wpdb->posts
WHERE (post_type IN ('post', 'page', 'attachment', 'revision')
OR post_status = 'trash'
OR post_title = 'Brouillon auto')
AND ID >= $start_id
);
SQL;

					$sql_queries[] = <<<SQL
-- Delete Posts
DELETE FROM $wpdb->posts
WHERE (post_type IN ('post', 'page', 'attachment', 'revision')
OR post_status = 'trash'
OR post_title = 'Brouillon auto')
AND ID >= $start_id;
SQL;
				}
			}

			// Execute SQL queries
			if ( count($sql_queries) > 0 ) {
				foreach ( $sql_queries as $sql ) {
					$result &= $wpdb->query($sql);
				}
			}

			// Reset the Magento last imported IDs
			update_option('fgm2wc_last_magento_product_id', 0);
			update_option('fgm2wc_last_magento_cms_id', 0);

			// Hook for doing other actions after emptying the database
			do_action('fgm2wc_post_empty_database', $action);

			// Re-count categories and tags items
			$this->terms_count();

			// Clean the cache
			$this->clean_cache(array(), 'category');
			$this->clean_cache(array(), 'product_cat');

			$this->optimize_database();

			$this->progressbar->set_total_count(0);
			
			$wpdb->hide_errors();
			return ($result !== false);
		}

		/**
		 * Delete all woocommerce data
		 *
		 */
		public function delete_woocommerce_data() {
			global $wpdb;
			global $wc_product_attributes;
			
			$wpdb->show_errors();
			
			$sql_queries = array();
			$sql_queries[] = <<<SQL
-- Delete WooCommerce attribute taxonomies
TRUNCATE {$wpdb->prefix}woocommerce_attribute_taxonomies
SQL;

			$sql_queries[] = <<<SQL
-- Delete WooCommerce order items
TRUNCATE {$wpdb->prefix}woocommerce_order_items
SQL;

			$sql_queries[] = <<<SQL
-- Delete WooCommerce order item metas
TRUNCATE {$wpdb->prefix}woocommerce_order_itemmeta
SQL;

			// Execute SQL queries
			if ( count($sql_queries) > 0 ) {
				foreach ( $sql_queries as $sql ) {
					$wpdb->query($sql);
				}
			}
			
			// Reset the WC pages flags
			$wc_pages = array('shop', 'cart', 'checkout', 'myaccount');
			foreach ( $wc_pages as $wc_page ) {
				update_option('woocommerce_' . $wc_page . '_page_id', 0);
			}
			
			// Empty attribute taxonomies cache
			delete_transient('wc_attribute_taxonomies');
			$wc_product_attributes = array();
			
			$wpdb->hide_errors();
			
			$this->display_admin_notice(__('WooCommerce data deleted', 'fg-magento-to-woocommerce'));
			
			// Recreate WooCommerce default data
			if ( class_exists('WC_Install') ) {
				WC_Install::create_pages();
				$this->display_admin_notice(__('WooCommerce default data created', 'fg-magento-to-woocommerce'));
			}
		}
		
		/**
		 * Optimize the database
		 *
		 */
		protected function optimize_database() {
			global $wpdb;

			$sql = <<<SQL
OPTIMIZE TABLE 
`$wpdb->commentmeta` ,
`$wpdb->comments` ,
`$wpdb->options` ,
`$wpdb->postmeta` ,
`$wpdb->posts` ,
`$wpdb->terms` ,
`$wpdb->term_relationships` ,
`$wpdb->term_taxonomy`,
`$wpdb->termmeta`
SQL;
			$wpdb->query($sql);
		}

		/**
		 * Clean the cache
		 * 
		 */
		public function clean_cache($terms=array(), $taxonomy='category') {
			delete_option($taxonomy . '_children');
			clean_term_cache($terms, $taxonomy);
		}

		/**
		 * Test the database connection
		 * 
		 * @return boolean
		 */
		function test_database_connection() {
			global $magento_db;

			if ( $this->magento_connect() ) {
				try {
					$prefix = $this->plugin_options['prefix'];
					
					// Test that the "catalog_product_entity" table exists
					$result = $magento_db->query("DESC ${prefix}catalog_product_entity");
					if ( !is_a($result, 'PDOStatement') ) {
						$errorInfo = $magento_db->errorInfo();
						throw new PDOException($errorInfo[2], $errorInfo[1]);
					}

					$this->display_admin_notice(__('Connected with success to the Magento database', 'fg-magento-to-woocommerce'));

					do_action('fgm2wc_post_test_database_connection');

					return true;

				} catch ( PDOException $e ) {
					$this->display_admin_error(__('Couldn\'t connect to the Magento database. Please check your parameters. And be sure the WordPress server can access the Magento database.', 'fg-magento-to-woocommerce') . '<br />' . $e->getMessage());
					return false;
				}
				$magento_db = null;
			}
			return false;
		}

		/**
		 * Test if the WooCommerce plugin is activated
		 *
		 * @return bool True if the WooCommerce plugin is activated
		 */
		public function test_woocommerce_activation() {
			if ( !class_exists('WooCommerce', false) ) {
				$this->display_admin_error(__('Error: the <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce plugin</a> must be installed and activated to import the products.', 'fg-magento-to-woocommerce'));
				return false;
			}
			return true;
		}

		/**
		 * Get some Magento information
		 *
		 */
		public function get_magento_info() {
			$message = __('Magento data found:', 'fg-magento-to-woocommerce') . '<br />';

			// CMS pages
			$cms_count = $this->get_cms_count();
			$message .= sprintf(_n('%d CMS page', '%d CMS pages', $cms_count, 'fg-magento-to-woocommerce'), $cms_count) . '<br />';

			// Product categories
			$cat_count = $this->get_all_product_categories_count();
			$message .= sprintf(_n('%d product category', '%d product categories', $cat_count, 'fg-magento-to-woocommerce'), $cat_count) . '<br />';

			// Products
			$products_count = $this->get_products_count();
			$message .= sprintf(_n('%d product', '%d products', $products_count, 'fg-magento-to-woocommerce'), $products_count) . '<br />';

			$message = apply_filters('fgm2wc_pre_display_magento_info', $message);

			$this->display_admin_notice($message);
		}

		/**
		 * Get the number of Magento categories
		 * 
		 * @return int Number of categories
		 */
		private function get_all_product_categories_count() {
			$prefix = $this->plugin_options['prefix'];
			$sql = "
				SELECT COUNT(*) AS nb
				FROM ${prefix}catalog_category_entity c
				WHERE c.parent_id != 0 -- don't import the root category
			";
			$result = $this->magento_query($sql);
			$cat_count = isset($result[0]['nb'])? $result[0]['nb'] : 0;
			return $cat_count;
		}

		/**
		 * Get the number of Magento products
		 * 
		 * @return int Number of products
		 */
		private function get_products_count() {
			$prefix = $this->plugin_options['prefix'];
			if ( version_compare($this->plugin_options['version'], '1.4', '<') ) {
				$sql = "
					SELECT COUNT(*) AS nb
					FROM ${prefix}catalog_product_entity p
				";
			} else {
				$sql = "
					SELECT COUNT(DISTINCT p.entity_id) AS nb
					FROM ${prefix}catalog_product_entity p
					LEFT JOIN ${prefix}catalog_product_relation r ON r.child_id = p.entity_id
					LEFT JOIN ${prefix}catalog_product_entity pp ON pp.entity_id = r.parent_id
					WHERE r.child_id IS NULL
					OR pp.type_id IN ('bundle', 'grouped')
				";
			}
			$result = $this->magento_query($sql);
			$posts_count = isset($result[0]['nb'])? $result[0]['nb'] : 0;
			return $posts_count;
		}

		/**
		 * Get the number of Magento CMS pages
		 * 
		 * @return int Number of pages
		 */
		private function get_cms_count() {
			$prefix = $this->plugin_options['prefix'];
			$sql = "
				SELECT COUNT(DISTINCT(a.page_id)) AS nb
				FROM ${prefix}cms_page a
				INNER JOIN ${prefix}cms_page_store s ON s.page_id = a.page_id AND s.store_id IN (0, {$this->store_id})
			";
			$sql = apply_filters('fgm2wc_get_posts_sql', $sql);
			$result = $this->magento_query($sql);
			$cms_count = isset($result[0]['nb'])? $result[0]['nb'] : 0;
			return $cms_count;
		}
		
		/**
		 * Save the plugin options
		 *
		 */
		private function save_plugin_options() {
			$this->plugin_options = array_merge($this->plugin_options, $this->validate_form_info());
			update_option('fgm2wc_options', $this->plugin_options);

			// Hook for doing other actions after saving the options
			do_action('fgm2wc_post_save_plugin_options');
		}

		/**
		 * Validate POST info
		 *
		 * @return array Form parameters
		 */
		private function validate_form_info() {
			// Add http:// before the URL if it is missing
			$url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
			if ( !empty($url) && (preg_match('#^https?://#', $url) == 0) ) {
				$url = 'http://' . $url;
			}
			return array(
				'automatic_empty'				=> filter_input(INPUT_POST, 'automatic_empty', FILTER_VALIDATE_BOOLEAN),
				'url'							=> $url,
				'hostname'						=> filter_input(INPUT_POST, 'hostname', FILTER_SANITIZE_STRING),
				'port'							=> filter_input(INPUT_POST, 'port', FILTER_SANITIZE_NUMBER_INT),
				'database'						=> filter_input(INPUT_POST, 'database', FILTER_SANITIZE_STRING),
				'username'						=> filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING),
				'password'						=> filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING),
				'prefix'						=> filter_input(INPUT_POST, 'prefix', FILTER_SANITIZE_STRING),
				'skip_media'					=> filter_input(INPUT_POST, 'skip_media', FILTER_VALIDATE_BOOLEAN),
				'first_image'					=> filter_input(INPUT_POST, 'first_image', FILTER_SANITIZE_STRING),
				'import_external'				=> filter_input(INPUT_POST, 'import_external', FILTER_VALIDATE_BOOLEAN),
				'import_duplicates'				=> filter_input(INPUT_POST, 'import_duplicates', FILTER_VALIDATE_BOOLEAN),
				'force_media_import'			=> filter_input(INPUT_POST, 'force_media_import', FILTER_VALIDATE_BOOLEAN),
				'first_image_not_in_gallery'	=> filter_input(INPUT_POST, 'first_image_not_in_gallery', FILTER_VALIDATE_BOOLEAN),
				'timeout'						=> filter_input(INPUT_POST, 'timeout', FILTER_SANITIZE_NUMBER_INT),
				'price'							=> filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING),
				'import_as_pages'				=> filter_input(INPUT_POST, 'import_as_pages', FILTER_VALIDATE_BOOLEAN),
				'logger_autorefresh'			=> filter_input(INPUT_POST, 'logger_autorefresh', FILTER_VALIDATE_BOOLEAN),
			);
		}

		/**
		 * Import
		 *
		 */
		private function import() {
			if ( $this->magento_connect() ) {

				$time_start = microtime(true);

				update_option('fgm2wc_stop_import', false, false); // Reset the stop import action
				
				// To solve the issue of links containing ":" in multisite mode
				kses_remove_filters();

				// Check prerequesites before the import
				$do_import = apply_filters('fgm2wc_pre_import_check', true);
				if ( !$do_import) {
					return;
				}

				$total_elements_count = $this->get_total_elements_count();
				$this->progressbar->set_total_count($total_elements_count);
				
				$this->post_type = ($this->plugin_options['import_as_pages'] == 1) ? 'page' : 'post';

				$this->product_types = $this->create_woocommerce_product_types(); // (Re)create the WooCommerce product types
				$this->attribute_types = $this->get_magento_attributes();
				$this->entity_type_codes = $this->get_magento_entity_type_codes();
				$this->default_backorders = 'no';
				$this->global_tax_rate = $this->get_default_tax_rate();

				// Hook for doing other actions before the import
				do_action('fgm2wc_pre_import');

				if ( !isset($this->premium_options['skip_cms']) || !$this->premium_options['skip_cms'] ) {
					$this->import_cms();
				}
				if ( !isset($this->premium_options['skip_products']) || !$this->premium_options['skip_products'] ) {
					$this->import_product_categories();
					$this->import_products();
				}				
				
				// Hook for doing other actions after the import
				do_action('fgm2wc_post_import');

				// Hook for other notices
				do_action('fgm2wc_import_notices');

				// Debug info
				if ( defined('WP_DEBUG') && WP_DEBUG ) {
					$this->display_admin_notice(sprintf("Memory used: %s bytes<br />\n", number_format(memory_get_usage())));
					$time_end = microtime(true);
					$this->display_admin_notice(sprintf("Duration: %d sec<br />\n", $time_end - $time_start));
				}

				if ( $this->import_stopped() ) {
					
					// Import stopped by the user
					$this->display_admin_notice("IMPORT STOPPED BY USER");
					
				} else {
					// Import complete
					$this->display_admin_notice("IMPORT COMPLETE");
				}
				wp_cache_flush();
			}
		}

		/**
		 * Get the number of elements to import
		 * 
		 * @since 2.0.0
		 * 
		 * @return int Number of elements to import
		 */
		private function get_total_elements_count() {
			$count = 0;
			
			// CMS articles
			if ( !isset($this->premium_options['skip_cms']) || !$this->premium_options['skip_cms'] ) {
				$count += $this->get_cms_count();
			}
			
			// Products
			if ( !isset($this->premium_options['skip_products']) || !$this->premium_options['skip_products'] ) {
				$count += $this->get_products_count();
				$count += $this->get_all_product_categories_count();
			}
			
			$count = apply_filters('fgm2wc_get_total_elements_count', $count);
			
			return $count;
		}
		
		/**
		 * Create the WooCommerce product types
		 *
		 * @return array Product types
		 */
		private function create_woocommerce_product_types() {
			$tab_types = array();
			$taxonomy = 'product_type';
			$product_types = array(
				'simple',
				'grouped',
				'variable',
				'external',
			);
			
			foreach ( $product_types as $product_type ) {
				$term = get_term_by('slug', $product_type, $taxonomy);
				if ( !empty($term) ) {
					$tab_types[$product_type] = $term->term_id;
				} else {
					$new_term = wp_insert_term($product_type, $taxonomy);
					if ( !is_wp_error($new_term) ) {
						$tab_types[$product_type] = $new_term['term_id'];
					}
				}
			}
			return $tab_types;
		}
		
		/**
		 * Import the Magento attributes (used for product categories and products)
		 * 
		 * @return array Attribute types
		 */
		protected function get_magento_attributes() {
			$attribute_types = array();
			$prefix = $this->plugin_options['prefix'];
			$sql = "
				SELECT a.attribute_id, a.entity_type_id, a.attribute_code, a.backend_type
				FROM ${prefix}eav_attribute a
				ORDER BY a.entity_type_id, a.backend_type
			";
			$attributes = $this->magento_query($sql);
			// Split the attributes by type
			foreach ( $attributes as $attribute ) {
				if ( $attribute['backend_type'] != 'static' ) {
					$attribute_types[$attribute['entity_type_id']][$attribute['backend_type']][$attribute['attribute_code']] = $attribute['attribute_id'];
				}
			}
			return $attribute_types;
		}
		
		/**
		 * Get the entity type codes
		 * 
		 * @return array Entity type codes
		 */
		protected function get_magento_entity_type_codes() {
			$entity_type_codes = array();
			$prefix = $this->plugin_options['prefix'];
			$sql = "
				SELECT t.entity_type_id, t.entity_type_code
				FROM ${prefix}eav_entity_type t
				ORDER BY t.entity_type_id
			";
			$result = $this->magento_query($sql);
			foreach ( $result as $row ) {
				$entity_type_codes[$row['entity_type_id']] = $row['entity_type_code'];
			}
			return $entity_type_codes;
		}

		/**
		 * Get the WooCommerce default tax rate
		 *
		 * @return float Tax rate
		 */
		private function get_default_tax_rate() {
			global $wpdb;
			$tax = 1;
			
			try {
				$sql = "
					SELECT tax_rate
					FROM {$wpdb->prefix}woocommerce_tax_rates
					WHERE tax_rate_priority = 1
					LIMIT 1
				";
				$tax_rate = $wpdb->get_var($sql);
				if ( !empty($tax_rate) ) {
					$tax = 1 + ($tax_rate / 100);
				}
			} catch ( PDOException $e ) {
				$this->plugin->display_admin_error(__('Error:', 'fg-magento-to-woocommerce') . $e->getMessage());
			}
			return $tax;
		}
		
		/**
		 * Import CMS pages
		 */
		private function import_cms() {
			if ( $this->import_stopped() ) {
				return;
			}
			$imported_posts_count = 0;
			
			$this->log(__('Importing articles...', 'fg-magento-to-woocommerce'));
			
			// Set the WordPress post ID to start the deletion (used when we want to remove only the new imported posts)
			$start_id = intval(get_option('fgm2wc_cms_start_id'));
			if ( $start_id == 0) {
				$start_id = $this->get_next_post_autoincrement();
				update_option('fgm2wc_cms_start_id', $start_id);
			}
			
			// Hook for doing other actions before the import
			do_action('fgm2wc_pre_import_posts');
			
			do {
				if ( $this->import_stopped() ) {
					return;
				}
				
				$posts = $this->get_cms_articles($this->chunks_size); // Get the CMS articles
				$posts_count = count($posts);
				
				if ( is_array($posts) ) {
					foreach ( $posts as $post ) {
						// Increment the CMS last imported post ID
						update_option('fgm2wc_last_magento_cms_id', $post['page_id']);
						
						$new_post_id = $this->import_cms_article($post);
						if ( $new_post_id ) {
							$imported_posts_count++;

							// Hook for doing other actions after inserting the post
							do_action('fgm2wc_post_import_cms_article', $new_post_id, $post);
						}
					}
				}
				$this->progressbar->increment_current_count($posts_count);
				
			} while ( ($posts != null) && ($posts_count > 0) );
			
			$this->display_admin_notice(sprintf(_n('%d article imported', '%d articles imported', $imported_posts_count, 'fg-magento-to-woocommerce'), $imported_posts_count));
			
			// Hook for doing other actions after the import
			do_action('fgm2wc_post_import_posts');
			
			return array(
				'posts_count'	=> $imported_posts_count,
			);
		}
		
		/**
		 * Import a CMS page
		 * 
		 * @since 2.4.0
		 * 
		 * @param array $post CMS page data
		 * @return int New post ID
		 */
		public function import_cms_article($post) {
			// Hook for modifying the CMS post before processing
			$post = apply_filters('fgm2wc_pre_process_post', $post);

			// Date
			$post_date = $post['creation_time'];

			// Content
			$content = $post['content'];
			if ( !empty($post['content_heading']) ) {
				$content = '<h2>' . $post['content_heading'] . '</h2>' . $content;
			}

			// Replace Magento media URLs
			$content = preg_replace('/{{media url="(.*?)"}}/', "media/$1", $content);

			// Medias
			if ( !$this->plugin_options['skip_media'] ) {
				// Extra featured image
				$featured_image = '';
				list($featured_image, $post) = apply_filters('fgm2wc_pre_import_media', array($featured_image, $post));
				// Import media
				$result = $this->import_media_from_content($featured_image . $content, $post_date);
				$post_media = $result['media'];
				$this->media_count += $result['media_count'];
			} else {
				// Skip media
				$post_media = array();
			}

			// Process content
			$content = $this->process_content($content, $post_media);

			// Status
			$status = ($post['is_active'] == 1)? 'publish' : 'draft';

			// Insert the post
			$new_post = array(
				'post_content'		=> $content,
				'post_date'			=> $post_date,
				'post_status'		=> $status,
				'post_title'		=> $post['title'],
				'post_name'			=> $post['identifier'],
				'post_type'			=> $this->post_type,
				'menu_order'        => $post['sort_order'],
			);

			// Hook for modifying the WordPress post just before the insert
			$new_post = apply_filters('fgm2wc_pre_insert_post', $new_post, $post);

			$new_post_id = wp_insert_post($new_post);

			if ( $new_post_id ) {
				// Add links between the post and its medias
				$this->add_post_media($new_post_id, $this->get_attachment_ids($post_media), $post_date, $this->plugin_options['first_image'] != 'as_is');

				// Add the CMS ID as a post meta in order to modify links after
				add_post_meta($new_post_id, '_fgm2wc_old_cms_id', $post['page_id'], true);
				
				// Hook for doing other actions after inserting the post
				do_action('fgm2wc_post_insert_post', $new_post_id, $post);
			}
			
			return $new_post_id;
		}
		
		/**
		 * Import product categories
		 *
		 * @return int Number of product categories imported
		 */
		private function import_product_categories() {
			if ( $this->import_stopped() ) {
				return;
			}
			$this->log(__('Importing product categories...', 'fg-magento-to-woocommerce'));
			
			$imported_categories_count = 0;
			$terms = array();
			$taxonomy = 'product_cat';
			$this->used_slugs = array();
			
			// Set the list of previously imported categories
			$this->get_imported_categories($this->default_language);
			
			$categories = $this->get_all_product_categories();
			$categories_count = count($categories);
			foreach ( $categories as $category ) {
				
				// Check if the category is already imported
				if ( array_key_exists($category['entity_id'], $this->imported_categories[$this->default_language]) ) {
					continue; // Do not import already imported category
				}
				
				$new_term = $this->import_product_category($category, $this->default_language);
				if ( !is_wp_error($new_term) ) {
					$imported_categories_count++;
					$terms[] = $new_term['term_id'];
					
					// Hook after inserting the category
					do_action('fgm2wc_post_import_product_category', $new_term['term_id'], $category);
				}
			}
			
			// Set the list of imported categories
			$this->get_imported_categories($this->default_language);
			
			// Update the categories with their parent ids
			foreach ( $categories as $category ) {
				if ( array_key_exists($category['entity_id'], $this->imported_categories[$this->default_language]) && array_key_exists($category['parent_id'], $this->imported_categories[$this->default_language]) ) {
					$cat_id = $this->imported_categories[$this->default_language][$category['entity_id']];
					$parent_cat_id = $this->imported_categories[$this->default_language][$category['parent_id']];
					$cat = get_term_by('term_taxonomy_id', $cat_id, $taxonomy);
					$parent_cat = get_term_by('term_taxonomy_id', $parent_cat_id, $taxonomy);
					if ( $cat && $parent_cat ) {
						// Hook before editing the category
						$cat = apply_filters('fgm2wc_pre_edit_category', $cat, $parent_cat);
						wp_update_term($cat->term_id, $taxonomy, array('parent' => $parent_cat->term_id));
						// Hook after editing the category
						do_action('fgm2wc_post_edit_category', $cat);
					}
				}
			}
			
			// Hook after importing all the categories
			do_action('fgm2wc_post_import_product_categories', $categories);
			
			// Update cache
			if ( !empty($terms) ) {
				wp_update_term_count_now($terms, $taxonomy);
				$this->clean_cache($terms, $taxonomy);
			}
			$this->progressbar->increment_current_count($categories_count);
			$this->display_admin_notice(sprintf(_n('%d product category imported', '%d product categories imported', $imported_categories_count, 'fg-magento-to-woocommerce'), $imported_categories_count));
		}
		
		/**
		 * Store the mapping of the imported product categories
		 * 
		 * @param int $language Language ID
		 */
		public function get_imported_categories($language) {
			$this->imported_categories[$language] = $this->get_term_metas_by_metakey('_fgm2wc_old_product_category_id' . '-lang' . $language);
		}
		
		/**
		 * Import a product category
		 *
		 * @since 2.4.0
		 * 
		 * @param array $category Category
		 * @return WP_Term|false Category
		 */
		public function import_product_category($category, $language) {
			$taxonomy = 'product_cat';
			
			// Other fields
			$category = array_merge($category, $this->get_attribute_values($category['entity_id'], $category['entity_type_id'], array(
				'name',
				'description',
				'url_key',
//					'url_path',
				'image',
			)));

			// Date
			$date = $category['created_at'];

			// Slug
			$slug = isset($category['url_key'])? $category['url_key']: sanitize_title($category['name']);
			$slug = $this->build_unique_slug($slug, $this->used_slugs);
			$this->used_slugs[] = $slug;

			// Insert the category
			$new_category = array(
				'description'	=> isset($category['description'])? $category['description']: '',
				'slug'			=> $slug,
			);

			// Hook before inserting the category
			$new_category = apply_filters('fgm2wc_pre_insert_product_category', $new_category, $category);

			$new_term = wp_insert_term($category['name'], $taxonomy, $new_category);
			if ( !is_wp_error($new_term) ) {
				// Store the product category ID
				add_term_meta($new_term['term_id'], '_fgm2wc_old_product_category_id' . '-lang' . $language, $category['entity_id'], true);
				
				// Category ordering
				if ( function_exists('wc_set_term_order') ) {
					wc_set_term_order($new_term['term_id'], $category['position'], $taxonomy);
				}

				// Category image
				if ( !$this->plugin_options['skip_media'] && function_exists('update_woocommerce_term_meta') ) {
					if ( isset($category['image']) && !empty($category['image']) ) {
						$image_path = '/media/catalog/category/' . $category['image'];
						$thumbnail_id = $this->import_media($category['name'], $image_path, $date);
						if ( !empty($thumbnail_id) ) {
							$this->media_count++;
							update_woocommerce_term_meta($new_term['term_id'], 'thumbnail_id', $thumbnail_id);
						}
					}
				}
				
				// Hook after inserting the category
				do_action('fgm2wc_post_insert_product_category', $new_term['term_id'], $category);
			}
			return $new_term;
		}
		
		/**
		 * Import products
		 *
		 * @return int Number of products imported
		 */
		private function import_products() {
			if ( !$this->test_woocommerce_activation() ) {
				return 0;
			}
			
			if ( $this->import_stopped() ) {
				return;
			}
			$this->log(__('Importing products...', 'fg-magento-to-woocommerce'));
			$imported_products_count = 0;
			
			do {
				if ( $this->import_stopped() ) {
					return;
				}
				$products = $this->get_products($this->chunks_size);
				$products_count = count($products);
				foreach ( $products as $product ) {
					// Increment the Magento last imported product ID
					update_option('fgm2wc_last_magento_product_id', $product['entity_id']);
					
					$new_post_id = $this->import_product($product, $this->default_language);
					if ( $new_post_id ) {
						$imported_products_count++;
						
						// Hook for doing other actions after importing the post
						do_action('fgm2wc_post_import_product', $new_post_id, $product);
					}
				}
				$this->progressbar->increment_current_count($products_count);
				
			} while ( ($products != null) && ($products_count > 0) );
			
			update_option('fgm2wc_last_update', date('Y-m-d H:i:s'));
			
			// Hook for doing other actions after all products are imported
			do_action('fgm2wc_post_import_products');
			
			$this->display_admin_notice(sprintf(_n('%d product imported', '%d products imported', $imported_products_count, 'fg-magento-to-woocommerce'), $imported_products_count));
		}
		
		/**
		 * Import a product
		 * 
		 * @since 2.4.0
		 * 
		 * @param array $product Product
		 * @return int Product ID
		 */
		public function import_product($product, $language) {
			// Date
			$date = $product['created_at'];

			// Other fields
			$product = array_merge($product, $this->get_other_product_fields($product['entity_id'], $product['entity_type_id']));

			// Product images
			list($product_medias, $post_media) = $this->import_product_medias($product);

			// Product categories
			$categories_ids = array();
			$product_categories = $this->get_product_categories($product['entity_id']);
			foreach ( $product_categories as $cat ) {
				if ( array_key_exists($cat, $this->imported_categories[$language]) ) {
					$categories_ids[] = $this->imported_categories[$language][$cat];
				}
			}

			// Process content
			$content = isset($product['description'])? $product['description'] : '';
			$content = $this->process_content($content, $post_media);
			$excerpt = isset($product['short_description'])? $product['short_description'] : '';
			
			$title = isset($product['name'])? $product['name']: '';
			
			// Insert the post
			$new_post = array(
				'post_content'		=> $content,
				'post_date'			=> $date,
				'post_excerpt'		=> $excerpt,
				'post_status'		=> ($product['status'] == 1)? 'publish': 'draft',
				'post_title'		=> $title,
				'post_name'			=> isset($product['url_key'])? $product['url_key'] : $title,
				'post_type'			=> 'product',
				'tax_input'			=> array(
					'product_cat'	=> $categories_ids,
				),
			);

			// Hook for modifying the WordPress post just before the insert
			$new_post = apply_filters('fgm2wc_pre_insert_product', $new_post, $product);

			$new_post_id = wp_insert_post($new_post);

			if ( $new_post_id ) {

				// Product type
				$product_type = $this->product_type($product['type_id']);
				wp_set_object_terms($new_post_id, $product_type, 'product_type', true);

				// Product galleries
				$medias_id = array();
				foreach ($product_medias as $media) {
					$medias_id[] = $media;
				}
				if ( $this->plugin_options['first_image_not_in_gallery'] ) {
					// Don't include the first image into the product gallery
					array_shift($medias_id);
				}
				$gallery = implode(',', $medias_id);

				// Prices
				$prices = $this->calculate_prices($product);
				$special_from_date = isset($product['special_from_date'])? strtotime($product['special_from_date']): '';
				$special_to_date = isset($product['special_to_date'])? strtotime($product['special_to_date']): '';

				// Stock
				$manage_stock = 'yes';
				$stock_status = ($product['is_in_stock'] > 0)? 'instock': 'outofstock';

				// Backorders
				$backorders = $this->allow_backorders($product['backorders']);

				// Add the meta data
				add_post_meta($new_post_id, '_visibility', 'visible', true);
				add_post_meta($new_post_id, '_stock_status', $stock_status, true);
				add_post_meta($new_post_id, '_regular_price', $prices['regular_price'], true);
				add_post_meta($new_post_id, '_price', $prices['price'], true);
				add_post_meta($new_post_id, '_sale_price', $prices['special_price'], true);
				add_post_meta($new_post_id, '_sale_price_dates_from', $special_from_date, true);
				add_post_meta($new_post_id, '_sale_price_dates_to', $special_to_date, true);
				add_post_meta($new_post_id, '_featured', 'no', true);
				if ( isset($product['weight']) ) {
					add_post_meta($new_post_id, '_weight', floatval($product['weight']), true);
				}
				add_post_meta($new_post_id, '_sku', $product['sku'], true);
				add_post_meta($new_post_id, '_stock', $product['qty'], true);
				add_post_meta($new_post_id, '_manage_stock', $manage_stock, true);
				add_post_meta($new_post_id, '_backorders', $backorders, true);
				add_post_meta($new_post_id, '_product_image_gallery', $gallery, true);

				// Add links between the post and its medias
				$this->add_post_media($new_post_id, $product_medias, $date, true);
				$this->add_post_media($new_post_id, $this->get_attachment_ids($post_media), $date, false);

				// Add the Magento ID as a post meta
				add_post_meta($new_post_id, '_fgm2wc_old_product_id', $product['entity_id'], true);

				// Hook for doing other actions after inserting the post
				do_action('fgm2wc_post_insert_product', $new_post_id, $product, $prices['price'], $prices['special_price']);
			}
			return $new_post_id;
		}
		
		/**
		 * Get the product type
		 * 
		 * @since      1.10.0
		 * 
		 * @param string $type_id Type ID (simple, grouped, …)
		 * @return int Product type term ID
		 */
		private function product_type($type_id) {
			$product_type = 0;
			switch ($type_id) {
				case 'grouped':
					$product_type = $this->product_types['grouped'];
					break;
				default:
					$product_type = $this->product_types['simple'];
			}
			return intval($product_type);
		}
		
		/**
		 * Calculate the product prices
		 * 
		 * @since 2.3.0
		 * 
		 * @param array $product Product
		 * @return array Prices
		 */
		public function calculate_prices($product) {
			$regular_price = isset($product['price'])? floatval($product['price']): 0.0;
			$special_price = isset($product['special_price'])? floatval($product['special_price']): '';
			if ( $this->plugin_options['price'] == 'with_tax' ) {
				$regular_price *= $this->global_tax_rate;
				if ( !empty($special_price) ) {
					$special_price *= $this->global_tax_rate;
				}
			}
			$prices = array(
				'regular_price'	=> $regular_price,
				'special_price'	=> $special_price,
				'price'			=> !empty($special_price)? $special_price: $regular_price,
			);
			
			return $prices;
		}
		
		/**
		 * Get the other product fields
		 * 
		 * @param int $product_id Product ID
		 * @param int $product_entity_id Product Entity ID
		 * @return array Product data
		 */
		public function get_other_product_fields($product_id, $product_entity_id) {
			return $this->get_attribute_values($product_id, $product_entity_id,
				array(
				'name',
				'description',
				'short_description',
				'price',
				'special_price',
				'special_from_date',
				'special_to_date',
				'weight',
//				'manufacturer',
				'meta_title',
				'meta_description',
				'meta_keyword',
				'meta_keywords',
				'image',
				'image_label',
				'status',
				'url_key',
//				'url_path',
//				'tax_class_id',
			));
		}
		
		/**
		 * Import the product medias
		 * 
		 * @param array $product Product data
		 * @return array[array, array] = [product_medias, post_media]
		 */
		public function import_product_medias($product) {
			$product_medias = array();
			$post_media = array();
			
			if ( !$this->plugin_options['skip_media'] ) {
				$images = array();
				
				// Featured image
				if ( !empty($product['image']) ) {
					$featured_image = array(
						'value_id'	=> 0,
						'value'		=> $product['image'],
						'label'		=> isset($product['image_label'])? $product['image_label']: '',
					);
					$images[] = $featured_image;
				}
				
				// Gallery images
				$images = array_merge($images, $this->get_product_images($product['entity_id']));
				foreach ( $images as $image ) {
					$image_name = !empty($image['label'])? $image['label'] : $product['name'] . '-' . $image['value_id'];
					$image_filename = '/media/catalog/product' . $image['value'];
					$media_id = $this->import_media($image_name, $image_filename, $product['created_at']);
					if ( $media_id !== false ) {
						$product_medias[] = $media_id;
					}
				}
				$this->media_count += count($product_medias);

				// Import content media
				$result = $this->import_media_from_content($product['description'], $product['created_at']);
				$post_media = $result['media'];
				$this->media_count += $result['media_count'];
			}
			return array($product_medias, $post_media);
		}
		
		/**
		 * Build a unique slug
		 * 
		 * @param string $slug Wished slug
		 * @param array $used_slugs Used slugs
		 * @return string New unique slug
		 */
		private function build_unique_slug($slug, $used_slugs) {
			$matches = array();
			$slug = apply_filters('fgm2wc_pre_build_unique_slug', $slug);
			$slug = sanitize_title($slug);
			if ( in_array($slug, $used_slugs) ) {
				// Get the slug suffix
				if ( preg_match('/(.*)\-(\d+)$/', $slug, $matches) ) {
					$inc = $matches[2] + 1;
					$slug = $matches[1];
				} else {
					$inc = 1;
				}
				// Add a suffix
				$slug .= '-' . $inc;
			}
			return $slug;
		}
		
		/**
		 * Get CMS articles
		 *
		 * @param int limit Number of articles max
		 * @return array of Posts
		 */
		protected function get_cms_articles($limit=1000) {
			$articles = array();
			$prefix = $this->plugin_options['prefix'];
			
			$last_magento_cms_id = (int)get_option('fgm2wc_last_magento_cms_id'); // to restore the import where it left

			// Hooks for adding extra cols and extra joins
			$extra_cols = apply_filters('fgm2wc_get_posts_add_extra_cols', '');
			$extra_joins = apply_filters('fgm2wc_get_posts_add_extra_joins', '');

			if ( $this->column_exists('cms_page', 'content_heading') ) {
				// Magento 1.4+
				$content_heading_field = 'a.content_heading';
			} else {
				// Magento 1.3 and less
				$content_heading_field = '"" AS content_heading';
			}
			$sql = "
				SELECT DISTINCT a.page_id, a.title, a.meta_keywords, a.meta_description, a.identifier, $content_heading_field, a.content, a.creation_time, a.is_active, a.sort_order
				$extra_cols
				FROM ${prefix}cms_page a
				INNER JOIN ${prefix}cms_page_store s ON s.page_id = a.page_id AND s.store_id IN (0, {$this->store_id})
				WHERE a.page_id > '$last_magento_cms_id'
				$extra_joins
				ORDER BY a.sort_order
				LIMIT $limit
			";
			$sql = apply_filters('fgm2wc_get_posts_sql', $sql, $extra_cols, $extra_joins, $last_magento_cms_id, $limit);
			$articles = $this->magento_query($sql);
			
			return $articles;
		}
		
		/**
		 * Get product categories
		 *
		 * @return array of Categories
		 */
		private function get_all_product_categories() {
			$categories = array();
			$prefix = $this->plugin_options['prefix'];

			$sql = "
				SELECT c.entity_id, c.entity_type_id, c.parent_id, c.created_at, c.position
				FROM ${prefix}catalog_category_entity c
				WHERE c.parent_id != 0 -- don't import the root category
				ORDER BY c.level, c.position
			";
			$sql = apply_filters('fgm2wc_get_categories_sql', $sql);
			$categories = $this->magento_query($sql);
			
			$categories = apply_filters('fgm2wc_get_categories', $categories);
			
			return $categories;
		}
		
		/**
		 * Get attribute values
		 *
		 * @param int $entity_id Entity ID
		 * @param int $entity_type_id Entity type ID
		 * @param int $required_attributes Array of attribute keys (optional)
		 * @return array of values
		 */
		public function get_attribute_values($entity_id, $entity_type_id, $required_attributes=array()) {
			$values = array();
			$prefix = $this->plugin_options['prefix'];
						
			// Get the entity type code
			$entity_type_code = $this->entity_type_codes[$entity_type_id];
			if ( empty($entity_type_code) ) {
				return array();
			}
			
			// Get the values from the different tables
			foreach ( $this->attribute_types[$entity_type_id] as $attribute_type => $attributes ) {
				if ( empty($required_attributes) ) {
					$attributes_ids = $attributes;
				} else {
					$attributes_ids = array();
					// Get the required attributes IDs
					foreach ( $required_attributes as $required_attribute) {
						if ( array_key_exists($required_attribute, $attributes) ) {
							$attributes_ids[] = $attributes[$required_attribute];
						}
					}
				}
				$attributes_ids_list = implode("', '", $attributes_ids);
				$sql = "
					SELECT a.attribute_code, e.value
					FROM ${prefix}${entity_type_code}_entity_$attribute_type e
					INNER JOIN ${prefix}eav_attribute a ON a.attribute_id = e.attribute_id
					WHERE e.entity_id = '$entity_id'
					AND e.attribute_id IN ('$attributes_ids_list')
				";
				if ( $this->column_exists("${entity_type_code}_entity_$attribute_type", 'store_id') ) {
					$sql .= "
						AND e.store_id IN (0, {$this->store_id})
						ORDER BY e.store_id
					";
				}
				$result = $this->magento_query($sql);
				foreach ( $result as $row ) {
					$values[$row['attribute_code']] = $row['value'];
				}
			}
			return $values;
		}
		
		/**
		 * Get the products
		 * 
		 * @param int limit Number of products max
		 * @return array of products
		 */
		private function get_products($limit=1000) {
			$products = array();
			$prefix = $this->plugin_options['prefix'];

			$last_magento_product_id = (int)get_option('fgm2wc_last_magento_product_id'); // to restore the import where it left
			
			if ( version_compare($this->plugin_options['version'], '1.4', '<') ) {
				$sql = "
					SELECT p.entity_id, p.entity_type_id, p.type_id, p.sku, p.created_at, s.qty, s.is_in_stock, s.backorders
					FROM ${prefix}catalog_product_entity p
					LEFT JOIN ${prefix}cataloginventory_stock_item s ON s.product_id = p.entity_id
					WHERE p.entity_id > '$last_magento_product_id'
					ORDER BY p.entity_id
					LIMIT $limit
				";
			} else {
				$sql = "
					SELECT DISTINCT p.entity_id, p.entity_type_id, p.type_id, p.sku, p.created_at, s.qty, s.is_in_stock, s.backorders
					FROM ${prefix}catalog_product_entity p
					LEFT JOIN ${prefix}cataloginventory_stock_item s ON s.product_id = p.entity_id
					LEFT JOIN ${prefix}catalog_product_relation r on r.child_id = p.entity_id
					LEFT JOIN ${prefix}catalog_product_entity pp ON pp.entity_id = r.parent_id
					WHERE p.entity_id > '$last_magento_product_id'
					AND (r.child_id IS NULL OR pp.type_id IN ('bundle', 'grouped'))
					ORDER BY p.entity_id
					LIMIT $limit
				";
			}
			$products = $this->magento_query($sql);
			
			return $products;
		}
		
		/**
		 * Get the product images
		 *
		 * @param int $product_id Product ID
		 * @return array of images
		 */
		public function get_product_images($product_id) {
			$images = array();
			$prefix = $this->plugin_options['prefix'];

			$sql = "
				SELECT DISTINCT g.value_id, g.value, gv.label
				FROM ${prefix}catalog_product_entity_media_gallery g
				INNER JOIN ${prefix}catalog_product_entity_media_gallery_value gv ON gv.value_id = g.value_id
				WHERE g.entity_id = '$product_id'
				AND gv.store_id IN (0, {$this->store_id})
				AND gv.disabled = 0
				ORDER BY gv.position
			";
			$images = $this->magento_query($sql);
			
			return $images;
		}
		
		/**
		 * Get the categories from a product
		 *
		 * @param int $product_id Magento product ID
		 * @return array of categories IDs
		 */
		private function get_product_categories($product_id) {
			$categories = array();
			$prefix = $this->plugin_options['prefix'];

			$sql = "
				SELECT cp.category_id
				FROM ${prefix}catalog_category_product cp
				WHERE cp.product_id = '$product_id'
				ORDER BY cp.position
			";
			$result = $this->magento_query($sql);
			foreach ( $result as $row ) {
				$categories[] = $row['category_id'];
			}
			return $categories;
		}
		
		/**
		 * Import post medias from content
		 *
		 * @param string $content post content
		 * @param date $post_date Post date (for storing media)
		 * @param array $options Options
		 * @return array:
		 * 		array media: Medias imported
		 * 		int media_count:   Medias count
		 */
		public function import_media_from_content($content, $post_date, $options=array()) {
			$media = array();
			$media_count = 0;
			$matches = array();
			$alt_matches = array();
			
			if ( preg_match_all('#<(img|a)(.*?)(src|href)="(.*?)"(.*?)>#', $content, $matches, PREG_SET_ORDER) > 0 ) {
				if ( is_array($matches) ) {
					foreach ($matches as $match ) {
						$filename = $match[4];
						$other_attributes = $match[2] . $match[5];
						// Image Alt
						$image_alt = '';
						if (preg_match('#alt="(.*?)"#', $other_attributes, $alt_matches) ) {
							$image_alt = wp_strip_all_tags(stripslashes($alt_matches[1]), true);
						}
						$attachment_id = $this->import_media($image_alt, $filename, $post_date, $options);
						if ( $attachment_id !== false ) {
							$media_count++;
							$media[$filename] = $attachment_id;
						}
					}
				}
			}
			return array(
				'media'			=> $media,
				'media_count'	=> $media_count
			);
		}
		
		/**
		 * Import a media
		 *
		 * @param string $name Image name
		 * @param string $filename Image URL
		 * @param date $date Date
		 * @param array $options Options
		 * @return int attachment ID or false
		 */
		public function import_media($name, $filename, $date, $options=array()) {
			if ( $date == '0000-00-00 00:00:00' ) {
				$date = date('Y-m-d H:i:s');
			}
			$import_external = ($this->plugin_options['import_external'] == 1) || (isset($options['force_external']) && $options['force_external'] );
			
			$filename = str_replace("%20", " ", $filename); // for filenames with spaces
			
			$filetype = wp_check_filetype($filename);
			if ( empty($filetype['type']) || ($filetype['type'] == 'text/html') ) { // Unrecognized file type
				return false;
			}

			// Upload the file from the Magento web site to WordPress upload dir
			if ( preg_match('/^http/', $filename) ) {
				if ( $import_external || // External file 
					preg_match('#^' . $this->plugin_options['url'] . '#', $filename) // Local file
				) {
					$old_filename = $filename;
				} else {
					return false;
				}
			} else {
				if ( strpos($filename, '/') == 0 ) { // Avoid a double slash
					$old_filename = untrailingslashit($this->plugin_options['url']) . $filename;
				} else {
					$old_filename = trailingslashit($this->plugin_options['url']) . $filename;
				}
			}
			$old_filename = str_replace(" ", "%20", $old_filename); // for filenames with spaces
			$img_dir = strftime('%Y/%m', strtotime($date));
			$uploads = wp_upload_dir($img_dir);
			$new_upload_dir = $uploads['path'];

			$new_filename = $filename;
			if ( $this->plugin_options['import_duplicates'] == 1 ) {
				// Images with duplicate names
				$new_filename = preg_replace('#.*media/catalog/#', '', $new_filename);
				$new_filename = str_replace('http://', '', $new_filename);
				$new_filename = str_replace('/', '_', $new_filename);
			}

			$basename = basename($new_filename);
			$new_full_filename = $new_upload_dir . '/' . $basename;

//			print "Copy \"$old_filename\" => $new_full_filename<br />";
			if ( ! @$this->remote_copy($old_filename, $new_full_filename) ) {
				$error = error_get_last();
				$error_message = $error['message'];
				$this->display_admin_error("Can't copy $old_filename to $new_full_filename : $error_message");
				return false;
			}
			
			$post_title = !empty($name)? $name : preg_replace('/\.[^.]+$/', '', $basename);
			$post_name = sanitize_title($post_title);
			
			// If the attachment does not exist yet, insert it in the database
			$attachment_id = 0;
			$attachment = $this->get_attachment_from_name($post_name);
			if ( $attachment ) {
				$attached_file = basename(get_attached_file($attachment->ID));
				if ( $attached_file == $basename ) { // Check if the filename is the same (in case of the legend is not unique)
					$attachment_id = $attachment->ID;
				}
			}
			if ( $attachment_id == 0 ) {
				$attachment_data = array(
					'guid'				=> $uploads['url'] . '/' . $basename, 
					'post_date'			=> $date,
					'post_mime_type'	=> $filetype['type'],
					'post_name'			=> $post_name,
					'post_title'		=> $post_title,
					'post_status'		=> 'inherit',
					'post_content'		=> '',
				);
				$attachment_id = wp_insert_attachment($attachment_data, $new_full_filename);
			}
			
			if ( !empty($attachment_id) ) {
				if ( preg_match('/image/', $filetype['type']) ) { // Images
					// you must first include the image.php file
					// for the function wp_generate_attachment_metadata() to work
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_full_filename );
					wp_update_attachment_metadata($attachment_id, $attach_data);

					// Image Alt
					if ( !empty($name) ) {
						$image_alt = wp_strip_all_tags(stripslashes($name), true);
						update_post_meta($attachment_id, '_wp_attachment_image_alt', addslashes($image_alt)); // update_meta expects slashed
					}
				}
				return $attachment_id;
			} else {
				return false;
			}
		}
		
		/**
		 * Check if the attachment exists in the database
		 *
		 * @param string $name
		 * @return object Post
		 */
		private function get_attachment_from_name($name) {
			$name = preg_replace('/\.[^.]+$/', '', basename($name));
			$r = array(
				'name'			=> $name,
				'post_type'		=> 'attachment',
				'numberposts'	=> 1,
			);
			$posts_array = get_posts($r);
			if ( is_array($posts_array) && (count($posts_array) > 0) ) {
				return $posts_array[0];
			}
			else {
				return false;
			}
		}

		/**
		 * Process the post content
		 *
		 * @param string $content Post content
		 * @param array $post_media Post medias
		 * @return string Processed post content
		 */
		public function process_content($content, $post_media) {

			if ( !empty($content) ) {
				$content = str_replace(array("\r", "\n"), array('', ' '), $content);

				// Replace page breaks
				$content = preg_replace("#<hr([^>]*?)class=\"system-pagebreak\"(.*?)/>#", "<!--nextpage-->", $content);

				// Replace media URLs with the new URLs
				$content = $this->process_content_media_links($content, $post_media);

				// For importing backslashes
				$content = addslashes($content);
			}

			return $content;
		}

		/**
		 * Replace media URLs with the new URLs
		 *
		 * @param string $content Post content
		 * @param array $post_media Post medias
		 * @return string Processed post content
		 */
		private function process_content_media_links($content, $post_media) {
			$matches = array();
			$matches_caption = array();

			if ( is_array($post_media) ) {

				// Get the attachments attributes
				$attachments_found = false;
				$medias = array();
				foreach ( $post_media as $old_filename => $attachment_id ) {
					$media = array();
					$media['attachment_id'] = $attachment_id;
					$media['url_old_filename'] = urlencode($old_filename); // for filenames with spaces or accents
					if ( preg_match('/image/', get_post_mime_type($attachment_id)) ) {
						// Image
						$image_src = wp_get_attachment_image_src($attachment_id, 'full');
						$media['new_url'] = $image_src[0];
						$media['width'] = $image_src[1];
						$media['height'] = $image_src[2];
					} else {
						// Other media
						$media['new_url'] = wp_get_attachment_url($attachment_id);
					}
					$medias[$old_filename] = $media;
					$attachments_found = true;
				}
				if ( $attachments_found ) {

					// Remove the links from the content
					$this->post_link_count = 0;
					$this->post_link = array();
					$content = preg_replace_callback('#<(a) (.*?)(href)=(.*?)</a>#i', array($this, 'remove_links'), $content);
					$content = preg_replace_callback('#<(img) (.*?)(src)=(.*?)>#i', array($this, 'remove_links'), $content);

					// Process the stored medias links
					$first_image_removed = false;
					foreach ($this->post_link as &$link) {

						// Remove the first image from the content
						if ( ($this->plugin_options['first_image'] == 'as_featured') && !$first_image_removed && preg_match('#^<img#', $link['old_link']) ) {
							$link['new_link'] = '';
							$first_image_removed = true;
							continue;
						}
						$new_link = $link['old_link'];
						$alignment = '';
						if ( preg_match('/(align="|float: )(left|right)/', $new_link, $matches) ) {
							$alignment = 'align' . $matches[2];
						}
						if ( preg_match_all('#(src|href)="(.*?)"#i', $new_link, $matches, PREG_SET_ORDER) ) {
							$caption = '';
							foreach ( $matches as $match ) {
								$old_filename = $match[2];
								$link_type = ($match[1] == 'src')? 'img': 'a';
								if ( array_key_exists($old_filename, $medias) ) {
									$media = $medias[$old_filename];
									if ( array_key_exists('new_url', $media) ) {
										if ( (strpos($new_link, $old_filename) > 0) || (strpos($new_link, $media['url_old_filename']) > 0) ) {
											// URL encode the filename
											$new_filename = basename($media['new_url']);
											$encoded_new_filename = rawurlencode($new_filename);
											$new_url = str_replace($new_filename, $encoded_new_filename, $media['new_url']);
											$new_link = preg_replace('#(' . preg_quote($old_filename) . '|' . preg_quote($media['url_old_filename']) . ')#', $new_url, $new_link, 1);

											if ( $link_type == 'img' ) { // images only
												// Define the width and the height of the image if it isn't defined yet
												if ((strpos($new_link, 'width=') === false) && (strpos($new_link, 'height=') === false)) {
													$width_assertion = isset($media['width'])? ' width="' . $media['width'] . '"' : '';
													$height_assertion = isset($media['height'])? ' height="' . $media['height'] . '"' : '';
												} else {
													$width_assertion = '';
													$height_assertion = '';
												}

												// Caption shortcode
												if ( preg_match('/class=".*caption.*?"/', $link['old_link']) ) {
													if ( preg_match('/title="(.*?)"/', $link['old_link'], $matches_caption) ) {
														$caption_value = str_replace('%', '%%', $matches_caption[1]);
														$align_value = ($alignment != '')? $alignment : 'alignnone';
														$caption = '[caption id="attachment_' . $media['attachment_id'] . '" align="' . $align_value . '"' . $width_assertion . ']%s' . $caption_value . '[/caption]';
													}
												}

												$align_class = ($alignment != '')? $alignment . ' ' : '';
												$new_link = preg_replace('#<img(.*?)( class="(.*?)")?(.*) />#', "<img$1 class=\"$3 " . $align_class . 'size-full wp-image-' . $media['attachment_id'] . "\"$4" . $width_assertion . $height_assertion . ' />', $new_link);
											}
										}
									}
								}
							}

							// Add the caption
							if ( $caption != '' ) {
								$new_link = sprintf($caption, $new_link);
							}
						}
						$link['new_link'] = $new_link;
					}

					// Reinsert the converted medias links
					$content = preg_replace_callback('#__fg_link_(\d+)__#', array($this, 'restore_links'), $content);
				}
			}
			return $content;
		}

		/**
		 * Remove all the links from the content and replace them with a specific tag
		 * 
		 * @param array $matches Result of the preg_match
		 * @return string Replacement
		 */
		private function remove_links($matches) {
			$this->post_link[] = array('old_link' => $matches[0]);
			return '__fg_link_' . $this->post_link_count++ . '__';
		}

		/**
		 * Restore the links in the content and replace them with the new calculated link
		 * 
		 * @param array $matches Result of the preg_match
		 * @return string Replacement
		 */
		private function restore_links($matches) {
			$link = $this->post_link[$matches[1]];
			$new_link = array_key_exists('new_link', $link)? $link['new_link'] : $link['old_link'];
			return $new_link;
		}

		/**
		 * Add a link between a media and a post (parent id + thumbnail)
		 *
		 * @param int $post_id Post ID
		 * @param array $post_media Post medias
		 * @param array $date Date
		 * @param boolean $set_featured_image Set the featured image?
		 */
		public function add_post_media($post_id, $post_media, $date, $set_featured_image=true) {
			$thumbnail_is_set = false;
			if ( is_array($post_media) ) {
				foreach ( $post_media as $media ) {
					$attachment = get_post($media);
					if ( !empty($attachment) && ($attachment->post_type == 'attachment') ) {
						$attachment->post_parent = $post_id; // Attach the post to the media
						$attachment->post_date = $date ;// Define the media's date
						wp_update_post($attachment);

						// Set the featured image. If not defined, it is the first image of the content.
						if ( $set_featured_image && !$thumbnail_is_set ) {
							set_post_thumbnail($post_id, $attachment->ID);
							$thumbnail_is_set = true;
						}
					}
				}
			}
		}

		/**
		 * Get the IDs of the medias
		 *
		 * @param array $post_media Post medias
		 * @return array Array of attachment IDs
		 */
		public function get_attachment_ids($post_media) {
			$attachments_ids = array();
			if ( is_array($post_media) ) {
				foreach ( $post_media as $media ) {
					$attachment = $this->get_attachment_from_name($media['name']);
					if ( !empty($attachment) ) {
						$attachments_ids[] = $attachment->ID;
					}
				}
			}
			return $attachments_ids;
		}
		
		/**
		 * Copy a remote file
		 * in replacement of the copy function
		 * 
		 * @param string $url URL of the source file
		 * @param string $path destination file
		 * @return boolean
		 */
		public function remote_copy($url, $path) {

			/*
			 * cwg enhancement: if destination already exists, just return true
			 *  this allows rebuilding the wp media db without moving files
			 */
			if ( !$this->plugin_options['force_media_import'] && file_exists($path) && (filesize($path) > 0) ) {
				return true;
			}

			$response = wp_remote_get($url, array(
				'timeout'     => $this->plugin_options['timeout'],
			)); // Uses WordPress HTTP API

			if ( is_wp_error($response) ) {
				trigger_error($response->get_error_message(), E_USER_WARNING);
				return false;
			} elseif ( $response['response']['code'] != 200 ) {
				trigger_error($response['response']['message'], E_USER_WARNING);
				return false;
			} else {
				file_put_contents($path, wp_remote_retrieve_body($response));
				return true;
			}
		}

		/**
		 * Allow the backorders or not
		 * 
		 * @param int $out_of_stock_value Out of stock value 0|1|2
		 * @return string yes|no
		 */
		public function allow_backorders($out_of_stock_value) {
			switch ( $out_of_stock_value ) {
				case 0: $backorders = 'no'; break;
				case 1: $backorders = 'yes'; break;
				case 2: $backorders = 'notify'; break;
				default: $backorders = $this->default_backorders;
			}
			return $backorders;
		}
		
		/**
		 * Recount the items for a taxonomy
		 * 
		 * @return boolean
		 */
		private function terms_tax_count($taxonomy) {
			$terms = get_terms(array($taxonomy));
			// Get the term taxonomies
			$terms_taxonomies = array();
			foreach ( $terms as $term ) {
				$terms_taxonomies[] = $term->term_taxonomy_id;
			}
			if ( !empty($terms_taxonomies) ) {
				return wp_update_term_count_now($terms_taxonomies, $taxonomy);
			} else {
				return true;
			}
		}

		/**
		 * Recount the items for each category and tag
		 * 
		 * @return boolean
		 */
		private function terms_count() {
			$result = $this->terms_tax_count('category');
			$result |= $this->terms_tax_count('post_tag');
		}

		/**
		 * Get the next post autoincrement
		 * 
		 * @return int post ID
		 */
		private function get_next_post_autoincrement() {
			global $wpdb;

			$sql = "SHOW TABLE STATUS LIKE '$wpdb->posts'";
			$row = $wpdb->get_row($sql);
			if ( $row ) {
				return $row->Auto_increment;
			} else {
				return 0;
			}
		}

		/**
		 * Guess the Magento version
		 *
		 * @return string Magento version
		 */
		private function magento_version() {
			$core_resource = $this->get_core_resource();
			$version = isset($core_resource['catalog_setup'])? $core_resource['catalog_setup'] : '0.0';
			return $version;
		}
		
		/**
		 * Get the Magento core_resource data
		 * 
		 * @since 1.12.1
		 * 
		 * @return string Version
		 */
		private function get_core_resource() {
			$core_resource = array();
			$prefix = $this->plugin_options['prefix'];
			$sql = "
				SELECT c.code, c.version
				FROM ${prefix}core_resource c
			";
			$result = $this->magento_query($sql);
			foreach ( $result as $row ) {
				$core_resource[$row['code']] = $row['version'];
			}
			return $core_resource;
		}

		/**
		 * Returns the imported products mapped with their Magento ID
		 *
		 * @since      1.10.0
		 * 
		 * @return array of post IDs [magento_product_id => wordpress_post_id]
		 */
		public function get_imported_magento_products() {
			global $wpdb;
			$posts = array();

			$sql = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_fgm2wc_old_product_id'";
			$results = $wpdb->get_results($sql);
			foreach ( $results as $result ) {
				$posts[$result->meta_value] = $result->post_id;
			}
			ksort($posts);
			return $posts;
		}

		/**
		 * Returns the imported users mapped with their Magento ID
		 *
		 * @return array of user IDs [magento_user_id => wordpress_user_id]
		 */
		public function get_imported_magento_users() {
			global $wpdb;
			$users = array();

			$sql = "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'magento_user_id'";
			$results = $wpdb->get_results($sql);
			foreach ( $results as $result ) {
				$users[$result->meta_value] = $result->user_id;
			}
			ksort($users);
			return $users;
		}

		/**
		 * Display the number of imported media
		 * 
		 */
		public function display_media_count() {
			$this->display_admin_notice(sprintf(_n('%d media imported', '%d medias imported', $this->media_count, 'fg-magento-to-woocommerce'), $this->media_count));
		}

		/**
		 * Test if a column exists
		 *
		 * @param string $table Table name
		 * @param string $column Column name
		 * @return bool
		 */
		public function column_exists($table, $column) {
			global $magento_db;

			try {
				$prefix = $this->plugin_options['prefix'];
				$sql = "SHOW COLUMNS FROM ${prefix}${table} LIKE '$column'";
				$query = $magento_db->query($sql, PDO::FETCH_ASSOC);
				if ( $query !== false ) {
					$result = $query->fetch();
					return !empty($result);
				} else {
					return false;
				}
			} catch ( PDOException $e ) {}
			return false;
		}

		/**
		 * Test if a table exists
		 *
		 * @param string $table Table name
		 * @return bool
		 */
		public function table_exists($table) {
			global $magento_db;

			try {
				$prefix = $this->plugin_options['prefix'];
				$sql = "SHOW TABLES LIKE '${prefix}${table}'";
				$query = $magento_db->query($sql, PDO::FETCH_ASSOC);
				if ( $query !== false ) {
					$result = $query->fetch();
					return !empty($result);
				} else {
					return false;
				}
			} catch ( PDOException $e ) {}
			return false;
		}

		/**
		 * Returns the imported product ID corresponding to a Magento ID
		 *
		 * @since 2.3.0
		 * 
		 * @param int $magento_id Magento article ID
		 * @return int WordPress product ID
		 */
		public function get_wp_product_id_from_magento_id($magento_id) {
			$product_id = $this->get_wp_post_id_from_meta('_fgm2wc_old_product_id', $magento_id);
			return $product_id;
		}

		/**
		 * Returns the imported post ID corresponding to a meta key and value
		 *
		 * @since 2.3.0
		 * 
		 * @param string $meta_key Meta key
		 * @param string $meta_value Meta value
		 * @return int WordPress post ID
		 */
		public function get_wp_post_id_from_meta($meta_key, $meta_value) {
			global $wpdb;

			$sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '$meta_key' AND meta_value = '$meta_value' LIMIT 1";
			$post_id = $wpdb->get_var($sql);
			return $post_id;
		}

		/**
		 * Get all the term metas corresponding to a meta key
		 * 
		 * @param string $meta_key Meta key
		 * @return array List of term metas: term_id => meta_value
		 */
		public function get_term_metas_by_metakey($meta_key) {
			global $wpdb;
			$metas = array();
			
			$sql = "SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = '$meta_key'";
			$results = $wpdb->get_results($sql);
			foreach ( $results as $result ) {
				$metas[$result->meta_value] = $result->term_id;
			}
			ksort($metas);
			return $metas;
		}
		
		/**
		 * Get all the term metas corresponding to a meta key
		 * 
		 * @param string $meta_key Meta key
		 * @return array List of term metas: term_id => meta_value
		 */
		public function get_term_metas_by_metakey_like($meta_key) {
			global $wpdb;
			$metas = array();
			
			$sql = "SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key LIKE '$meta_key'";
			$results = $wpdb->get_results($sql);
			foreach ( $results as $result ) {
				$metas[$result->meta_value] = $result->term_id;
			}
			ksort($metas);
			return $metas;
		}
		
		/**
		 * Search a term by its slug (LIKE search)
		 * 
		 * @param string $slug slug
		 * @return int Term id
		 */
		public function get_term_id_by_slug($slug) {
			global $wpdb;
			return $wpdb->get_var("
				SELECT term_id FROM $wpdb->terms
				WHERE slug LIKE '$slug'
			");
		}

		/**
		 * Stop the import
		 * 
		 * @since 2.0.0
		 * 
		 * @return boolean true: stop the import
		 */
		public function stop_import() {
			update_option('fgm2wc_stop_import', true);
		}
		
		/**
		 * Test if the import needs to stop
		 * 
		 * @since 2.0.0
		 * 
		 * @return boolean Import needs to stop or not
		 */
		public function import_stopped() {
			return get_option('fgm2wc_stop_import');
		}
		
	}
}
