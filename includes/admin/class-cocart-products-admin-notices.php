<?php
/**
 * CoCart Products - Display notices in the WordPress admin.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Products\Admin\Notices
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Products_Admin_Notices' ) ) {

	class CoCart_Products_Admin_Notices {

		/**
		 * Activation date.
		 *
		 * @access public
		 * @static
		 * @var    string
		 */
		public static $install_date;

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			self::$install_date = get_option( 'cocart_products_install_date', time() );

			// Don't bug the user if they don't want to see any notices.
			add_action( 'admin_init', array( $this, 'dont_bug_me' ), 15 );

			// Check CoCart Products dependency.
			add_action( 'admin_init', array( $this, 'check_cocart_products_dependency' ), 12 );

			// Display other admin notices when required. All are dismissible.
			// add_action( 'admin_print_styles', array( $this, 'add_review_notice' ), 0 );
			// add_action( 'admin_print_styles', array( $this, 'add_pre_release_notice' ), 0 );
		} // END __construct()

		/**
		 * Check CoCart Products Dependency.
		 *
		 * @access public
		 */
		public function check_cocart_products_dependency() {
			// If the current user can not install plugins then return nothing!
			if ( ! CoCart_Products_Helpers::user_has_capabilities() ) {
				return false;
			}

			// Notice should only show on a CoCart page.
			if ( ! CoCart_Products_Helpers::is_cocart_admin_page() ) {
				return false;
			}

			if ( ! CoCart_Products_Helpers::is_cocart_installed() ) {
				add_action( 'admin_notices', array( $this, 'cocart_not_installed' ) );
				return false;
			} elseif ( version_compare( COCART_VERSION, CoCart_Products::$required_cocart, '<' ) ) {
				add_action( 'admin_notices', array( $this, 'required_cocart_version_failed' ) );
				return false;
			}
		} // END check_cocart_products_dependency()

		/**
		 * Don't bug the user if they don't want to see any notices.
		 *
		 * @access public
		 * @global $current_user
		 */
		public function dont_bug_me() {
			global $current_user;

			$user_hidden_notice = false;

			// If the user is allowed to install plugins and requested to hide the review notice then hide it for that user.
			if ( ! empty( $_GET['hide_cocart_products_review_notice'] ) && CoCart_Products_Helpers::user_has_capabilities() ) {
				add_user_meta( $current_user->ID, 'cocart_products_hide_review_notice', '1', true );
				$user_hidden_notice = true;
			}

			// Hide the beta notice for two weeks if requested.
			if ( ! empty( $_GET['hide_cocart_products_beta_notice'] ) && CoCart_Products_Helpers::user_has_capabilities() ) {
				set_transient( 'cocart_products_beta_notice_hidden', 'hidden', apply_filters( 'cocart_products_beta_notice_expiration', WEEK_IN_SECONDS * 2 ) );
				$user_hidden_notice = true;
			}

			// If the user is allowed to install plugins and requested to dismiss beta notice forever.
			if ( ! empty( $_GET['hide_forever_cocart_products_beta_notice'] ) && CoCart_Helpers::user_has_capabilities() ) {
				set_transient( 'cocart_products_beta_notice_hidden', 'hidden' );
				$user_hidden_notice = true;
			}

			if ( $user_hidden_notice ) {
				// Redirect to the plugins page.
				wp_safe_redirect( admin_url( 'plugins.php' ) );
				exit;
			}
		} // END dont_bug_me()

		/**
		 * Displays plugin review notice.
		 *
		 * Shown after 2 weeks or more from the time the plugin was installed.
		 *
		 * @access public
		 * @global $current_user
		 * @return void|bool
		 */
		public function add_review_notice() {
			global $current_user;

			// If the current user can not install plugins then return nothing!
			if ( ! CoCart_Products_Helpers::user_has_capabilities() ) {
				return false;
			}

			// Notice should only show on a CoCart page.
			if ( ! CoCart_Products_Helpers::is_cocart_admin_page() ) {
				return false;
			}

			// Is admin review notice hidden?
			$hide_review_notice = get_user_meta( $current_user->ID, 'cocart_products_hide_review_notice', true );

			// Check if we need to display the review plugin notice.
			if ( empty( $hide_review_notice ) ) {
				// If it has been 2 weeks or more since activating the plugin then display the review notice.
				if ( ( intval( time() - self::$install_date ) ) > WEEK_IN_SECONDS * 2 ) {
					add_action( 'admin_notices', array( $this, 'plugin_review_notice' ) );
				}
			}
		} // END add_review_notice()

		/**
		 * Displays notice if user is testing pre-release version of the plugin.
		 *
		 * @access public
		 * @global $current_user
		 * @return void|bool
		 */
		public function add_pre_release_notice() {
			global $current_user;

			// If the current user can not install plugins then return nothing!
			if ( ! CoCart_Products_Helpers::user_has_capabilities() ) {
				return false;
			}

			// Notice should only show on a CoCart page.
			if ( ! CoCart_Products_Helpers::is_cocart_admin_page() ) {
				return false;
			}

			// Is this version of CoCart Products a pre-release?
			if ( CoCart_Products_Helpers::is_cocart_products_pre_release() && empty( get_transient( 'cocart_products_beta_notice_hidden' ) ) ) {
				add_action( 'admin_notices', array( $this, 'beta_notice' ) );
			}
		} // END add_pre_release_notice()

		/**
		 * CoCart is Not Installed or Activated Notice.
		 *
		 * @access public
		 * @return void
		 */
		public function cocart_not_installed() {
			include_once COCART_PRODUCTS_FILE_PATH . '/includes/admin/views/html-notice-cocart-not-installed.php';
		} // END cocart_not_installed()

		/**
		 * Display a warning message if minimum version of CoCart check fails and
		 * provide an update button if the user has admin capabilities to update plugins.
		 *
		 * @access public
		 * @return void
		 */
		public function required_cocart_version_failed() {
			include_once COCART_PRODUCTS_FILE_PATH . '/includes/admin/views/html-notice-required-cocart.php';
		} // END required_cocart_version_failed()

		/**
		 * Show the beta notice.
		 *
		 * @access public
		 */
		public function beta_notice() {
			include_once COCART_PRODUCTS_FILE_PATH . '/includes/admin/views/html-notice-trying-beta.php';
		} // END beta_notice()

		/**
		 * Show the plugin review notice.
		 *
		 * @access public
		 */
		public function plugin_review_notice() {
			$install_date = self::$install_date;

			include_once COCART_PRODUCTS_FILE_PATH . '/includes/admin/views/html-notice-please-review.php';
		} // END plugin_review_notice()

	} // END class.

} // END if class exists.

return new CoCart_Products_Admin_Notices();
