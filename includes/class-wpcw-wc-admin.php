<?php
/**
 * WP Courseware Admin Class.
 *
 * @package WPCW_WC_Addon/Includes
 * @since 1.4.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCW_WC_Admin' ) ) {
	/**
	 * Class WPCW_WC_Admin.
	 *
	 * @since 1.4.2
	 */
	class WPCW_WC_Admin {

		/**
		 * Admin Hooks.
		 *
		 * @since 1.4.2
		 */
		public function hooks() {
			add_filter( 'woocommerce_prevent_admin_access', array( $this, 'allow_instructor_admin_access' ) );
			add_filter( 'woocommerce_disable_admin_bar', array( $this, 'enable_instructor_admin_bar' ) );
		}

		/**
		 * Allow Instructor Admin Access.
		 *
		 * @since 1.4.2
		 *
		 * @param bool $prevent_access True or false to prevent access.
		 *
		 * @return bool $prevent_access True to prevent, false to allow.
		 */
		public function allow_instructor_admin_access( $prevent_access ) {
			if ( current_user_can( 'view_wpcw_courses' ) && ! apply_filters( 'wpcw_wc_addon_admin_prevent_instructor_access', false ) ) {
				$prevent_access = false;
			}

			return $prevent_access;
		}

		/**
		 * Enable Instructor Admin Bar.
		 *
		 * @since 1.4.2
		 *
		 * @param bool $show_admin_bar True or false to enale admin bar.
		 *
		 * @return bool $show_admin_bar True or false to enale admin bar.
		 */
		public function enable_instructor_admin_bar( $show_admin_bar ) {
			if ( current_user_can( 'view_wpcw_courses' ) && ! apply_filters( 'wpcw_wc_addon_admin_prevent_instructor_access', false ) ) {
				$show_admin_bar = false;
			}

			return $show_admin_bar;
		}
	}
}
