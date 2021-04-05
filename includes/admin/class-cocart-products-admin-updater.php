<?php
/**
 * CoCart Products - Plugin Updater.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Products/Admin/Updater
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If CoCart Pro Updater does not exist then CoCart Pro is not installed.
if ( ! class_exists( 'CoCart_Pro_Updater' ) ) {
	return;
}

if ( ! class_exists( 'CoCart_Products_Updater' ) ) {

	class CoCart_Products_Updater {

		/**
		 * Updater Configuration
		 *
		 * @access private
		 * @var    array
		 */
		private $config = array();

		/**
		 * Plugin API URL
		 *
		 * @access private
		 * @var    string
		 */
		private $api_url = 'https://download.cocart.xyz/';

		/**
		 * Constructor
		 *
		 * @access public
		 * @static
		 */
		public function __construct() {
			$this->config = array(
				'file'               => COCART_PRODUCTS_FILE,
				'version'            => COCART_PRODUCTS_VERSION,
				'slug'               => COCART_PRODUCTS_SLUG,
				'proper_folder_name' => COCART_PRODUCTS_SLUG,
			);

			// Hooks into the plugin updater and checks for updates for CoCart Products.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );

			// Excludes CoCart Products from WP.org updates.
			add_filter( 'http_request_args', array( $this, 'exclude_plugin_from_update_check' ), 5, 2 );

			// Hack the returned object before returning plugin information.
			add_filter( 'plugins_api', array( $this, 'force_plugin_info' ), 10, 3 );

			// Hook into the plugin install process to provide plugin information.
			add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );

			// Clear update transients when the user clicks the "Check Again" button from the update screen.
			add_action( 'current_screen', array( $this, 'check_again_clear_transients' ) );

			// Auto update CoCart Products.
			add_filter( 'auto_update_plugin', array( $this, 'auto_update_plugin' ), 100, 2 );

			// Add after_plugin_row... action for CoCart Products.
			add_action( 'after_plugin_row_' . plugin_basename( $this->config['file'] ), array( $this, 'plugin_row' ), 11, 2 );
		} // END __construct()

		/**
		 * Flush the update cache.
		 *
		 * @access public
		 */
		public function flush_update_cache() {
			delete_site_transient( 'update_plugins' ); // Clear all plugin update data.
			delete_site_transient( md5( $this->config['slug'] ) . '_latest' ); // Clear latest release.
			delete_site_transient( md5( $this->config['slug'] ) . '_timeout' ); // Clear timeout if any.
		} // END flush_update_cache()

		/**
		 * Clear update transients when the user clicks the "Check Again" button from the update screen.
		 *
		 * @param object $current_screen
		 */
		public function check_again_clear_transients( $current_screen ) {
			if ( ! isset( $current_screen->id ) || strpos( $current_screen->id, 'update-core' ) === false || ! isset( $_GET['force-check'] ) ) {
				return;
			}

			$this->flush_update_cache();
		} // END check_again_clear_transients()

		/**
		 * Enable auto updates for CoCart Products if latest release supports
		 * the current installed version of WooCommerce.
		 *
		 * @access public
		 * @param  bool   $should_update Should this plugin auto update.
		 * @param  object $plugin Plugin being checked.
		 * @return bool   $should_update Returns the new status if plugin should auto update.
		 */
		public function auto_update_plugin( $should_update, $plugin ) {
			if ( ! isset( $plugin->slug ) ) {
				return $should_update;
			}

			// If the plugin is not CoCart Products then just return original status.
			if ( $this->config['file'] !== $plugin->plugin ) {
				return $should_update;
			}

			/**
			 * Check to see if the current installed WooCommerce version
			 * is less than a version required or more than a tested up to.
			 */
			if (
				version_compare( WC_VERSION, $this->config['wc_requires'], '<' ) ||
				version_compare( WC_VERSION, $this->config['wc_tested_up_to'], '>' )
			) {
				return false;
			}

			/**
			 * Developers can disable CoCart Products from auto-updating by filtering the status.
			 * Currently set to "true" at this stage.
			 */
			$should_update = apply_filters( 'cocart_products_auto_update', true );

			return $should_update;
		} // END auto_update_plugin()

		/**
		 * Notifies the user on the plugins table whether or
		 * not auto updates are enabled for CoCart Products.
		 *
		 * @access public
		 * @param  string $column_name
		 * @param  string $plugin_file
		 * @param  array  $plugin_data
		 * @return void
		 */
		public function wp_autoupdates_plugin_column( $column_name, $plugin_file, $plugin_data ) {
			if ( ! current_user_can( 'update_plugins' ) || ! wp_autoupdates_is_plugins_auto_update_enabled() ) {
				return;
			}

			if ( is_multisite() && ! is_network_admin() ) {
				return;
			}

			if ( 'autoupdates_column' !== $column_name ) {
				return;
			}

			if ( $column_name === 'autoupdates_column' ) {

				// Check the plugin is CoCart Products only.
				if ( $plugin_file === plugin_basename( $this->config['file'] ) ) {

					// If filtered to disable then notify user auto updates are disabled.
					if ( has_filter( 'cocart_products_auto_update' ) ) {
						echo '<span class="plugin-autoupdate-disabled"><span class="dashicons dashicons-update" aria-hidden="true"></span> ' . __( 'Automatic updates disabled!', 'cocart-products' ) . ' </span>';
					} else {
						// TODO: Add licence status checker here.

						/*
						if ( ! is_cocart_licence_valid() ) {
							echo '<span class="plugin-autoupdate-disabled"><span class="dashicons dashicons-update" aria-hidden="true"></span> ' . __( 'Updates available if licence renewed.', 'cocart-products' ) . ' </span>';
						} else {*/
							echo '<span class="plugin-autoupdate-enabled"><span class="dashicons dashicons-update" aria-hidden="true"></span> ' . __( 'Automatic updates enabled!', 'cocart-products' ) . '</span>';

							echo '<br />';

							// Display next scheduled update.
							$next_update_time = wp_next_scheduled( 'wp_version_check' );

							$time_to_next_update = human_time_diff( intval( $next_update_time ) );

							$plugins_updates = get_site_transient( 'update_plugins' );

						if ( isset( $plugins_updates->response[ $plugin_file ] ) ) {
							echo sprintf(
								/* translators: Time until the next update. */
								__( 'Update scheduled in %s', 'cocart-products' ),
								$time_to_next_update
							);
						}
						// }
					}
				}
			}
		} // END wp_autoupdates_plugin_column()

		/**
		 * Disables WordPress plugin auto updates actions in the plugins table.
		 *
		 * @access public
		 * @param  bool   $status
		 * @param  string $plugin_file
		 * @return bool   $status
		 */
		public function disable_auto_updates_action( $status, $plugin_file ) {
			if ( $plugin_file === plugin_basename( $this->config['file'] ) ) {
				return false;
			}

			return $status;
		} // END disable_auto_updates_action()

		/**
		 * Removes CoCart Products from plugin bulk action for auto updates.
		 *
		 * @access protected
		 * @param  array $plugins - List of plugins selected to enable or disable auto updates in bulk.
		 * @return array $plugins - Updated list of plugins to enable or disable auto updates in bulk.
		 */
		protected function remove_bulk_autoupdate( $plugins ) {
			unset( $plugins['cocart-products'] );

			return $plugins;
		} // END remove_bulk_autoupdate()

		/**
		 * Get the update information and set data.
		 *
		 * @access public
		 * @return array
		 */
		public function get_update_data() {
			$plugin_data = $this->get_plugin_data();

			$this->get_update(); // Check for updates.

			$this->config['plugin_name']     = $plugin_data['Name'];
			$this->config['description']     = $plugin_data['Description'];
			$this->config['version']         = $plugin_data['Version'];
			$this->config['author']          = $plugin_data['Author'];
			$this->config['homepage']        = $plugin_data['PluginURI'];
			$this->config['new_version']     = str_replace( 'v', '', $this->get_latest_release() );
			$this->config['requires']        = $this->get_requirement( 'requires' );
			$this->config['tested']          = $this->get_requirement( 'tested' );
			$this->config['requires_php']    = $this->get_requirement( 'requires_php' );
			$this->config['wc_requires']     = $this->get_requirement( 'wc_requires' );
			$this->config['wc_tested_up_to'] = $this->get_requirement( 'wc_tested_up_to' );
			$this->config['last_updated']    = $this->get_date();
			$this->config['changelog']       = $this->get_changelog();
			$this->config['faq']             = $this->get_faq();
			$this->config['zip_name']        = $this->get_latest_release();
			$this->config['zip_url']         = $this->get_zip( $this->config['zip_name'] );
		} // END get_update_data()

		/**
		 * Gets the requested version of the plugin package to download.
		 *
		 * @access public
		 * @param  string $version
		 * @return string
		 */
		public function get_zip( $version ) {
			// TODO: Add licence key for extra validation.

			return add_query_arg(
				array(
					'release' => $version,
				),
				$this->api_url
			);
		} // END get_zip()

		/**
		 * Get New Version.
		 *
		 * @access public
		 * @return int $tagged_version the version number
		 */
		public function get_latest_release() {
			$tagged_version = '';

			$release = $this->get_update();

			if ( ! empty( $release ) ) {
				$tagged_version = isset( $release['version'] ) ? $release['version'] : COCART_PRODUCTS_VERSION;
			}

			return $tagged_version;
		} // END get_latest_release()

		/**
		 * Get Published date of New Version.
		 *
		 * @access public
		 * @return string $published_date of the latest release
		 */
		public function get_latest_release_date() {
			$published_date = '';

			$release = $this->get_update();

			if ( ! empty( $release ) ) {
				$published_date = isset( $release['published_at'] ) ? $release['published_at'] : '';
			}

			return $published_date;
		} // END get_latest_release_date()

		/**
		 * Get Changelog of New Version.
		 *
		 * @access public
		 * @return string $changelog of the latest release
		 */
		public function get_latest_release_changelog() {
			$changelog = '';

			$release = $this->get_update();

			if ( ! empty( $release ) ) {
				$changelog = isset( $release['sections'] ) ? $release['sections']['changelog'] : '';
			}

			return $changelog;
		} // END get_latest_release_changelog()

		/**
		 * Gets the latest update information.
		 *
		 * @access public
		 * @return array $update - Returns details of the latest update if any.
		 */
		public function get_update() {
			$update  = get_site_transient( md5( $this->config['slug'] ) . '_latest' );
			$timeout = get_site_transient( md5( $this->config['slug'] ) . '_timeout' );

			/**
			 * If an update has not been saved before and there is no
			 * timeout set from the last check then check for an update.
			 */
			if ( empty( $update ) && empty( $timeout ) ) {
				$response = wp_remote_get(
					add_query_arg( array( 'get-update' => true ), $this->api_url ),
					array(
						'timeout' => 30,
					)
				);

				if ( is_wp_error( $response ) ) {
					// Set a 1 hour timeout to prevent constant checking if an error occurred.
					set_site_transient( md5( $this->config['slug'] ) . '_timeout', '1', 60 * 60 );

					return false;
				} else {
					$update = json_decode( wp_remote_retrieve_body( $response ), true );

					// Return just the plugin info should it return a 404 error or is not valid data.
					if ( wp_remote_retrieve_response_code( $response ) == '404' || ! is_object( $update ) && ! is_array( $update ) ) {
						$update = array(
							'version' => COCART_PRODUCTS_VERSION,
						);
					}
				}

				// Save update response.
				set_site_transient( md5( $this->config['slug'] ) . '_latest', $update, 60 * 60 * 6 );
			}

			return $update;
		} // END get_update()

		/**
		 * Get update date.
		 *
		 * @access public
		 * @return string $_date the date
		 */
		public function get_date() {
			$_date = $this->get_latest_release_date();

			$date = ! empty( $_date ) ? $_date : date( 'd m y h:i:s A' );

			return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
		} // END get_date()

		/**
		 * Get plugin changelog.
		 *
		 * @access public
		 * @return string $_changelog the changelog of the release
		 */
		public function get_changelog() {
			$_changelog = $this->get_latest_release_changelog();
			return ! empty( $_changelog ) ? $_changelog : __( 'Unable to return the changelog at this time.', 'cocart-products' );
		} // END get_changelog()

		/**
		 * Get plugin FAQ.
		 *
		 * @access public
		 * @return string
		 */
		public function get_faq() {
			return sprintf( __( 'The documentation for CoCart Products is %1$shere%2$s.', 'cocart-products' ), '<a href="' . COCART_PRODUCTS_DOCUMENTATION_URL . '" target="_blank">', '</a>' );
		} // END get_faq()

		/**
		 * Get Plugin data.
		 *
		 * @access public
		 * @return object $data the data
		 */
		public function get_plugin_data() {
			return get_plugin_data( WP_PLUGIN_DIR . '/' . plugin_basename( $this->config['file'] ) );
		} // END get_plugin_data()

		/**
		 * Hooks into the plugin updater and checks for updates.
		 *
		 * @access  public
		 * @param   object $transient the plugin data transient
		 * @return  object $transient updated plugin data transient
		 */
		public function api_check( $transient ) {
			// If no plugins have been checked then return its value without hacking it.
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			/**
			 * Clear our transient if we have debug enabled.
			 * This will allow the API to check fresh every time.
			 */
			if ( WP_DEBUG ) {
				delete_site_transient( md5( $this->config['slug'] ) . '_latest' );
			}

			// Get Update data.
			$this->get_update_data();

			// Filename.
			$filename = plugin_basename( $this->config['file'] );

			$data = array(
				'id'             => $this->config['slug'],
				'slug'           => $this->config['slug'],
				'plugin'         => $filename,
				'new_version'    => $this->config['new_version'],
				'requires'       => $this->config['requires'],
				'tested'         => $this->config['tested'],
				'requires_php'   => $this->config['requires_php'],
				'url'            => $this->config['homepage'],
				'package'        => '',
				'icons'          => array(
					'2x' => esc_url( trailingslashit( $this->api_url ) . 'updater/icon-256x256.jpg' ),
					'1x' => esc_url( trailingslashit( $this->api_url ) . 'updater/icon-128x128.jpg' ),
				),
				'banners'        => array(
					'low'  => esc_url( trailingslashit( $this->api_url ) . 'updater/banner-772x250.jpg' ),
					'high' => esc_url( trailingslashit( $this->api_url ) . 'updater/banner-1544x500.jpg' ),
				),
				'upgrade_notice' => '',
			);

			// Only set the package if the user is approved to download the update.
			if ( $this->allow_update() ) {
				$data['package'] = $this->config['zip_url'];
			}

			// If a new version exists then return data, otherwise... unset data.
			if ( version_compare( $this->config['version'], $this->config['new_version'], '<' ) ) {
				$transient->response[ $filename ] = (object) $data;
				unset( $transient->no_update[ $filename ] );
			} else {
				$transient->no_update[ $filename ] = (object) $data;
				unset( $transient->response[ $filename ] );
			}

			return $transient;
		} // END api_check()

		/**
		 * Determines if the user is allowed to update the plugin including pre-releases.
		 *
		 * @access public
		 * @return bool  $allow - Returns true if user can update.
		 */
		public function allow_update() {
			// If the user does not have the ability to update plugins then return false.
			if ( ! current_user_can( 'update_plugins' ) ) {
				return false;
			}

			$allow = false;

			// Check if its a beta release or a pre-release candidate.
			$is_beta_rc = $this->is_stable_version( $this->config['new_version'] );

			$allow_prereleases = $this->allow_prereleases();

			// If we allow beta's or pre-releases then return true.
			if ( ! $is_beta_rc && $allow_prereleases ) {
				$allow = true;
			}

			// Check that the new version is stable.
			if ( $this->is_stable_version( $this->config['new_version'] ) ) {
				$allow = true;
			}

			/**
			 * Check to see if the current installed WooCommerce version
			 * is less than a version required or more than a tested up to.
			 */
			if (
				version_compare( WC_VERSION, $this->config['wc_requires'], '<' ) ||
				version_compare( WC_VERSION, $this->config['wc_tested_up_to'], '>' )
			) {
				$allow = false;
			}

			// TODO: Add licence checker here!

			return $allow;
		} // END allow_update()

		/**
		 * Returns the status of allowing pre-release updates.
		 *
		 * @access public
		 * @return bool
		 */
		public function allow_prereleases() {
			return apply_filters( 'cocart_products_allow_prereleases', false );
		} // END allow_prereleases()

		/**
		 * Plugin information callback for CoCart Products.
		 *
		 * @access  public
		 * @param   object $response The response core needs to display the modal.
		 * @param   string $action   The requested plugins_api() action.
		 * @param   object $args     Arguments passed to plugins_api().
		 * @return  object $response An updated $response.
		 */
		public function get_plugin_info( $response, $action, $args ) {
			// Check that we are getting plugin information.
			if ( 'plugin_information' !== $action ) {
				return $response;
			}

			// Check if this call for the API is for the right plugin.
			if ( ! isset( $args->slug ) || $args->slug != $this->config['slug'] ) {
				return $response;
			}

			// Get Update data
			$this->get_update_data();

			// New Version
			$new_version = $this->config['new_version'];

			// Prepare warning!
			$warning = '';

			// Only show warnings if user allows pre-releases.
			if ( $this->allow_prereleases() ) {
				if ( $this->is_stable_version( $new_version ) ) {
					$warning = sprintf( __( '%1$s%3$sThis is the latest stable release%3$s%2$s', 'cocart-products' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
				}

				if ( $this->is_beta_version( $new_version ) ) {
					$warning = sprintf( __( '%1$s%3$sThis is a beta release%3$s%2$s', 'cocart-products' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
				}

				if ( $this->is_rc_version( $new_version ) ) {
					$warning = sprintf( __( '%1$s%3$sThis is a pre-release%3$s%2$s', 'cocart-products' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
				}
			}

			/**
			 * Check to see if the current installed WooCommerce version
			 * is less than tested up to.
			 */
			if ( version_compare( WC_VERSION, $this->config['wc_tested_up_to'], '<' ) ) {
				$warning .= '<p><strong>' . sprintf( __( '%1$sThe version of WooCommerce you have installed has not yet been tested with this release.%1$s', 'cocart-products' ), '<span>&#9888;</span>' ) . '</strong></p>';
			}

			// Update the results to return.
			$response->name            = $this->config['plugin_name'];
			$response->plugin_name     = $this->config['plugin_name'];
			$response->version         = $new_version;
			$response->author          = $this->config['author'];
			$response->author_homepage = 'https://cocart.xyz';
			$response->homepage        = $this->config['homepage'];
			$response->requires        = $this->config['requires'];
			$response->tested          = $this->config['tested'];
			$response->requires_php    = $this->config['requires_php'];
			$response->last_updated    = $this->config['last_updated'];
			$response->slug            = $this->config['slug'];
			$response->plugin          = $this->config['slug'];

			// Sections
			$response->sections = array(
				'description' => $this->config['description'],
				'changelog'   => $this->config['changelog'],
				'faq'         => $this->config['faq'],
			);

			$response->contributors = array(
				'sebd86' => array(
					'display_name' => 'Sébastien Dumont',
					'profile'      => esc_url( 'https://sebastiendumont.com' ),
					'avatar'       => get_avatar_url(
						'mailme@sebastiendumont.com',
						array(
							'size' => '36',
						)
					),
				),
			);

			// Add WordPress dot org banners for recognition.
			$response->banners = array(
				'low'  => esc_url( trailingslashit( $this->api_url ) . 'updater/banner-772x250.jpg' ),
				'high' => esc_url( trailingslashit( $this->api_url ) . 'updater/banner-1544x500.jpg' ),
			);

			// Apply warning to all sections if any.
			foreach ( $response->sections as $key => $section ) {
				$response->sections[ $key ] = $warning . $section;
			}

			// If the new version is no different than the one installed then reset version.
			if ( version_compare( $this->config['version'], $new_version, '=' ) ) {
				$response->version = $this->config['version'];
			}

			// Check if the user is allowed to update the plugin.
			if ( $this->allow_update() ) {
				$response->download_link = $this->config['zip_url'];
			}

			return $response;
		} // END get_plugin_info()

		/**
		 * Return true if version string is a beta version.
		 *
		 * @access protected
		 * @static
		 * @param  string $version_str Version string.
		 * @return bool
		 */
		protected static function is_beta_version( $version_str ) {
			return strpos( $version_str, 'beta' ) !== false;
		} // END is_beta_version()

		/**
		 * Return true if version string is a Release Candidate.
		 *
		 * @access protected
		 * @static
		 * @param  string $version_str Version string.
		 * @return bool
		 */
		protected static function is_rc_version( $version_str ) {
			return strpos( $version_str, 'rc' ) !== false;
		} // END is_rc_version()

		/**
		 * Return true if version string is a stable version.
		 *
		 * @access protected
		 * @static
		 * @param  string $version_str Version string.
		 * @return bool
		 */
		protected static function is_stable_version( $version_str ) {
			return ! self::is_beta_version( $version_str ) && ! self::is_rc_version( $version_str );
		} // END is_stable_version()

		/**
		 * Gets the plugin requirements from the latest update.
		 *
		 * @access public
		 * @param  string $value - The requirement to return.
		 * @return string $requirement
		 */
		public function get_requirement( $value ) {
			$release = $this->get_update();

			switch ( $value ) {
				case 'requires':
					$requirement = isset( $release['requires'] ) ? $release['requires'] : '';
					break;
				case 'tested':
					$requirement = isset( $release['tested'] ) ? $release['tested'] : '';
					break;
				case 'requires_php':
					$requirement = isset( $release['requires_php'] ) ? $release['requires_php'] : '';
					break;
				case 'wc_requires':
					$requirement = isset( $release['wc_requires'] ) ? $release['wc_requires'] : '';
					break;
				case 'wc_tested_up_to':
					$requirement = isset( $release['wc_tested_up_to'] ) ? $release['wc_tested_up_to'] : '';
					break;
			}

			return $requirement;
		} // END get_requirement()

		/**
		 * Displays update information for CoCart Products.
		 *
		 * @access public
		 * @param  string $file        Plugin basename.
		 * @param  array  $plugin_data Plugin information.
		 * @return false|void
		 */
		public function plugin_row( $file, $plugin_data ) {
			$current = get_site_transient( 'update_plugins' );

			if ( ! isset( $current->response[ $file ] ) ) {
				return false;
			}

			$response = $current->response[ $file ];

			$plugins_allowedtags = array(
				'a'       => array(
					'href'  => array(),
					'title' => array(),
				),
				'abbr'    => array( 'title' => array() ),
				'acronym' => array( 'title' => array() ),
				'code'    => array(),
				'em'      => array(),
				'strong'  => array(),
			);

			$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $response->slug . '&section=changelog&TB_iframe=true&width=600&height=800' );

			if ( is_network_admin() || ! is_multisite() ) {
				if ( is_network_admin() ) {
					$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
				} else {
					$active_class = is_plugin_active( $file ) ? ' active' : '';
				}

				$requires_php   = isset( $response->requires_php ) ? $response->requires_php : null;
				$compatible_php = is_php_version_compatible( $requires_php );
				$notice_type    = $compatible_php ? 'notice-warning' : 'notice-error';

				echo '<tr class="plugin-update-tr' . $active_class . ' cocart-products-custom" id="' . esc_attr( $response->slug . '-update' ) . '" data-slug="' . esc_attr( $response->slug ) . '" data-plugin="' . esc_attr( $file ) . '"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline ' . $notice_type . ' notice-alt"><p>';

				if ( ! current_user_can( 'update_plugins' ) ) {
					/* translators: 1: plugin name, 2: details URL, 3: additional link attributes, 4: version number */
					printf(
						__( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.', 'cocart-products' ),
						$plugin_name,
						esc_url( $details_url ),
						sprintf(
							'class="thickbox open-plugin-details-modal" aria-label="%s"',
							/* translators: 1: plugin name, 2: version number */
							esc_attr( sprintf( __( 'View %1$s version %2$s details', 'cocart-products' ), $plugin_name, $response->new_version ) )
						),
						esc_attr( $response->new_version )
					);
				} elseif ( empty( $response->package ) ) {
					/* translators: 1: plugin name, 2: details URL, 3: additional link attributes, 4: version number */
					printf(
						__( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>. <em>Automatic update is unavailable for this plugin.</em>', 'cocart-products' ),
						$plugin_name,
						esc_url( $details_url ),
						sprintf(
							'class="thickbox open-plugin-details-modal" aria-label="%s"',
							/* translators: 1: plugin name, 2: version number */
							esc_attr( sprintf( __( 'View %1$s version %2$s details', 'cocart-products' ), $plugin_name, $response->new_version ) )
						),
						esc_attr( $response->new_version )
					);
				} else {
					if ( $compatible_php ) {
						/* translators: 1: plugin name, 2: details URL, 3: additional link attributes, 4: version number, 5: update URL, 6: additional link attributes */
						printf(
							__( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s" %6$s>update now</a>.', 'cocart-products' ),
							$plugin_name,
							esc_url( $details_url ),
							sprintf(
								'class="thickbox open-plugin-details-modal" aria-label="%s"',
								/* translators: 1: plugin name, 2: version number */
								esc_attr( sprintf( __( 'View %1$s version %2$s details', 'cocart-products' ), $plugin_name, $response->new_version ) )
							),
							esc_attr( $response->new_version ),
							wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ),
							sprintf(
								'class="update-link" aria-label="%s"',
								/* translators: %s: plugin name */
								esc_attr( sprintf( __( 'Update %s now', 'cocart-products' ), $plugin_name ) )
							)
						);
					} else {
						/* translators: 1: plugin name, 2: details URL, 3: additional link attributes, 4: version number 5: Update PHP page URL */
						printf(
							__( 'There is a new version of %1$s available, but it doesn&#8217;t work with your version of PHP. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s">learn more about updating PHP</a>.', 'cocart-products' ),
							$plugin_name,
							esc_url( $details_url ),
							sprintf(
								'class="thickbox open-plugin-details-modal" aria-label="%s"',
								/* translators: 1: plugin name, 2: version number */
								esc_attr( sprintf( __( 'View %1$s version %2$s details', 'cocart-products' ), $plugin_name, $response->new_version ) )
							),
							esc_attr( $response->new_version ),
							esc_url( wp_get_update_php_url() )
						);
						wp_update_php_annotation( '<br><em>', '</em>' );
					}
				}

				echo '</p></div></td></tr>';
			}
			?>
			<script type="text/javascript">
				(function( $ ) {
					var row = jQuery( '[data-slug=<?php echo sanitize_title( $plugin_name ); ?>]:first' );

					// Fallback for earlier versions of WordPress.
					if ( ! row.length ) {
						row = jQuery( '#<?php echo $plugin_name; ?>' );
					}

					var next_row = row.next();

					// If there's a plugin update row - need to keep the original update row available so we can switch it out
					// if the user has a successful response from the 'check my license again' link
					if ( next_row.hasClass( 'plugin-update-tr' ) && !next_row.hasClass( 'cocart-products-custom' ) ) {
						var original = next_row.clone();
						original.add;
						next_row.html( next_row.next().html() ).addClass( 'cocart-products-custom-visible' );
						next_row.next().remove();
						next_row.after( original );
						original.addClass( 'cocart-products-original-update-row' ).css('display', 'none');
					}
				})( jQuery );
			</script>
			<?php
		} // END plugin_row()

		/**
		 * Excludes CoCart Products from WP.org updates.
		 *
		 * @access public
		 * @param  array  $request An array of HTTP request arguments.
		 * @param  string $url The request URL.
		 * @return array  Updated array of HTTP request arguments.
		 */
		public function exclude_plugin_from_update_check( $request, $url ) {
			if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) || ! isset( $request['body']['plugins'] ) ) {
				return $request; // Not a plugin update request. Stop immediately.
			}

			$plugins = maybe_unserialize( $request['body']['plugins'] );

			if ( isset( $plugins->plugins[ plugin_basename( $this->config['file'] ) ], $plugins->active[ array_search( plugin_basename( $this->config['file'] ), $plugins->active, true ) ] ) ) {
				unset( $plugins->plugins[ plugin_basename( $this->config['file'] ) ] );
				unset( $plugins->active[ array_search( plugin_basename( $this->config['file'] ), $plugins->active, true ) ] );
			}

			$request['body']['plugins'] = maybe_serialize( $plugins );

			return $request;
		} // END exclude_plugin_from_update_check()

		/**
		 * Hack the returned object
		 *
		 * @access public
		 * @param  false|object|array $bool The result object or array. Default false.
		 * @param  string             $action The type of information being requested from the Plugin Install API.
		 * @param  object             $args Plugin API arguments.
		 * @return false|object|array Empty object if slug is CoCart Pro, default value otherwise
		 */
		public function force_plugin_info( $bool, $action, $args ) {
			if ( 'plugin_information' === $action && $this->config['slug'] === $args->slug ) {
				return new stdClass();
			}

			return $bool;
		} // END force_plugin_info()

	} // END class

} // END if class exists

return new CoCart_Products_Updater();
