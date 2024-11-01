<?php
/**
 * WP Courseware WooCommerce Membership Class.
 *
 * This includes all functionality of the old addon
 * that eventually will be moved into something new.
 *
 * @package WPCW_WC_Addon/Includes
 * @since 1.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCW_WC_Membership' ) ) {
	/**
	 * Class WPCW_WC_Membership.
	 *
	 * Class that handles the specifics of the WooCommerce plugin and
	 * handling the data for products for that plugin.
	 *
	 * @since 1.3.0
	 */
	class WPCW_WC_Membership extends WPCW_WC_Members {

		/**
		 * @var string Add Version.
		 * @since 1.3.0
		 */
		protected $addon_version = '1.0.0';

		/**
		 * @var string Addon Id.
		 * @since 1.3.0
		 */
		protected $addon_id = 'WPCW_Woo';

		/**
		 * @var string Addon Name.
		 * @since 1.3.0
		 */
		protected $addon_name = 'WooCommerce';

		/**
		 * @var WC_Subscription[] WooCommerce User Subscriptions.
		 * @since 1.4.0
		 */
		protected $user_subscriptions = array();

		/**
		 * WPCW_WooCommerce_Addon constructor.
		 *
		 * @since 1.3.0
		 */
		function __construct() {
			parent::__construct( $this->addon_name, $this->addon_id, $this->addon_version );
		}

		/**
		 * Get Membership Levels.
		 *
		 * @since 1.3.0
		 *
		 * @return array|bool Membership levels or false on failure.
		 */
		protected function getMembershipLevels() {
			$membership_levels = array();

			$wc_products = wc_get_products(array(
							    'numberposts' => -1,
							    'post_status' => 'published', // Only published products
							) );

			if ( $wc_products && count( $wc_products ) > 0 ) {
				foreach ( $wc_products as $wc_product ) {
					$wc_product_level         = array();
					$wc_product_level['name'] = $wc_product->get_name();
					$wc_product_level['id']   = $wc_product->get_id();
					$wc_product_level['raw']  = $wc_product;

					$membership_levels[ $wc_product_level['id'] ] = $wc_product_level;
				}
			}

			return ! empty( $membership_levels ) ? $membership_levels : false;
		}

		/**
		 * Attach Update User Course Access.
		 *
		 * Function called to attach hooks for handling when a user is updated or created.
		 *
		 * @sicne 1.3.0
		 */
		protected function attach_updateUserCourseAccess() {
			// Events called whenever the user products are changed, which updates the user access.
			add_action( 'woocommerce_order_status_processing', array( $this, 'handle_updateUserCourseAccess' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'handle_updateUserCourseAccess' ) );

			// WooCommerce Subscriptions Integration.
			if ( class_exists( 'WC_Subscriptions' ) ) {
				add_filter( 'wpcw_courses_canuseraccesscourse', array( $this, 'wc_check_wpcw_course_access' ), 10, 3 );
			}

			// WC Subscriptions: Enroll on status 'active'.
			add_action( 'woocommerce_subscription_status_active', array( $this, 'handle_wc_subscription_user_course_enrollment' ) );

			// WC Subscriptions: De-enroll on status 'on-hold', 'cancelled', and expired.
			if ( ! apply_filters( 'wpcw_woocommerce_disable_deenrollment', false ) ) {
				// add_action( 'woocommerce_subscription_status_on-hold', array( $this, 'handle_wc_subscription_user_course_de_enrollment' ) );
				add_action( 'woocommerce_subscription_status_expired', array( $this, 'handle_wc_subscription_user_course_de_enrollment' ) );
				add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'handle_wc_subscription_user_course_de_enrollment' ) );

				// WC Memberships: De-enroll on status 'expired' and 'cancelled'.
				add_action( 'wc_memberships_user_membership_status_changed', array( $this, 'handle_wc_membership_user_course_status_change' ), 10, 3 );

				// WC Teams
				add_action( 'wc_memberships_for_teams_add_team_member', array( $this, 'handle_wc_teams_membership_user_course_enrollment' ), 10, 3 );
				add_action( 'wc_memberships_for_teams_after_remove_team_member', array( $this, 'handle_wc_teams_membership_user_course_de_enrollment' ), 10, 2 );

			}
		}

		/**
		 * Assign selected courses to members of a paticular product.
		 *
		 * @since 1.3.0
		 *
		 * @param string $level_id The Level Id in which members will get courses enrollment adjusted.
		 */
		protected function retroactive_assignment( $level_id ) {
			global $wpdb;

			$page = new PageBuilder( false );

			$batch = 50;
			$step  = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
			$count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
			$steps = isset( $_GET['steps'] ) ? $_GET['steps'] : 'continue';

			$coursesToAdd = get_transient( 'wpcw_add_courses_' . $level_id );
			$coursesToRemove = get_transient( 'wpcw_remove_courses_' . $level_id );

			$summary_url = add_query_arg( array( 'page' => $this->extensionID ), admin_url( 'admin.php' ) );
			$course_url  = add_query_arg( array( 'page' => $this->extensionID, 'level_id' => $level_id ), admin_url( 'admin.php' ) );

			if ( 'finished' === $steps ) {
				$page->showMessage(
					esc_html__( 'Course access settings successfully updated.', 'wpcw-wc-addon' )
					. '<br />' .
					esc_html__( 'All existing customers were retroactively enrolled into the selected courses successfully.', 'wpcw-wc-addon' )
					. '<br /><br />' .
					/* translators: %s - Summary Url. */
					sprintf( __( 'Want to return to the <a href="%s">Course Access Settings</a>?', 'wpcw-wc-addon' ), $summary_url )
				);

				printf( '<br /><a href="%s" class="button-primary">%s</a>', $course_url, __( '&laquo; Return to Course', 'wpcw-wc-addon' ) );

				if ( $coursesToRemove ){
					delete_transient( 'wpcw_remove_courses_' . $level_id );
				}

				if ( $coursesToAdd ){
					delete_transient( 'wpcw_add_courses_' . $level_id );
				}

				return;
			}

			if ( isset( $_POST['retroactive_assignment'] ) ) {
				$step  = 1;
				$count = 0;
				$steps = 'continue';
			}

			$customers = $wpdb->get_results( $wpdb->prepare(
                "SELECT DISTINCT max( CASE WHEN pm.meta_key = '_customer_user' and p.ID = pm.post_id THEN pm.meta_value END ) as customer
                FROM
                    {$wpdb->prefix}posts p
                    join {$wpdb->prefix}postmeta pm on p.ID = pm.post_id
                    join {$wpdb->prefix}woocommerce_order_items oi on p.ID = oi.order_id
                    join {$wpdb->prefix}woocommerce_order_itemmeta oim on oi.order_item_id = oim.order_item_id
                WHERE
                    p.post_type = 'shop_order' AND
                    ( p.post_status = 'wc-completed' OR p.post_status = 'wc-processing' ) AND
                    oim.meta_key = '_product_id' AND
                    oim.meta_value = %d
                GROUP BY
                    p.ID
                LIMIT %d
                OFFSET %d",
                $level_id,
                $batch,
                $count
            ), ARRAY_A );

			if ( ! $customers && ! isset( $_GET['action'] ) ) {
				$page->showMessage( esc_html__( 'No existing customers found for the specified product.', 'wpcw-wc-addon' ) );

				return;
			}

			if ( $customers && 'continue' === $steps ) {
				if ( count( $customers ) < $batch ) {
					$steps = 'finished';
				}

				foreach ( $customers as $key => $customer ) {
					$customer_id = $customer['customer'];

					//Only remove necessary courses
					if ( $coursesToRemove ){

						$products = array();
						$courseIDList = array();

						$customer_orders = wc_get_orders( array(
						    'customer_id' 	=> $customer_id,
						    'status' 		=> array( 'wc-processing', 'wc-completed' ),
						) );

						// Fetch order ID's for customer.
						foreach ( $customer_orders as $customer_order ) {
							$cust_orders = wc_get_order( $customer_order );

							// Get product items from order and insert into array.
							foreach ( $cust_orders->get_items() as $item_id => $item ) {
								// We need to evaluate products other than $level_id
								if( $level_id != $item->get_product_id() ){
									// Build array for evaluation
									$products[] = $item->get_product_id();
								}
							}
						}

						// Clean up duplicate IDs.
						$unique_products = array_unique( $products );

						// Might not have any products to process.
						if ( $unique_products && count( $unique_products ) > 0 ) { 
							// Assume that there might be multiple products per user.
							foreach ( $unique_products as $unique_product ) { 
								// Got courses for this product.
								$courses = $this->getCourseAccessListForLevel( $unique_product ); 

								if ( $courses ) {
									foreach ( $courses as $courseIDToKeep => $levelID ) { 
										$courseIDList[] = $courseIDToKeep; 
									}
								}
							}
						}

						$removeCourses = array_diff( $coursesToRemove, $courseIDList );

						// De-enroll students from specified courses
						$this->handle_course_de_enrollment( $customer_id, $removeCourses );
					}

					if ( $coursesToAdd ){
						// Enroll students into specified courses
						WPCW_courses_syncUserAccess( $customer_id, $coursesToAdd, 'add' );
					}
					// Increment Count.
					$count += 1;
				}

				$step += 1;
			} else {
				$steps = 'finished';
			}

			$page->showMessage( esc_html__( 'Please wait. Retroactively updating existing customers...', 'wpcw-wc-addon' ) );

			$location_url = add_query_arg( array(
				'page'     => $this->extensionID,
				'level_id' => $level_id,
				'step'     => $step,
				'count'    => $count,
				'steps'    => $steps,
				'action'   => 'retroactiveassignment'
			), admin_url( 'admin.php' ) );

			?>
			<script type="text/javascript">
				setTimeout( function () {
					document.location.href = "<?php echo $location_url; ?>";
				}, 1000 );
			</script>
			<?php
		}

		/**
		 * Update User Course Access.
		 *
		 * Function just for handling course enrollment upon purchase of product.
		 *
		 * @since 1.3.0
		 *
		 * @param int $order_id The order id.
		 */
		public function handle_updateUserCourseAccess( $order_id ) {
			// Get order data.
			$order = wc_get_order( $order_id );
			$items = $order->get_items();
			$products = array();

			foreach ( $items as $item ) {
			    $products[] = $item->get_product_id();
			}


			// Get customer ID.
			$user = $order->get_customer_id();

			
			$this->handle_courseSync( $user, $products, 'add' );
		}

		/**
		 * Function called when updating a user for their course access.
		 *
		 * @since 1.3.0
		 *
		 * @param int    $userID The user id.
		 * @param array  $levelList The level list.
		 * @param string $mode The sync mode, either 'add' or 'sync' Default is 'sync'.
		 */
		public function handle_courseSync( $userID, $levelList, $mode = 'sync' ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			$courseIDList = array();
			$enrollment_dates = array();
			// Might not have any levels to process.
			if ( $levelList && count( $levelList ) > 0 ) {
				foreach ( $levelList as $aLevelID ) {
					// Got courses for this level.
					$courses = $this->getCourseAccessListForLevel( $aLevelID );
					if ( $courses ) {
						foreach ( $courses as $courseIDToKeep => $levelID ) {
							// Use array index to build a list of valid course IDs
							// $levelID not needed, just used to assign something interesting. It's
							// the $courseIDToKeep that's the valuable bit.
							$courseIDList[ $courseIDToKeep ] = $levelID;
							if ( WPCW_check_course_expiration( $courseIDToKeep ) ){
								if ( true === apply_filters( 'wpcw_wc_addon_enroll_current_date', false ) ) {
								$enrollment_dates[ $courseIDToKeep ] = current_time( 'timestamp' );
								}
							}
						}
					}
				}
			} 
			// By this point, $courseIDList may or may not contain a list of courses.
			WPCW_courses_syncUserAccess( $userID, array_keys( $courseIDList ), $mode, $enrollment_dates );
			//WPCW_courses_syncUserAccess( $userID, array_keys( $courseIDList ), $mode );
		}

		/**
		 * Handle Subscription Course Enrollment
		 *
		 * @since 1.3.0
		 *
		 * @param \WC_Subscription $subscription The woocommerce subscription.
		 */
		public function handle_wc_subscription_user_course_enrollment( $subscription ) {
			// Key Variables.
			$product_ids        = array();
			$student_id         = $subscription->get_customer_id();
			$subscription_items = $subscription->get_items();

			if ( ! empty( $subscription_items ) ) {
				/** @var WC_Order_Item_Product $subscription_item */
				foreach ( $subscription_items as $subscription_item ) {
					$product_ids[] = $subscription_item->get_product_id();
				}
			}

			if ( ! $student_id || empty( $product_ids ) ) {
				return;
			}

			// Take out duplicates.
			$product_ids = array_unique( $product_ids );

			// Handle course sync.
			$this->handle_courseSync( $student_id, $product_ids, 'add' );
		}

		/**
		 * Handle Subscription Course De-Enrollment
		 *
		 * @since 1.3.0
		 *
		 * @param \WC_Subscription $subscription The woocommerce subscription.
		 */
		public function handle_wc_subscription_user_course_de_enrollment( $subscription ) {
			// Key Variables.
			$sub_products       = array();
			$sub_courses        = array();
			$products			= array();
			$prod_courses		= array();
			$student_id         = $subscription->get_customer_id();
			$subscription_items = $subscription->get_items();

			if ( ! empty( $subscription_items ) ) {
				/** @var WC_Order_Item_Product $subscription_item */
				foreach ( $subscription_items as $subscription_item ) {
					$sub_products[] = $subscription_item->get_product_id();
				}
			}

			if ( ! empty( $sub_products ) ) {
				foreach ( $sub_products as $product_id ) {
					$courses = $this->getCourseAccessListForLevel( $product_id );
					if ( ! empty( $courses ) ) {
						foreach ( $courses as $course_id => $course_level_id ) {
							$sub_courses[] = $course_id;
						}
					}
				}
			}

			$all_products = array_keys( $this->getMembershipLevels() );
			$products = array_diff( $all_products, $sub_products );

			if ( ! empty( $products ) ){
				foreach ( $products as $product_id ) {
					$courses = $this->getCourseAccessListForLevel( $product_id );
						if ( ! empty( $courses ) ){
							foreach ( $courses as $course_id => $course_level_id ) {
								$prod_courses[] = $course_id;
							}
						}
				}
			}

			$remove_courses = array_diff( $sub_courses, $prod_courses );

			if ( ! $student_id || empty( $sub_products ) || empty( $sub_courses ) ) {
				return;
			}

			// Handle Course De-Enrollment.
			$this->handle_course_de_enrollment( $student_id, $remove_courses );
		}

		/**
		 * Handle Membership Course Status Change.
		 *
		 * @since 1.3.0
		 *
		 * @param \WC_Memberships_User_Membership $user_membership the membership.
		 * @param string                          $old_status old status, without the `wcm-` prefix.
		 * @param string                          $new_status new status, without the `wcm-` prefix.
		 */
		public function handle_wc_membership_user_course_status_change( $user_membership, $old_status, $new_status ) {
			$enroll_statuses    = apply_filters( 'wpcw_wc_addon_wc_membership_enroll_statuses', array( 'active', 'complimentary' ) );
			$de_enroll_statuses = apply_filters( 'wpcw_wc_addon_wc_membership_de_enroll_statuses', array( 'expired', 'cancelled' ) );

			if ( in_array( $new_status, $enroll_statuses, true ) ) {
				$this->handle_wc_membership_user_course_enrollment( $user_membership );
			}

			if ( in_array( $new_status, $de_enroll_statuses, true ) ) {
				$this->handle_wc_membership_user_course_de_enrollment( $user_membership );
			}
		}

		/**
		 * Handle Teams Membership Course Enrollment.
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member $member the team member instance
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 * @param \WC_Memberships_User_Membership $user_membership the related user membership instance
		 */
		public function handle_wc_teams_membership_user_course_enrollment( $member, $team, $user_membership ) {
			$enroll_statuses    = apply_filters( 'wpcw_wc_addon_wc_membership_enroll_statuses', array( 'active', 'complimentary' ) );

			if ( in_array( $user_membership->get_status(), $enroll_statuses, true ) ) {
				$this->handle_wc_membership_user_course_enrollment( $user_membership );
			}
		}

		/**
		 * Handle Teams Membership Course De-enrollment.
		 * @param int $user_id the id of the user (team member)
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team the team instance
		 */
		public function handle_wc_teams_membership_user_course_de_enrollment( $user_id, $team ) {
			global $wpdb, $wpcwdb;
			$product_id = $team->get_product_id();
			$course_ids = array();

			$courses = $this->getCourseAccessListForLevel( $product_id );
				if ( ! empty( $courses ) ) {
					foreach ( $courses as $course_id => $course_level_id ) {
						$course_ids[] = $course_id;
					}
				}

			if ( empty( $course_ids ) || ! is_array( $course_ids ) ) {
				return;
			}

			$csv_course_ids = implode( ',', $course_ids );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpcwdb->user_courses} WHERE user_id = %d AND course_id IN ({$csv_course_ids})", $user_id ) );
		}

		/**
		 * Handle Membership Course Enrollment.
		 *
		 * @since 1.3.0
		 *
		 * @param \WC_Memberships_User_Membership $user_membership the membership.
		 */
		public function handle_wc_membership_user_course_enrollment( $user_membership ) {
			// Key Variables.
			$product_ids           = array();
			$student_id            = $user_membership->get_user_id();
			$membership_plan       = $user_membership->get_plan();
			$membership_product_id = $user_membership->get_product_id();

			if ( ! $membership_plan || ! $membership_product_id ) {
				return;
			}

			// Get Product Ids.
			$product_ids = array( $membership_product_id );

			if ( ! $student_id || empty( $product_ids ) ) {
				return;
			}

			// Take out duplicates.
			$product_ids = array_unique( $product_ids );

			// Handle course sync.
			$this->handle_courseSync( $student_id, $product_ids, 'add' );
		}

		/**
		 * Handle Membership Course De-Enrollment.
		 *
		 * @since 1.3.0
		 *
		 * @param \WC_Memberships_User_Membership $user_membership the membership.
		 */
		public function handle_wc_membership_user_course_de_enrollment( $user_membership ) {
			// Key Variables.
			$mem_products           = array();
			$mem_courses            = array();
			$products 				= array();
			$courseIDList			= array();
			$student_id            	= $user_membership->get_user_id();
			$membership_plan       	= $user_membership->get_plan();
			$membership_product_id 	= $user_membership->get_product_id();

			if ( ! $membership_plan || ! $membership_product_id ) {
				return;
			}

			// Get Product Ids.
			$mem_products = array( $membership_product_id );

			if ( ! empty( $mem_products ) ) {
				foreach ( $mem_products as $product_id ) {
					$courses = $this->getCourseAccessListForLevel( $product_id );
					if ( ! empty( $courses ) ) {
						foreach ( $courses as $course_id => $course_level_id ) {
							$mem_courses[] = $course_id;
						}
					}
				}
			}

			$customer_orders = wc_get_orders( array(
			    'customer_id' 	=> $student_id,
			    'status' 		=> array( 'wc-processing', 'wc-completed' ),
			) );

			// Fetch order ID's for customer.
			foreach ( $customer_orders as $customer_order ) {
				$cust_orders = wc_get_order( $customer_order );

				// Get product items from order and insert into array.
				foreach ( $cust_orders->get_items() as $item_id => $item ) {
					// We need to evaluate products other than $level_id
					if( $membership_product_id != $item->get_product_id() ){
						// Build array for evaluation
						$products[] = $item->get_product_id();
					}
				}
			}

			// Clean up duplicate IDs.
			$unique_products = array_unique( $products );

			// Might not have any products to process.
			if ( $unique_products && count( $unique_products ) > 0 ) { 
				// Assume that there might be multiple products per user.
				foreach ( $unique_products as $unique_product ) { 
					// Got courses for this product.
					$courses = $this->getCourseAccessListForLevel( $unique_product ); 

					if ( $courses ) {
						foreach ( $courses as $courseIDToKeep => $levelID ) { 
							$courseIDList[] = $courseIDToKeep; 
						}
					}
				}
			}

			$remove_courses = array_diff( $mem_courses, $courseIDList );

			if ( ! $student_id || empty( $mem_products ) || empty( $mem_courses ) ) {
				return;
			}

			// Handle Course De-Enrollment.
			$this->handle_course_de_enrollment( $student_id, $remove_courses );
		}

		/**
		 * Handle Course De-Enrollment.
		 *
		 * @since 1.3.0
		 *
		 * @param int   $student_id The student id.
		 * @param array $course_ids The course ids to enroll.
		 */
		public function handle_course_de_enrollment( $student_id, $course_ids = array() ) {
			global $wpdb, $wpcwdb;

			if ( empty( $course_ids ) || ! is_array( $course_ids ) ) {
				return;
			}

			$csv_course_ids = implode( ',', $course_ids );

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpcwdb->user_courses} WHERE user_id = %d AND course_id IN ({$csv_course_ids})", $student_id ) ); // phpcs:ignore WordPress.DB

			WPCW_queue_dripfeed::updateQueueItems_removeUser_fromCourseList( $student_id, $course_ids );
		}


		/**
		 * Check User Course Access.
		 *
		 * Function just for checking subscription status
		 * to determine whether or not to display course content.
		 *
		 * @since 1.4.0
		 *
		 * @param bool $access Can the user access this course?
		 * @param int  $course_id The course id.
		 * @param int  $user_id The user id.
		 *
		 * @return bool $access True if the user can access the course, false otherwise.
		 */
		public function wc_check_wpcw_course_access( $access, $course_id, $user_id, $ignore = false ) {
			global $wpdb, $wpcwdb;

			if( 'WPCW_showPage_UserProgess' === wpcw_get_var( 'page' ) ) {
				return $access;
			}

			$post = get_post($course_id);
			$fe = new WPCW_UnitFrontend( $post );
			$is_admin_or_teacher = $fe->check_is_admin_or_teacher();

			if ( $is_admin_or_teacher === true ) {
				return true;
			}

			$ignore = apply_filters( 'wc_subscriptions_ignore_wpcw_course_access', ( $ignore ? true : false ) );

			if ( $ignore ){
				return $access;
			}

			// Get orders for $user_id
			$customer_orders = wc_get_orders( array(
					    'customer_id' 	=> $user_id,
					    'status' 		=> array( 'wc-processing', 'wc-completed' ),
					) );
			
			if( ! empty($customer_orders) ){
				foreach ( $customer_orders as $customer_order ) {
					$cust_orders = wc_get_order( $customer_order );
					// Get product items from order and insert into array.
					foreach ( $cust_orders->get_items() as $item_id => $item ) {
						$products[] = $item->get_product_id();
					}
				}

				foreach ( $products as $product_id ){
					//Check for products that are not subscription based
					if (! WC_Subscriptions_Product::is_subscription( $product_id )){
						$courses = $this->getCourseAccessListForLevel( $product_id );
						if ( $courses ){
							foreach ( $courses as $courseid => $productid ) {
								if ( absint( $courseid ) === absint( $course_id ) ) {
									//if the course was purchased as a one-off product grant access
									return true;
								}
							}
						}
					}
				}
			}

			$course_subscriptions = array();

			if ( empty( $this->user_subscriptions ) ) {
				$this->user_subscriptions = wcs_get_users_subscriptions( $user_id );
			}

			if ( $this->user_subscriptions ) {
				foreach ( $this->user_subscriptions as $subscription ) {
					$order = wc_get_order( $subscription->get_id() );
					$items = $order->get_items();

					if ( $items ) {
						foreach ( $items as $item ) {
							$product_id = $item->get_product_id();

							$user_courses = $wpdb->get_col( $wpdb->prepare(
								"SELECT course_id 
							     FROM $wpcwdb->map_member_levels
							     WHERE member_level_id = %s",
								$product_id
							) );

							if ( $user_courses ) {
								foreach ( $user_courses as $user_course_id ) {
									if ( absint( $user_course_id ) === absint( $course_id ) ) {
										$course_subscriptions[ $subscription->get_id() ] = $subscription->get_status();
									}
								}
							}
						}
					}
				}
			}

			if ( ! empty( $course_subscriptions ) &&
			     ! in_array( 'active', $course_subscriptions ) &&
			     ! in_array( 'pending-cancel', $course_subscriptions ) ) {
				$access = false;
			}

			return $access;
		}

		

		/**
		 * Detect presence of WooCommerce plugin.
		 *
		 * @since 1.3.0
		 */
		public function found_membershipTool() {
			return class_exists( 'WooCommerce' );
		}
	}
}

if ( ! class_exists( 'WPCW_WC_Membership' ) ) {
	/**
	 * Class WPCW_Woo.
	 *
	 * Included for compatability with old addon.
	 *
	 * @since 1.0.0
	 */
	class WPCW_Woo extends WPCW_WC_Membership {

	}
}
