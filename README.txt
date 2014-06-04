=== Library Instruction Recorder ===
Contributors: mbrooks34
Donate link: http://library.gsu.edu/giving/
Tags: library, library instruction recorder, instruction scheduling
Requires at least: 3.6
Tested up to: 3.9.1
Stable tag: 0.2.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A library plugin for instruction scheduling/recording.

== Description ==

Could use a better description here with perhaps:

**Features**

* Feature a goes here.
* Feature b goes here.

== Requirements ==

* JavaScript must be enabled in order for some of the functionality to work.

== Installation ==

1. Upload the library-instruction-recorder directory to the plugins folder.
2. Activate the plugin on the plugins section of the dashboard.
3. Add fields for the Library Instruction Recorder by going to LIR -> Fields (must be an administrator).
4. Adjust any additional settings by going to LIR -> Settings (must be an administrator).

== Frequently Asked Questions ==

= Why is this section here? =
It is required and there are no FAQs yet.

== Screenshots ==

1. This shot shows the main display of LIR.
2. Here is the adding a class page.

== Upgrade Notice ==

= 0.2.0 =
Core functionality added.

= 0.1.0 =
Initial release so why not install?

== Changelog ==

= 0.3.0 =
Week of 6/2/2014, MB

* Added flags table to replace bool values in posts table, altered posts table to remove bool values, and updated code to be compatible with these changes.
* Key references added to database tables.

Week of 5/26/2014, MB

* Fixed a permission error with non-admins adding a class (check in wrong place).
* Reports show names in place of WordPress IDs now.
* Table structure change - attendance now unsigned and defaults at NULL.

Week of 5/12/2014, MB

* Changed last_updated_by field to bigint(20) (to reference WordPress user ID) and associated code.
* Downloading a report now works.
* Can also display reports (without downloading).
* Reports have the following optional fields: primary librarian, start date, and end date.

4/4/2014, MB

* Tweaked some display settings on the fields page.

Week of 3/10/2014, MB

* Refined PHP code, fixed spacing, and made some other visual changes.
* Added more constants for greater flexibility.

= 0.2.0 =
10/16/2013, MB

* Removed AJAX functionality and localized script.

Week of 10/7/2013, MB

* Editing a class has now been implemented.
* My classes listing available on upcoming classes page.
* Added dependencies for js/admin.js script.
* Localized js/admin.js script.
* Details link for upcoming classes now functions.

Week of 9/30/2013, MB

* Fields are now saved on add a class page after a bad submission.
* Fields can now be sorted on the upcoming classes page.
* Delete links on upcoming classes page now functional.
* Different class listings possible (upcoming, incomplete, previous).

9/25/2013, MB

* Added reports page (non-functional).
* Added jquery-ui-datepicker.js (from local WordPress repository) for date field on add a class page.
* Added jQuery UI Redmond styling to the plugin.

= 0.1.1 =
9/24/2013, MB

* Disabled test filter.
* Moved some functionality from the the constructor to a new init function to prevent unnecessary code from executing.

= 0.1.0 =
9/23/2013, MB

* Initial non-public release.
