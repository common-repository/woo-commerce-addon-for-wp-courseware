<?php
/**
 * WP Courseware WooCommerce Add-on Class
 *
 * @package WPCW_WC_Addon/Includes
 * @since 1.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCW_WC_Addon' ) ) {
	/**
	 * Class WPCW_WC_Addon.
	 *
	 * @since 1.3.0
	 */
	final class WPCW_WC_Addon {

		/**
		 * @var bool Can Load Addon?
		 * @since 1.3.0
		 */
		public $can_load = false;

		/**
		 * @var WPCW_WC_Admin $admin The admin object.
		 * @since 1.4.2
		 */
		public $admin;

		/**
		 * @var WPCW_WC_Membership $membership The membership object.
		 * @since 1.3.0
		 */
		public $membership;

		/**
		 * @var WPCW_WC_Menu_Courses $menu_courses The menu courses object.
		 * @since 1.3.0
		 */
		public $menu_courses;

		/**
		 * Innitalize.
		 *
		 * @since 1.3.0
		 *
		 * @return WPCW_WC_Addon $wc_addon The addon object.
		 */
		public static function init() {
			$wc_addon = new self();

			$wc_addon->membership   = $wc_addon->load_membership();
			$wc_addon->admin        = $wc_addon->load_admin();
			$wc_addon->menu_courses = $wc_addon->load_menu_courses();

			/**
			 * Action: Initalize WooCommerce Addon.
			 *
			 * @since 1.3.0
			 *
			 * @param WPCW_WC_Addon $wc_addon The WPCW_WC_Addon object.
			 */
			do_action( 'wpcw_wc_addon_init', $wc_addon );

			return $wc_addon;
		}

		/**
		 * Load Compatability.
		 *
		 * @since 1.3.0
		 *
		 * @return null|WPCW_WC_Membership Null or WPCW_WC_Membership class object.
		 */
		public function load_membership() {
			// Load Class.
			$wc_membership = new WPCW_WC_Membership();

			// Check for WP Courseware.
			if ( ! $wc_membership->found_wpcourseware() ) {
				$wc_membership->attach_showWPCWNotDetectedMessage();

				return;
			}

			// Check for WooCommerce.
			if ( ! $wc_membership->found_membershipTool() ) {
				$wc_membership->attach_showToolNotDetectedMessage();

				return;
			}

			// Set Can Load Flag.
			$this->can_load = apply_filters( 'wpcw_wc_addon_can_load', true );

			// Attach to tools.
			$wc_membership->attachToTools();

			/**
			 * Action: Load Membership.
			 *
			 * @since 1.3.0
			 *
			 * @param WPCW_WC_Membership $wc_membership The WPCW_WC_Membership class object.
			 * @param WPCW_WC_Addon      $this The WPCW_WC_Addon class object.
			 */
			do_action( 'wpcw_wc_addon_load_membership', $wc_membership, $this );

			return $wc_membership;
		}

		/**
		 * Load Admin.
		 *
		 * @since 1.4.2
		 */
		public function load_admin() {
			if ( ! $this->can_load ) {
				return;
			}

			// Initialize Plugin.
			$wc_admin = new WPCW_WC_Admin();
			$wc_admin->hooks();

			/**
			 * Action: Load Admin.
			 *
			 * @since 1.4.2
			 *
			 * @param WPCW_WC_Admin $wc_admin The WPCW_WC_Menu_Courses class object.
			 * @param WPCW_WC_Addon $this The WPCW_WC_Addon class object.
			 */
			do_action( 'wpcw_wc_addon_load_admin', $wc_admin, $this );

			return $wc_admin;
		}

		/**
		 * Load Menu Courses.
		 *
		 * @since 1.3.0
		 *
		 * @return null|WPCW_WC_Menu_Courses Null or the WPCW_WC_Menu_Courses class object.
		 */
		public function load_menu_courses() {
			if ( ! $this->can_load ) {
				return;
			}

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			if ( is_plugin_active( 'wpcw-courses-endpoint-for-woocommerce/wpcw-courses-endpoint-for-woocommerce.php' ) ) {
				return;
			}

			// Initialize Plugin.
			$wc_menu_courses = new WPCW_WC_Menu_Courses();
			$wc_menu_courses->hooks();

			/**
			 * Action: Load Menu Courses.
			 *
			 * @since 1.3.0
			 *
			 * @param WPCW_WC_Menu_Courses $wc_menu_courses The WPCW_WC_Menu_Courses class object.
			 * @param WPCW_WC_Addon        $this The WPCW_WC_Addon class object.
			 */
			do_action( 'wpcw_wc_addon_load_menu_courses', $wc_menu_courses, $this );

			return $wc_menu_courses;
		}
	}
}
