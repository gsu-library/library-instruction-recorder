=== Library Instruction Recorder ===
Contributors: mbrooks34
Donate link: http://library.gsu.edu/giving/
Tags: library, instruction, teaching, library instruction recorder, instruction scheduling, library instruction, bibliographic instruction
Requires at least: 3.6
Tested up to: 4.2.4
Stable tag: 1.1.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A plugin for recording and reporting library instruction classes.

== Description ==

The Library Instruction Recorder (LIR) is designed to record library instruction classes and provide statistical reports.  It is simple, easy-to-use, and intuitive.

**Features**

* Accessible only from the WordPress Dashboard, allowing it to be used on either internally- or externally-facing WordPress instances.
* Displays classes by: Upcoming, Incomplete, Previous and My Classes
* Customizable fields for Department, Class Location, Class Type and Audience.
* Customizable flags (i.e. "Do any students have disabilities or special requirements?" "Is this a First Year Experience class?")
* Ability to duplicate classes for multiple sessions.
* Statistical reports can be narrowed by date range or primary librarian.  Reports are downloadable as .csv files.
* Email reminder to enter the number of students who attended the class.

Visit [LIR's Bitbucket page](https://bitbucket.org/gsulibwebmaster/library-instruction-recorder) to submit issues, contribute to the wiki, contribute to the source code, and to check out the development version of the plugin!

== Warning ==

Library Instruction Recorder stores most of its data in custom tables within the WordPress database. **Deleting (*not deactivating*) this plugin will result in the loss of all stored data.** All of the class data is stored in the following tables by default (not including the WordPress database prefix): LIR_posts, LIR_flags, and LIR_meta.

== Adding/Removing Fields ==

Changing fields on the fields page does not affect classes already entered into LIR. For example, removing the class location "Classroom 1" from the fields page will not remove it from classes that exist with "Classroom 1" selected as the class location, it will just not be available as a choice for new classes. This is the same for all fields on the fields page.

== Requirements ==

* JavaScript must be enabled in order for some of the functionality to work.

== Installation ==

1. Upload the library-instruction-recorder directory to the plugins folder.
2. Activate the plugin on the plugins section of the dashboard.
3. Add fields for the Library Instruction Recorder by going to LIR -> Fields (must be an administrator).
4. Adjust any additional settings by going to LIR -> Settings (must be an administrator).

== Frequently Asked Questions ==

= Why did you make this when there are so many other tools out there? =

The Library Instruction Recorder is specifically designed to be simple, without a lot of the features often found in larger and more complex resource scheduling tools.  By reducing unnecessary features, librarians are able to quickly record sessions and get statistical reports easily.

= What about resource scheduling? Or .ics files? Or attaching instructional materials? Or making me a cup of coffee? =

Right now there are no plans for major enhancements to LIR.

= How/Where should I report problems? =

You can submit issue tickets to our [Bitbucket issue tracker](https://bitbucket.org/gsulibwebmaster/library-instruction-recorder/issues).

== Screenshots ==

1. Upcoming Classes
2. Other Details
3. Add a Class, part 1
4. Add a Class, part 2
5. Reports

== Upgrade Notice ==

= 1.1.4 =
Fixes date/time/timezone, last updated field, single quote/forward slash, report, and copy issues. Adds an email setting. THIS UPDATE ALTERS THE LIR_POSTS TABLE, PLEASE BACK UP YOUR TABLE DATA BEFORE UPDATING.

= 1.1.3 =
Fixes the uninstaller!

= 1.1.2 =
Fixed a lot of warnings that PHP was throwing.

= 1.1.1 =
Fixed a report generation issue.

= 1.1.0 =
Added persistent record count and smashed some bugs.

= 1.0.2 =
Updated script loading order for the plugin.

= 1.0.1 =
Fixes a few bugs including a timezone related bug.

= 1.0.0 =
Initial release so why not install?

== Changelog ==

= 1.1.4 =
* No longer sets PHP timezone, uses WordPress current_time function instead.
* Updated table creation for posts table, now last_updated is updated on an update (ON UPDATE CURRENT_TIMESTAMP).
* Alters posts table to be in line with the above.
* Strips extra slashes that WordPress adds to POST/GET/&c. variables (this was causing extra slashes to be added in front of single quotes).
* Escaped a lot of fields that were not already escaped.
* Report downloads in Firefox are fixed (removed spaces from file name).
* Copying a class now allows all fields to be edited before being submitted.
* Added an option to disable email reminders.

= 1.1.3 =
* Doesn't look for WP_UNINSTALL_PLUGIN flag in the uninstall method now.
* Activation, deactivation, and uninstall hooks now reference the class name instead of 'this'.

= 1.1.2 =
* Fixed a TON of undefined index errors!
* Removed some unnecessary code related to report downloading.
* Changed a few deprecated WordPress functions.

= 1.1.1 =
* CSV headers were not being sent before data in some instances of report generation causing reports to not download. This has been fixed!

= 1.1.0 =
* Record count ("show entries" number) is now persistent on upcoming classes page (per user).
* Empty settings can no longer be saved.

= 1.0.2 =
* Tweaked script loading to make sure dependencies are loaded first.

= 1.0.1 =
* Moved version checking to admin_init hook.
* Fixed a bug where saving the settings would remove the version number from the options table.
* The WordPress timezone setting is now used when using PHP date (potential issue with DST when using UTC offsets).

= 1.0.0 =
* Initial release!
