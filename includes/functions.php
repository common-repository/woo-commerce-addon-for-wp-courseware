<?php
/**
 * WP Courseware Addon Functions.
 *
 * @package WPCW_WC_Addon/Includes
 * @since 1.3.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Addon - Get Courses.
 *
 * @since 1.3.2
 *
 * @return array $courses The courses array.
 */
function wpcw_wc_addon_get_courses() {
	$courses = array();

	if ( function_exists( 'wpcw' ) && isset( wpcw()->courses ) ) {
		$current_user_id = get_current_user_id();
		$course_args     = array(
			'status' => 'publish',
			'number' => 10000
		);

		if ( ! user_can( $current_user_id, 'manage_wpcw_settings' ) ) {
			$course_args['course_author'] = $current_user_id;
		}

		$course_objects = wpcw()->courses->get_courses( $course_args, true );

		if ( ! empty( $course_objects ) ) {
			foreach ( $course_objects as $course_object ) {
				if ( ! empty( $course_object->course_title ) ) {
					$courses[ $course_object->course_id ] = $course_object->course_title;
				}
			}
		}
	} else {
		$courses = WPCW_courses_getCourseList( false );
	}

	return $courses;
}
