<?php
/**
 * WP Courseware WooCommerce Menu Courses Class
 *
 * @package WPCW_WC_Addon/Includes
 * @since 1.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCW_WC_Menu_Courses' ) ) {
	/**
	 * Class WPCW_WC_Menu_Courses.
	 *
	 * @since 1.3.0
	 */
	class WPCW_WC_Menu_Courses {

		/**
		 * @var string Menu Id.
		 * @since 1.3.0
		 */
		protected $menu_id = 'wpcw_wc_addon_menu_courses_endpoint';

		/**
		 * Menu Courses Hooks.
		 *
		 * @since 1.3.1
		 */
		public function hooks() {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.3.5', '<=' ) ) {
				add_action( 'init', array( $this, 'account_menu_courses_pre_335_query_vars' ), 5 );
			}

			add_filter( 'woocommerce_get_query_vars', array( $this, 'account_menu_courses_query_vars' ) );

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.3.5', '<=' ) ) {
				add_filter( 'woocommerce_account_settings', array( $this, 'account_menu_pre_335_courses_settings_page' ) );
			} else {
				add_filter( 'woocommerce_settings_pages', array( $this, 'account_menu_courses_settings_page' ) );
			}

			add_filter( 'woocommerce_account_menu_items', array( $this, 'account_menu_courses_item' ) );
			add_filter( "woocommerce_endpoint_{$this->get_menu_query_var()}_title", array( $this, 'account_menu_courses_title' ) );
			add_filter( "wpcw_student_account_{$this->get_menu_query_var()}_title", array( $this, 'account_menu_courses_content_title' ) );

			if ( function_exists( 'wpcw_student_account_courses' ) ) {
				add_action( "woocommerce_account_{$this->get_menu_query_var()}_endpoint", 'wpcw_student_account_courses' );
			} else {
				add_action( "woocommerce_account_{$this->get_menu_query_var()}_endpoint", array( $this, 'account_menu_courses_content' ) );
			}
		}

		/**
		 * Account Menu Courses Query Vars - Pre 3.3.5.
		 *
		 * @since 1.3.1
		 */
		public function account_menu_courses_pre_335_query_vars() {
			if ( empty( WC()->query->query_vars[ $this->get_menu_query_var() ] ) ) {
				WC()->query->query_vars[ $this->get_menu_query_var() ] = $this->get_menu_slug_from_options();
			}
		}

		/**
		 * Account Menu Courses Query Vars.
		 *
		 * @since 1.3.1
		 *
		 * @param array $query_vars The WooCommerce query vars.
		 *
		 * @return array $query_vars The WooCommerce query vars.
		 */
		public function account_menu_courses_query_vars( $query_vars ) {
			if ( ! get_query_var( 'post_type' ) ) {
				$query_vars[ $this->get_menu_query_var() ] = $this->get_menu_slug_from_options();
			}

			return $query_vars;
		}

		/**
		 * Account Menu Courses Settings Page.
		 *
		 * @since 1.3.0
		 *
		 * @param array $settings The settings pages for WooCommerce.
		 *
		 * @return array $settings The settings pages for WooCommerce.
		 */
		public function account_menu_courses_settings_page( $settings ) {
			$new_settings = array();

			/**
			 * Filter: Menu Courses Settings.
			 *
			 * @since 1.3.0
			 *
			 * @param array The menu courses settings.
			 *
			 * @return array The menu courses settings.
			 */
			$courses_page_setting = apply_filters( 'wpcw_wc_addon_menu_courses_settings', array(
				'title'    => $this->get_menu_title(),
				'desc'     => esc_html__( 'Endpoint for the "Courses" page.', 'wpcw-wc-addon' ),
				'id'       => $this->get_menu_id(),
				'type'     => 'text',
				'default'  => $this->get_menu_slug(),
				'desc_tip' => true,
			) );

			/**
			 * Filter: Setting Insert After.
			 *
			 * @since 1.3.0
			 *
			 * @param string The WooCommerce setting to insert after.
			 *
			 * @param string The WooCommerce setting to insert after.
			 */
			$account_endpoint_to_insert_after = apply_filters( 'wpcw_wc_addon_menu_courses_setting_insert_after', 'woocommerce_myaccount_downloads_endpoint' );

			foreach ( $settings as $setting ) {
				$new_settings[] = $setting;

				if ( ! empty( $setting['id'] ) && $account_endpoint_to_insert_after === $setting['id'] ) {
					$new_settings[] = $courses_page_setting;
				}
			}

			$settings = ! empty( $new_settings ) ? $new_settings : $settings;

			return $settings;
		}

		/**
		 * Account Menu Courses Settings Page - Pre 3.3.0.
		 *
		 * @since 1.3.1
		 *
		 * @param array $settings The settings pages for WooCommerce.
		 *
		 * @return array $settings The settings pages for WooCommerce.
		 */
		public function account_menu_pre_335_courses_settings_page( $settings ) {
			$new_settings = array();

			/**
			 * Filter: Pre 3.3.5 - Menu Courses Settings.
			 *
			 * @since 1.3.1
			 *
			 * @param array The menu courses settings.
			 *
			 * @return array The menu courses settings.
			 */
			$courses_page_setting = apply_filters( 'wpcw_wc_addon_menu_pre_335_courses_settings', array(
				'title'    => $this->get_menu_title(),
				'desc'     => esc_html__( 'Endpoint for the "Courses" page.', 'wpcw-wc-addon' ),
				'id'       => $this->get_menu_id(),
				'type'     => 'text',
				'default'  => $this->get_menu_slug(),
				'desc_tip' => true,
			) );

			/**
			 * Filter: Pre 3.3.5 Setting Insert After.
			 *
			 * @since 1.3.1
			 *
			 * @param string The WooCommerce setting to insert after.
			 *
			 * @param string The WooCommerce setting to insert after.
			 */
			$account_endpoint_to_insert_after = apply_filters( 'wpcw_wc_addon_menu_pre_335_courses_setting_insert_after', 'woocommerce_myaccount_downloads_endpoint' );

			foreach ( $settings as $setting ) {
				$new_settings[] = $setting;

				if ( ! empty( $setting['id'] ) && $account_endpoint_to_insert_after === $setting['id'] ) {
					$new_settings[] = $courses_page_setting;
				}
			}

			$settings = ! empty( $new_settings ) ? $new_settings : $settings;

			return $settings;
		}

		/**
		 * Account Menu Courses Item.
		 *
		 * @since 1.3.1
		 *
		 * @param array $items The nav menu items.
		 *
		 * @return array $items The new nav manu items.
		 */
		public function account_menu_courses_item( $items ) {
			$new_items = array();

			/**
			 * Filter: Menu Courses Insert After Slug.
			 *
			 * This is the menu slug to insert our menu item after.
			 *
			 * @since 1.3.0
			 *
			 * @param string The menu slug. Default is 'edit-account'
			 *
			 * @return string The different menu slug.
			 */
			$menu_slug = apply_filters( 'wpcw_wc_addon_menu_courses_insert_after_slug', 'edit-account' );

			if ( ! empty( $items ) ) {
				foreach ( $items as $slug => $item ) {
					$new_items[ $slug ] = $item;

					if ( $menu_slug === $slug && ! isset( $new_items['courses'] ) ) {
						$new_menu_slug = $this->get_menu_slug_from_options();
						if ( ! empty( $new_menu_slug ) ) {
							$new_items[ $new_menu_slug ] = $this->get_menu_title();
						}
					}
				}
			}

			$items = ! empty( $new_items ) ? $new_items : $items;

			return $items;
		}

		/**
		 * Account Menu Courses Title.
		 *
		 * @since 1.3.0
		 *
		 * @param string $title The courses title.
		 *
		 * @return string $title The courses title.
		 */
		public function account_menu_courses_title( $title ) {
			$title = $this->get_menu_title();

			return $title;
		}

		/**
		 * Account Menu Courses Content Title.
		 *
		 * @since 1.3.0
		 *
		 * @param string $title The account menu courses content title.
		 *
		 * @return string $title The account menu courses content title.
		 */
		public function account_menu_courses_content_title( $title ) {
			$title = apply_filters( 'wpcw_wc_addon_account_menu_content_title', esc_html__( 'My Courses', 'wpcw-wc-addon' ) );

			return $title;
		}

		/**
		 * Account Menu Courses Content.
		 *
		 * @since 1.3.0
		 *
		 * @param int $current_page The current page of courses.
		 */
		public function account_menu_courses_content( $current_page ) {
			printf( '<h3>%s</h3>', apply_filters( 'wpcw_wc_account_menu_courses_content_title', esc_html__( 'My Courses', 'wpcw-wc-addon' ) ) );

			echo do_shortcode( '[wpcw_course_progress user_progress="true" user_grade="true"]' );
		}

		/** Getter Methods -------------------------------------- */

		/**
		 * Get Menu Id.
		 *
		 * @since 1.3.0
		 *
		 * @return string The menu id.
		 */
		public function get_menu_id() {
			return apply_filters( 'wpcw_wc_addon_menu_courses_id', $this->menu_id );
		}

		/**
		 * Get Menu Query Var.
		 *
		 * @since 1.3.9
		 *
		 * @return string The menu slug.
		 */
		public function get_menu_query_var() {
			return apply_filters( 'wpcw_wc_addon_menu_courses_query_var', 'courses' );
		}

		/**
		 * Get Menu Slug.
		 *
		 * @since 1.3.0
		 *
		 * @return string The menu slug.
		 */
		public function get_menu_slug() {
			return apply_filters( 'wpcw_wc_addon_menu_courses_slug', 'courses' );
		}

		/**
		 * Get Menu Slug from Options.
		 *
		 * @since 1.3.1
		 *
		 * @return string The menu slug from options.
		 */
		public function get_menu_slug_from_options() {
			return get_option( $this->get_menu_id(), $this->get_menu_slug() );
		}

		/**
		 * Get Menu Title.
		 *
		 * @since 1.3.0
		 *
		 * @return string The menu title.
		 */
		public function get_menu_title() {
			return apply_filters( 'wpcw_wc_addon_menu_courses_title', esc_html__( 'Courses', 'wpcw-wc-addon' ) );
		}
	}
}
