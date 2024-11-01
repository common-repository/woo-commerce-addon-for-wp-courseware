=== WP Courseware for WooCommerce ===
Contributors: flyplugins
Donate link: http://flyplugins.com/donate
Tags: learning management system, selling online courses
Requires at least: 4.8
Tested up to: 6.4.2
Stable tag: 1.5.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin adds integration between WooCommerce and WP Courseware which allows you to associate courses to digital products for automatic enrollment.

== Description ==
[Fly Plugins](https://flyplugins.com) presents [WooCommerce](https://wordpress.org/plugins/woocommerce/) for WP Courseware a simple, yet powerful [WordPress LMS](https://flyplugins.com/wp-courseware) plugin.

= Would you like to sell an online course with WooCommerce? =
The WooCommerce Addon for WP Courseware will add full integration with WP Courseware. Simply assign WP Courseware courses to a WooCommerce product. When a student purchases the product, they will automatically be enrolled into the associated courses.

With this addon, you will be able to create a fully automated [Learning Management System](https://flyplugins.com/wp-courseware) and sell online courses.

= WooCommerce Plugin Integration with WP Courseware Plugin =
[youtube https://www.youtube.com/watch?v=_-uMPnw7T14]

= Basic Configuration Steps =
1. Create a course with WP Courseware and add modules, units, and quizzes
2. Create a product and set a price
3. Associate one or more WP Courseware courses with the product
4. New student pays for the product, and WP Courseware enrolls them to the appropriate courses based on the purchased product

= Check out Fly Plugins =
For more tools and resources for selling online courses check out:

* [WP Courseware](https://flyplugins.com/wp-courseware/) - The leading learning management system for WordPress. Create and sell online courses with a drag and drop interface. It’s that easy!
* [S3 Media Maestro](https://flyplugins.com/s3-media-maestro) - The most secure HTML 5 media player plugin for WordPress with full AWS (Amazon Web Services) S3 and CloudFront integration.

= Follow Fly Plugins =
* [Facebook](https://facebook.com/flyplugins)
* [YouTube](https://www.youtube.com/flyplugins)
* [Twitter](https://twitter.com/flyplugins)
* [Instagram](https://www.instagram.com/flyplugins/)
* [LinkedIn](https://www.linkedin.com/company/flyplugins)

= Disclaimer =
This plugin is only the integration, or “middle-man” between WP Courseware and WooCommerce.

== Installation ==

1. Upload the `WooCommerce for WP Courseware addon` folder into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does this plugin require WP Courseware to already be installed =

Yes!

= Does this plugin require WooCommerce to already be installed? =

Yes!

= Where can I get WP Courseware? =

[WP Courseware](https://flyplugins.com/wp-courseware)

= Where can I get WooCommerce for WordPress? =

[WooCommerce](https://wordpress.org/plugins/woocommerce/).

== Screenshots ==

1. The Course Access Settings screen will display which products are associated with which courses

2. This is the actual configuration screen where you can select courses that will be associated with a particular product as well as retroactively assign courses to current customers

== Changelog ==

= 1.5.0 =
* New: Teams for WooCommerce Memberships integration
* Tweak: Modified retroactive enrollment to utilize batch process in order to accomodate a large number of students
* Tweak: Only display courses associated with product on the WooCommerce & Automatic Course Access Settings page
* Fix: Fixed so that only enrolled courses will display on the Detailed Student Progress Report page for a single student

= 1.4.9 =
* Tweak: Modified the Course Access Settings page to ONLY display the courses associated with the product.

= 1.4.8 =
* Tweak: Added wc_subscriptions_ignore_wpcw_course_access filter to prevent checking each subscription for active status
* Tweak: Added wpcw_wc_addon_enroll_current_date filter to force re-purchase enrollments to be updated with current date.

= 1.4.7 =
* Fix: Fixed issue where students were not de-enrolled when WooCommerce subcription was expired or canceled.

= 1.4.6 =
* Fix: Fixed warning message that appeared when student was enrolled to a course manually.

= 1.4.5 =
* Tweak: Changed enrollment method when product is purchased to simply add (not sync ) courses associated with new products in order to avoid losing manual enrollments.
* Tweak: Changed retroactive enrollment method from sync to add/remove in order to avoid losing manual enrollments.

= 1.4.4 =
* Fix: Fixed issue where a purchased a WooCommerce simple product and simple subscription product in which the same course was assigned to both. If the subscription failed or was canceled, student would lose access to view the "course in common".

= 1.4.3 =
* Fix: Fixed issue where database query incorrectly identifying table prefixes causing issues with the retroactive enrollment function. 

= 1.4.2 =
* Fix: Allow WP Courseware Instructors to access wp-admin when WooCommerce is activated.

= 1.4.1 =
* Fix: Course objects not returned when using `wpcw_wc_addon_get_courses` with an old version of WP Courseware.

= 1.4.0 =
* Fix: Access check on the course if a user has multiple subscriptions which are associated with the same course.
* Tweak: Improved performance when access needs to be verified.

= 1.3.9 =
* Fix: Fix a conflict with other plugins that have a 'courses' post type.

= 1.3.8 =
* Tweak: Added batch processing on retroactive enrollment course product access settings.

= 1.3.7 =
* Fix: Removed the WooCommerce Memberships paused status for the automatic de-enrollment function.

= 1.3.6 =
* Fix: Issue where users without subscriptions couldn't view course units.

= 1.3.5 =
* Fix: Issue where filter for course access was not checking if WooCommerce Subscriptions existed hence causing sites without Subscriptions to not display course units.

= 1.3.4 =
* Fix: Added functionality to prevent access to a course when a subscription is NOT on hold. Note, this does not de-enroll the student, it merely prevents them from viewing course content, hence the course will be visible on the course progress page, however, units will not be "clickable" nor accessible.
* Fix: Course listing admin screen only displayed 20 courses instead of all courses.

= 1.3.3 =
* Fix: Course author setting should not be applied to Administrators.

= 1.3.2 =
* Fix: Courses with a status of draft and auto-draft would show up in the course => product maping list.

= 1.3.1 =
* Fix: Ability to change the courses endpoint to something other than `courses`.
* Fix: Compatibility with WooCommerce 3.3.5 and below.
* Dev: Filter 'wpcw_woocommerce_disable_deenrollment' to disable membership de-enrollment.

= 1.3.0 =
* New: Courses menu item and endpoint added to the WooCommerce account menu.
* New: Ability to change the endpoint slug for the Courses WooCommerce account menu.
* New: Support for WooCommerce Subscriptions add-on.
* New: Support for WooCommerce Memberships add-on.
* Tweak: Re-tooled the loading of the plugin to include more error checking.

= 1.2.0 =
* Fixed multiple bugs where a function was referencing a property that was deprecated with WooCommerce 3.0.

= 1.1.0 =
* Fixed bug that prevented retroactive course assignment to assign incorrect course

= 1.0.0 =
* Initial release

== Upgrade notice ==
