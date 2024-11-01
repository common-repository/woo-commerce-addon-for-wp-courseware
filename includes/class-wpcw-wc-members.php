<?php
/**
 * WP Courseware Members Class.
 *
 * Included here for compatability.
 *
 * @package WPCW_WC_Addon/Includes
 * @since 1.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCW_WC_Members' ) ) {
	/**
	 * Class WPCW_WC_Members.
	 *
	 * @since 1.3.0
	 */
	class WPCW_WC_Members {

		/**
		 * Stores the name of the extension, i.e. the membership plugin that this is for.
		 * @var String
		 */
		public $extensionName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		/**
		 * Stores the version of this extension.
		 * @var Float
		 */
		public $version;
		/**
		 * Stores the full unique string ID for this extension.
		 * @var String
		 */
		public $extensionID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		/**
		 * The cached storage of the membership levels.
		 * @var Array
		 */
		private $membershipLevelData; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		/**
		 * Initialize the membership plugin.
		 *
		 * @param string $extensionName The extension name.
		 * @param string $extensionID The extension id.
		 * @param string $version The extensino version.
		 */
		function __construct( $extensionName, $extensionID, $version ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$this->extensionName = $extensionName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$this->extensionID   = $extensionID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$this->version       = $version;

			$this->membershipLevelData = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		/**
		 * Create a menu for this plugin for configuration.
		 */
		function attachToTools() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			// Disable new user registration action (which otherwise sets course access).
			add_filter( 'wpcw_extensions_ignore_new_user', array( $this, 'filter_disableNewUserHook' ) );

			// Add items to the menu.
			add_filter( 'wpcw_extensions_menu_items', array( $this, 'init_menu' ) );

			// Indicate that extension is handling access control.
			add_filter( 'wpcw_extensions_access_control_override', array( $this, 'filter_accessControlForUsers' ) );

			// Call child classes to handle user updates.
			$this->attach_updateUserCourseAccess();
		}

		/**
		 * Creates the page that shows the level mapping for users.
		 */
		function showMembershipMappingLevels() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			// Page Intro.
			$page = new PageBuilder( false );
			$page->showPageHeader( $this->extensionName . ' ' . __( '&amp; Automatic Course Access Settings', 'wpcw-wc-addon' ), false, WPCW_icon_getPageIconURL() ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			// Check for parameters for modifying the levels for a specific level.
			$levelID     = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$showSummary = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			if ( isset( $_GET['level_id'] ) ) {
				// Seem to have a level, check it exists in the list.
				$levelID = trim( $_GET['level_id'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				if ( $levelID ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$levelList = $this->getMembershipLevels(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

					// Found the level in the list of levels we have.
					if ( isset( $levelList[ $levelID ] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						// Show the page for editing those specific level settings.
						$showSummary = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						$this->showMembershipMappingLevels_specificLevel( $page, $levelList[ $levelID ] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					} else {
						$page->showMessage( __( 'That product does not appear to exist.', 'wpcw-wc-addon' ), true );
					}
				} // end if ($levelID)
			} // end if (isset($_GET['level_id']))

			// Showing summary, as not editing a specific level above.
			if ( $showSummary ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$this->showMembershipMappingLevels_overview( $page );
			}

			// Page Footer.
			$page->showPageFooter();
		}

		/**
		 * Show the form for editing the specific courses a user can access based on what level that they have access to.
		 *
		 * @param PageBuilder $page The page rendering object.
		 * @param Array       $levelDetails The list of level details.
		 */
		private function showMembershipMappingLevels_specificLevel( $page, $levelDetails ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName, WordPress.NamingConventions.ValidFunctionName

			// Show a nice summary of what level is being edited.
			printf( '<div id="wpcw_member_level_name_title">%s</div>',
				/* translators: %1$s - Level Details Name, %2$s - Extension Name */
				sprintf( __( 'Editing access settings for <b>%1$s</b> product with <b>%2$s</b>:', 'wpcw-wc-addon' ), $levelDetails['name'], $this->extensionName ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			);

			if ( isset( $_REQUEST['action'] ) && 'retroactiveassignment' === $_REQUEST['action'] ) {
				$level_ID = $levelDetails['id'];
				$this->retroactive_assignment( $level_ID );

				return;
			}

			// Get a list of course IDs that exist.
			$courses = wpcw_wc_addon_get_courses( false );

			// Get list of courses already associated with level.
			$courseListInDB = $this->getCourseAccessListForLevel( $levelDetails['id'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			// Create the summary URL to return.
			$summaryURL = admin_url( 'admin.php?page=' . $this->extensionID ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			// Update form...
			$form = new FormBuilder( 'wpcw_member_levels_edit' );
			$form->setSubmitLabel( __( 'Save Changes', 'wpcw-wc-addon' ) );

			// Create list of courses using checkboxes (max of 2 columns).
			$elem = new FormElement( 'level_courses', __( 'Courses user can access with this product', 'wpcw-wc-addon' ), false );
			$elem->setTypeAsCheckboxList( $courses );
			$elem->checkboxListCols = 2; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$form->addFormElement( $elem );

			// Create retroactive option.
			$elem = new FormElement( 'retroactive_assignment', __( 'Do you want to retroactively enroll students into selected courses? <br><br><strong><i>[NOTE]</i></strong> - Deselecting course(s) will de-enroll students.<br>', 'wpcw-wc-addon' ), true );
			$elem->setTypeAsRadioButtons( array(
				'Yes' => __( 'Yes, enroll students into newly selected course(s)', 'wpcw-wc-addon' ),
				'No'  => __( 'No, just associate course(s) with the product.', 'wpcw-wc-addon' ),
			) );
			
			$form->addFormElement( $elem );

			$form->setDefaultValues( array(
				'retroactive_assignment' => 'No',
			) );

			// Normally would check for errors too, but there's not a lot to check here.
			if ( $form->formSubmitted() ) {
				if ( $form->formValid() ) {
					$mapplingList = $form->getValue( 'level_courses' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

					global $wpdb, $wpcwdb;
					$wpdb->show_errors();

					$current_levelList = array();

					if ( $this->getCourseAccessListForLevel( $levelDetails['id'] ) ){
						$current_levelList = array_keys( $this->getCourseAccessListForLevel( $levelDetails['id'] ) );
					}
					
					// Remove all previous level mappings (as some will have been removed).
					$wpdb->query( $wpdb->prepare(
						"DELETE 
						 FROM $wpcwdb->map_member_levels
						 WHERE member_level_id = %s",
						$levelDetails['id'] // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					) ); // WPCS: unprepared SQL ok.

					// Add all of the new mappings the user has chosen.
					if ( $mapplingList && count( $mapplingList ) > 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						foreach ( $mapplingList as $courseID => $itemState ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
							$wpdb->query( $wpdb->prepare(
								"INSERT INTO $wpcwdb->map_member_levels 
								 (course_id, member_level_id)  
								 VALUES (%d, %s)",
								$courseID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName
								$levelDetails['id'] // phpcs:ignore WordPress.NamingConventions.ValidVariableName
							) ); // WPCS: unprepared SQL ok.
						}
					}

					// Get retroactive selection.
					$retroactive_assignment = $form->getValue( 'retroactive_assignment' );

					// Call the retroactive assignment function passing the member level ID.
					if ( 'Yes' == $retroactive_assignment && count( $mapplingList ) >= 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						$level_ID = $levelDetails['id']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

						$new_levelList = array_keys( $mapplingList );
						$remove_courses = array_diff( $current_levelList, $new_levelList );
						$add_courses = array_diff( $new_levelList, $current_levelList );

						if ( $add_courses ){
							set_transient( 'wpcw_add_courses_' . $level_ID, $add_courses, 60*60*12 );
						}
						
						if ( $remove_courses ){
							set_transient( 'wpcw_remove_courses_' . $level_ID, $remove_courses, 60*60*12 );
						}

						$this->retroactive_assignment( $level_ID ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

						return;
						// $page->showMessage(__('All members were successfully retroactively enrolled into the selected courses.', 'wpcw-wc-addon'));
					}

					// Show a success message.
					$page->showMessage(
						__( 'Product and course access settings successfully updated.', 'wpcw-wc-addon' )
						. '<br/><br/>' .
						/* translators: %s - Summary Url. */
						sprintf( __( 'Want to return to the <a href="%s">Course Access Settings</a>?', 'wpcw-wc-addon' ), $summaryURL ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					);
				}
			} else {
				$form->setDefaultValues( array( 'level_courses' => $courseListInDB ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			}

			// Show the form.
			echo $form->toString();

			printf( '<a href="%s" class="button-secondary">%s</a>', $summaryURL, __( '&laquo; Return to Course Access Settings', 'wpcw-wc-addon' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		/**
		 * Page that shows the overview of mapping for the levels to courses.
		 *
		 * @param PageBuilder $page The current page object.
		 */
		private function showMembershipMappingLevels_overview( $page ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			// Handle the detection of the membership plugin before doing anything else.
			if ( ! $this->found_membershipTool() ) {
				$page->showPageFooter();

				return;
			}


			// Try to show the level data.
			$levelData = $this->getMembershipLevels_cached(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			if ( $levelData ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				// Create the table to show the data.
				$table             = new TableBuilder();
				$table->attributes = array(
					'class' => 'wpcw_tbl widefat',
					'id'    => 'wpcw_members_tbl',
				);

				$col = new TableColumn( __( 'Product ID', 'wpcw-wc-addon' ), 'wpcw_members_id' );
				$table->addColumn( $col );

				$col = new TableColumn( __( 'Product Name', 'wpcw-wc-addon' ), 'wpcw_members_name' );
				$table->addColumn( $col );

				$col = new TableColumn( __( 'Users with this product can access:', 'wpcw-wc-addon' ), 'wpcw_members_levels' );
				$table->addColumn( $col );

				$col = new TableColumn( __( 'Actions', 'wpcw-wc-addon' ), 'wpcw_members_actions' );
				$table->addColumn( $col );

				$odd = false;

				// Work out the base URL for the overview page.
				$baseURL = admin_url( 'admin.php?page=' . $this->extensionID ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

				// The list of courses that are currently on the system.
				$courses = wpcw_wc_addon_get_courses( false );

				// Add actual level data.
				foreach ( $levelData as $id => $levelDatum ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$data                      = array();
					$data['wpcw_members_id']   = $levelDatum['id']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$data['wpcw_members_name'] = $levelDatum['name']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName


					// Get list of courses already associated with level.
					$courseListInDB = $this->getCourseAccessListForLevel( $levelDatum['id'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

					if ( $courses ) {
						$data['wpcw_members_levels'] = '<ul class="wpcw_tickitems">';

						// Show which courses will be added to users created at this level.
						foreach ( $courses as $courseID => $courseName ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
							if ( ! isset($courseListInDB[ $courseID ]) ){
								continue;
							}
							$data['wpcw_members_levels'] .= sprintf( '<li class="wpcw_enabled">%s</li>', $courseName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						}

						$data['wpcw_members_levels'] .= '</ul>';
					} else {
						$data['wpcw_members_levels'] = __( 'There are no courses yet.', 'wpcw-wc-addon' );
					}


					// Buttons to edit the permissions.
					$data['wpcw_members_actions'] = sprintf( '<a href="%s&level_id=%s" class="button-secondary">%s</a>',
						$baseURL, $levelDatum['id'], __( 'Edit Course Access Settings', 'wpcw-wc-addon' ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					);

					$odd = ! $odd;
					$table->addRow( $data, ( $odd ? 'alternate' : '' ) );
				}

				echo $table->toString();
			} else {
				/* translators: %s - Extension Name */
				$page->showMessage( sprintf( __( 'No products were found for %s.', 'wpcw-wc-addon' ), $this->extensionName ), true ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			}
		}

		/**
		 * Gets the membership levels if we've not already got them, and then
		 * caches them locally in this object to minimise database calls.
		 *
		 * @return Array The membership data.
		 */
		protected function getMembershipLevels_cached() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			if ( $this->membershipLevelData ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				return $this->membershipLevelData; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			}

			$this->membershipLevelData = $this->getMembershipLevels(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			return $this->membershipLevelData; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		/**
		 * Get a list of the courses a user can access based on a specified level ID. Does not
		 * check that the level ID is valid.
		 *
		 * @param String $levelID The ID of the level that determines which courses can be accessed.
		 *
		 * @return Array The list of courses that a user can access for the membership level ($courseID => $levelID).
		 */
		protected function getCourseAccessListForLevel( $levelID ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName, WordPress.NamingConventions.ValidVariableName
			global $wpcwdb, $wpdb;
			$wpdb->show_errors();

			$result     = $wpdb->get_col( $wpdb->prepare( "SELECT course_id FROM $wpcwdb->map_member_levels WHERE member_level_id = %s", $levelID ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName, WordPress.DB.PreparedSQL.NotPrepared
			$courseList = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			if ( $result ) {
				$courseList = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				foreach ( $result as $courseID ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$courseList[ $courseID ] = $levelID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				}
			}

			return $courseList; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		/**
		 * Adds the details for the extension to the menu for WP Courseware.
		 *
		 * @param Array $menuItems The list of menu items to add this extension to.
		 *
		 * @return Array The list of menu items that has been modififed.
		 */
		public function init_menu( $menuItems ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			// Add our menu.
			$menuDetails               = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$menuDetails['page_title'] = $this->extensionName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$menuDetails['menu_label'] = $this->extensionName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$menuDetails['id']         = $this->extensionID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			// Use the function in this object to create the page that shows the level mapping.
			$menuDetails['menu_function'] = array( $this, 'showMembershipMappingLevels' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			$menuItems[] = $menuDetails; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

			return $menuItems; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		/**
		 * Attaches method to WP hooks to show that the plugin has not been detected.
		 */
		public function attach_showToolNotDetectedMessage() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			add_action( 'admin_notices', array( $this, 'showToolNotDetectedMessage' ) );
		}

		/**
		 * Show the message that the tool has not been detected
		 */
		public function showToolNotDetectedMessage() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			printf( '<div class="error"><p><b>%s - %s %s:</b> %s</p></div>', 'WP Courseware', $this->extensionName, __( 'addon', 'wpcw-wc-addon' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				/* translators: %s - Extension Name */
				sprintf( __( 'The %s plugin has not been detected. Is it installed and activated?', 'wpcw-wc-addon' ), $this->extensionName ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			);
		}

		/**
		 * Attaches method to WP hooks to show that WP Courseware has not been detected.
		 */
		public function attach_showWPCWNotDetectedMessage() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			add_action( 'admin_notices', array( $this, 'showWPCWNotDetectedMessage' ) );
		}

		/**
		 * Show the message that WP Courseware has not been detected
		 */
		public function showWPCWNotDetectedMessage() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			printf( '<div class="error"><p><b>%s</b> %s</p></div>', __( 'WP Courseware', 'wpcw-wc-addon' ), __( 'has not been detected. Is it installed and activated?', 'wpcw-wc-addon' ) );
		}

		/**
		 * Changes the message indicating there's an override for the access control due to this extension.
		 *
		 * @param String $existing The HTML for the existing message.
		 *
		 * @return String The replacement HTML
		 */
		public function filter_accessControlForUsers( $existing ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			// Handle when there are multiple addons detected. If no other add ons are
			// detected, clear out the existing HTML.
			if ( stripos( $existing, 'wpcw_override' ) === false ) {
				$existing = false;
			}

			return $existing . sprintf( '<li class="wpcw_bullet wpcw_override">%s <a href="%s">%s</a> %s</li>', __( 'New users given access based on', 'wpcw-wc-addon' ), admin_url( 'admin.php?page=' . $this->extensionID ), $this->extensionName, __( 'product', 'wpcw-wc-addon' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		/**
		 * By returning true, this disables the normal hook that would
		 * set the course access controls when a user is created, rather
		 * than relying on the user levels for membership.
		 */
		public function filter_disableNewUserHook() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			return true;
		}

		/**
		 * Method that gets the full list of user levels for the current membership plugin.
		 *
		 * This is intended to be overridden.
		 */
		protected function getMembershipLevels() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			return false;
		}

		/**
		 * Function called to attach hooks for handling when a user is updated or created.
		 *
		 * This is intended to be overridden to handle different hooks for different membership plugins.
		 */
		protected function attach_updateUserCourseAccess() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			return false;
		}

		/**
		 * Function called when updating a user for their course access.
		 *
		 * @param int   $userID The user id.
		 * @param array $levelList The level list.
		 */
		// public function handle_courseSync( $userID, $levelList ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName, WordPress.NamingConventions.ValidVariableName
		// 	global $wpdb, $wpcwdb;
		// 	$wpdb->show_errors();

		// 	$courseIDList = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		// 	// Might not have any levels to process.
		// 	if ( $levelList && count( $levelList ) > 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		// 		// Assume that there might be multiple levels per user.
		// 		foreach ( $levelList as $aLevelID ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		// 			// Got courses for this level.
		// 			$courses = $this->getCourseAccessListForLevel( $aLevelID ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		// 			if ( $courses ) {
		// 				foreach ( $courses as $courseIDToKeep => $levelID ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		// 					// Use array index to build a list of valid course IDs
		// 					// $levelID not needed, just used to assign something interesting. It's
		// 					// the $courseIDToKeep that's the valuable bit.
		// 					$courseIDList[ $courseIDToKeep ] = $levelID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		// 				}
		// 			}
		// 		}
		// 	} // end if ($levelList && count($levelList) > 0)

		// 	// By this point, $courseIDList may or may not contain a list of courses.
		// 	WPCW_courses_syncUserAccess( $userID, array_keys( $courseIDList ), 'sync' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		// }

		/**
		 * Function called to determine if WP Courseware is installed.
		 * @return Boolean True if it's found, false otherwise.
		 */
		public function found_wpcourseware() {
			return function_exists( 'WPCW_plugin_init' );
		}

		/**
		 * Method to detect if the membership tool has been found or not. If false is returned,
		 * a need error message is shown to the user.
		 *
		 * This is intended to be overridden.
		 */
		public function found_membershipTool() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			return false;
		}
	}
}
