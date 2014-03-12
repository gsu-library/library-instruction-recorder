<?php
/*
   Plugin Name: Library Instruction Recorder
   Plugin URI: http://bitbucket.org/gsulibwebmaster/library-instruction-recorder
   Description: A plugin for recording library instruction events and their associated data.
   Version: 0.2.0
   Author: Georgia State University Library
   Author URI: http://library.gsu.edu/
   License: GPLv3


   Library Instruction Recorder - A WordPress Plugin
   Copyright (C) 2013 Georgia State University Library

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


if(!class_exists('LIR')) {
   /*
      Class: LIR
         The LIR class which enables the Library Instruction Recorder functionality
         in WordPress.
   */
   class LIR {
      //Do not change these variables. The plugin name and slug can be changed on the settings page.
      const NAME = 'Library Instruction Recorder';
      const SLUG = 'LIR';
      const OPTIONS = 'lir_options';
      const OPTIONS_GROUP = 'lir_options_group';
      const VERSION = '0.2.0';
		const TABLE_POSTS = '_posts';
		const TABLE_META = '_meta';
      private $options;
      private $table;


      /*
         Constructor: __construct
            Adds register hooks, actions, and filters to WP upon construction.
      */
      public function __construct() {
         //Registration hooks.
         register_activation_hook(__FILE__, array(&$this, 'activationHook'));
         register_deactivation_hook(__FILE__, array(&$this, 'deactivationHook'));
         register_uninstall_hook(__FILE__, array(&$this, 'uninstallHook'));

         //Actions and filters.
         add_action('admin_menu', array(&$this, 'createMenu'));
         add_action('admin_init', array(&$this, 'adminInit'));
         add_action('admin_enqueue_scripts', array(&$this, 'addCssJS'));
         //add_filter('the_content', array(&$this, 'easterEgg')); //For testing purposes.

      }


      /*
         Function: init
            Initializes WordPress options and LIR table names.

         Inputs:
            wpdb   -   Takes the global variable $wpdb by reference to save on duplication.
      */
      private function init(&$wpdb = NULL) {
         //If these values are set then return.
         if(isset($this->options) && isset($this->table)) return;
         if($wpdb == NULL) global $wpdb;

         //Load options.
         $this->options = get_option(self::OPTIONS, NULL);

         //Prep table names.
         $this->table = array(
            'posts'  =>  $wpdb->prefix.self::SLUG.self::TABLE_POSTS,
            'meta'   =>  $wpdb->prefix.self::SLUG.self::TABLE_META
         );
      }


      /*
         Function: activationHook
            Checks to make sure WordPress is compatible, sets up tables, and sets up options.
				**STATIC FUNCTION**
      */
      public static function activationHook() {
         if(!current_user_can('manage_options'))
            wp_die('You do not have sufficient permissions to access this page.');

         //Make sure compatible WordPress version.
         global $wp_version;
         if(version_compare($wp_version, '3.6', '<'))
            wp_die('This plugin requires WordPress version 3.6 or higher.');

         //Create default options for wp_options if they don't already exist.
         $options = array('debug'    =>  false,
                          'version'  =>  self::VERSION,
                          'name'     =>  self::NAME,
                          'slug'     =>  self::SLUG);

         add_option(self::OPTIONS, $options, '', 'no');
         $options = get_option(self::OPTIONS);

         //CHECK VERSION NUMBER AND UPDATE DATABASE IF NEEDED
			//So far not needed.

         //Add LIR tables to the database if they do not exist.
         global $wpdb;
         require_once(ABSPATH.'wp-admin/includes/upgrade.php'); //Required for dbDelta.

         //Post table.
         $query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.self::SLUG.self::TABLE_POSTS." (
                     id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                     librarian_name varchar(255) NOT NULL,
                     librarian2_name varchar(255) DEFAULT NULL,
                     instructor_name varchar(255) NOT NULL,
                     instructor_email varchar(255) DEFAULT NULL,
                     instructor_phone varchar(255) DEFAULT NULL,
                     class_start datetime NOT NULL,
                     class_end datetime NOT NULL,
                     class_location varchar(255) NOT NULL,
                     class_type varchar(255) NOT NULL,
                     audience varchar(255) NOT NULL,
                     class_description mediumtext,
                     department_group varchar(255) NOT NULL,
                     course_number varchar(255) DEFAULT NULL,
                     bool1 tinyint(1) NOT NULL DEFAULT '0',
                     bool2 tinyint(1) NOT NULL DEFAULT '0',
                     bool3 tinyint(1) NOT NULL DEFAULT '0',
                     attendance smallint(6) NOT NULL DEFAULT '-1',
                     owner_id bigint(20) NOT NULL,
                     last_updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                     last_updated_by varchar(255) NOT NULL,
                     PRIMARY KEY  (id)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

         dbDelta($query);

         //Meta table.
         $query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.self::SLUG.self::TABLE_META." (
                     field varchar(255) NOT NULL,
                     value mediumtext NOT NULL,
                     PRIMARY KEY  (field)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

         dbDelta($query);
      }


      /*
         Function: deactivationHook
            Currently used for testing purposes only.
				**STATIC FUNCTION**
      */
      public static function deactivationHook() {
         if(!current_user_can('manage_options'))
            wp_die('You do not have sufficient permissions to access this page.');

         //Remove options saved in wp_options table.
         //delete_option(self::OPTIONS);

         //Remove custom database tables (post & meta).
         //global $wpdb;
         //$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.self::SLUG.'_posts'.", ".$wpdb->prefix.self::SLUG.'_meta');
      }


      /*
         Function: uninstallHook
            Used to cleanup items after uninstalling the plugin (databases, wp_options, &c).
				**STATIC FUNCTION**
      */
      public static function uninstallHook() {
         if(!current_user_can('manage_options') || !defined('WP_UNINSTALL_PLUGIN'))
            wp_die('You do not have sufficient permissions to access this page.');

         //Remove options saved in wp_options table.
         delete_option(self::OPTIONS);

         //Remove custom database tables (post & meta).
         global $wpdb;
         $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.self::SLUG.self::TABLE_POSTS.", ".$wpdb->prefix.self::SLUG.self::TABLE_META);
      }


      /*
         Function: createMenu
            Creates a menu and submenu on the dashboard for plugin usage and administration.

         See Also:
            <defaultPage>, <addClassPage>, <reportsPage>, <fieldsPage>, and <settingsPage>
      */
      public function createMenu() {
         $this->init();

         //Changes language of "add a class" on the submenu when editing a class.
         $addClassName = $_GET['edit'] ? 'Add/Edit a Class' : 'Add a Class';

         add_menu_page('', $this->options['slug'], 'edit_posts', self::SLUG, array(&$this, 'defaultPage'), '', '58.992');

         //Added so the first submenu item does not have the same title as the main menu item.
         add_submenu_page(self::SLUG, 'Upcoming Classes', 'Upcoming Classes', 'edit_posts', self::SLUG, array(&$this, 'defaultPage'));
         add_submenu_page(self::SLUG, $addClassName, $addClassName, 'edit_posts', self::SLUG.'-add-a-class', array(&$this, 'addClassPage'));
         add_submenu_page(self::SLUG, 'Reports', 'Reports', 'edit_posts', self::SLUG.'-reports', array(&$this, 'reportsPage'));
         add_submenu_page(self::SLUG, 'Fields', 'Fields', 'manage_options', self::SLUG.'-fields', array(&$this, 'fieldsPage'));
         add_submenu_page(self::SLUG, 'Settings', 'Settings', 'manage_options', self::SLUG.'-settings', array(&$this, 'settingsPage'));
      }


      /*
         Function: adminInit
            Registers an option group so that the settings page functions and can be sanitized.
      */
      public function adminInit() {
         register_setting(self::OPTIONS_GROUP, self::OPTIONS, array(&$this, 'sanitizeSettings'));
      }


      /*
         Function: addCssJS
            Adds custom CSS and JavaScript links to LIR pages.  Adds jQuery, jQueryUI, and jQueryUI dialog
            & datepicker from the local WordPress scripts repository.  Custom added CSS for for styling jQueryUI
            dialog and datepicker.
      */
      public function addCssJS() {
         global $parent_file;

         if($parent_file != self::SLUG) { return; }

         wp_enqueue_script(self::SLUG.'-admin-JS', plugins_url('js/admin.js', __FILE__), array('jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog'), self::VERSION);
         wp_enqueue_style(self::SLUG.'-admin-CSS', plugins_url('css/admin.css', __FILE__), array(), self::VERSION);
         wp_enqueue_style(self::SLUG.'-jquery-ui-redmond', plugins_url('css/jquery-ui/redmond/jquery-ui.min.css', __FILE__), false, '1.10.3');
      }


      /*
         Function: defaultPage
            The default page displayed when clicking on the LIR menu item.  This page shows
            a list of upcoming classes while allowing users to see the details, edit entries,
            and delete entries.

         Outputs:
            HTML for the default (upcoming classes) page.
      */
      public function defaultPage() {
         if(!current_user_can('edit_posts')) {
            wp_die('You do not have sufficient permissions to access this page.');
			}

         global $wpdb, $current_user;
         $this->init($wpdb);
         get_currentuserinfo();
         $baseUrl = admin_url('admin.php?page='.self::SLUG);
         $delete = (!empty($_GET['delete'])) ? $_GET['delete'] : NULL;


         /*
            Handle deletion if present.
         */
         if($delete) {
            $query = $wpdb->prepare("SELECT * FROM ".$this->table['posts']." WHERE id = %d", $delete);
            $class = $wpdb->get_row($query);

            //Check if the user has permissions to remove the class and verify the nonce.
            if((current_user_can('manage_options') || $current_user->id == $class->owner_id) && wp_verify_nonce($_GET['n'], self::SLUG.'-delete-'.$delete)) {
               $classRemoved = $wpdb->delete($this->table['posts'], array('id' => $delete), '%d');
            }
         }


         /*
            Sorting statements.
         */
         $orderBy = $_GET['orderby'];

         //Set default order by if non-default view with no order already selected.
         if(!$orderBy && ($_GET['incomplete'] || $_GET['previous']))   $orderBy = 'dateDesc';

         if($orderBy == 'department')            $orderQ = 'department_group, course_number, class_start, class_end';
         else if($orderBy == 'departmentDesc')   $orderQ = 'department_group DESC, course_number, class_start, class_end';
         else if($orderBy == 'course')            $orderQ = 'course_number, class_start, class_end';
         else if($orderBy == 'courseDesc')      $orderQ = 'course_number DESC, class_start, class_end';
         else if($orderBy == 'date')            $orderQ = 'class_start, class_end';
         else if($orderBy == 'dateDesc')         $orderQ = 'class_start DESC, class_end';
         else if($orderBy == 'librarian')         $orderQ = 'librarian_name, class_start, class_end';
         else if($orderBy == 'librarianDesc')   $orderQ = 'librarian_name DESC, class_start, class_end';
         else if($orderBy == 'instructor')      $orderQ = 'instructor_name, class_start, class_end';
         else if($orderBy == 'instructorDesc')   $orderQ = 'instructor_name DESC, class_start, class_end';
         else                                    $orderQ = 'class_start, class_end';


         /*
            Queries to display the listings and also to give the appropriate counts for upcoming, incomplete, and previous classes.
         */
         $upcoming = $wpdb->get_results("SELECT * FROM ".$this->table['posts']." WHERE NOW() <= class_end ORDER BY ".$orderQ);
         $upcomingCount = $wpdb->num_rows;
         $incomplete = $wpdb->get_results("SELECT * FROM ".$this->table['posts']." WHERE NOW() > class_end AND attendance = -1 ORDER BY ".$orderQ);
         $incompleteCount = $wpdb->num_rows;
         $previous = $wpdb->get_results("SELECT * FROM ".$this->table['posts']." WHERE NOW() > class_end ORDER BY ".$orderQ);
         $previousCount = $wpdb->num_rows;
         $myclasses = $wpdb->get_results("SELECT * FROM ".$this->table['posts']." WHERE owner_id = ".$current_user->id." ORDER BY ".$orderQ);
         $myclassesCount = $wpdb->num_rows;

         //Pick list to display and setup for link persistence.
         $mode = '';
         if($_GET['incomplete']) {
            $result = $incomplete;
            $mode .= '&incomplete=1';
         }
         else if($_GET['previous']) {
            $result = $previous;
            $mode .= '&previous=1';
         }
         else if($_GET['myclasses']) {
            $result = $myclasses;
            $mode .= '&myclasses=1';
         }
         else
            $result = $upcoming;

         //Query to get bool names.
         $boolInfo = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']. " WHERE field = 'bool_info'"));

         ?>
         <div class="wrap">
            <h2><?= $this->options['name']; ?> <a href="<?= $baseUrl.'-add-a-class'; ?>" class="add-new-h2">Add a Class</a></h2>

            <?php
            /*
               Success or error message for removing a class.
            */
            if($delete && $classRemoved) {
               echo '<div id="message" class="updated">
                  <p><strong>The class has been removed!</strong></p>
               </div>';
            }
            else if($delete && !$classRemoved) {
               echo '<div id="message" class="error">
                  <p><strong>There was a problem removing the class.</strong></p>
               </div>';
            }
            ?>

            <ul class="subsubsub">
               <?php
               //Upcoming classes.
               echo '<li><a href="'.$baseUrl.'"';
               if(!$_GET['incomplete'] && !$_GET['previous'] && !$_GET['myclasses']) echo ' class="current"';
               echo '>Upcoming <span class="count">('.$upcomingCount.')</span></a> |</li>';
               //Incomplete classes.
               echo '<li><a href="'.$baseUrl.'&incomplete=1"';
               if($_GET['incomplete'] == '1') echo ' class="current"';
               echo '>Incomplete <span class="count">('.$incompleteCount.')</span></a> |</li>';
               //Previous classes.
               echo '<li><a href="'.$baseUrl.'&previous=1"';
               if($_GET['previous'] == '1') echo ' class="current"';
               echo '>Previous <span class="count">('.$previousCount.')</span></a> |</li>';
               //My classes.
               echo '<li><a href="'.$baseUrl.'&myclasses=1"';
               if($_GET['myclasses'] == '1') echo ' class="current"';
               echo '>My Classes <span class="count">('.$myclassesCount.')</span></a></li>';
               ?>
            </ul>

            <p class="search-box">
               <input type="search" name="s" value="in development" />
               <input type="submit" class="button" value="?" />
            </p>

            <table class="widefat fixed">
               <?php
               $tableHF = '<th class="check-column">&nbsp;</th>';

               /*
                  The following section is used to created the table headers and footers with sorting functionality.
               */
               //Department/Group THead & TFoot
               if($orderBy == 'department')
                  $tableHF .= '<th class="sorted asc"><a href="'.$baseUrl.'&orderby=departmentDesc'.$mode.'"><span>Department/Group</span><span class="sorting-indicator"></span></a></th>';
               else if($orderBy == 'departmentDesc')
                  $tableHF .= '<th class="sorted desc"><a href="'.$baseUrl.'&orderby=department'.$mode.'"><span>Department/Group</span><span class="sorting-indicator"></span></a></th>';
               else
                  $tableHF .= '<th class="sortable desc"><a href="'.$baseUrl.'&orderby=department'.$mode.'"><span>Department/Group</span><span class="sorting-indicator"></span></a></th>';
               //Course # THead & TFoot
               if($orderBy == 'course')
                  $tableHF .= '<th class="sorted asc"><a href="'.$baseUrl.'&orderby=courseDesc'.$mode.'"><span>Course #</span><span class="sorting-indicator"></span></a></th>';
               else if($orderBy == 'courseDesc')
                  $tableHF .= '<th class="sorted desc"><a href="'.$baseUrl.'&orderby=course'.$mode.'"><span>Course #</span><span class="sorting-indicator"></span></a></th>';
               else
                  $tableHF .= '<th class="sortable desc"><a href="'.$baseUrl.'&orderby=course'.$mode.'"><span>Course #</span><span class="sorting-indicator"></span></a></th>';
               //Date/Time THead & TFoot
               if(!isset($orderBy) || $orderBy == 'date')
                  $tableHF .= '<th class="date-column sorted asc"><a href="'.$baseUrl.'&orderby=dateDesc'.$mode.'"><span>Date/Time</span><span class="sorting-indicator"></span></a></th>';
               else if($orderBy == 'dateDesc')
                  $tableHF .= '<th class="date-column sorted desc"><a href="'.$baseUrl.'&orderby=date'.$mode.'"><span>Date/Time</span><span class="sorting-indicator"></span></a></th>';
               else
                  $tableHF .= '<th class="date-column sortable desc"><a href="'.$baseUrl.'&orderby=date'.$mode.'"><span>Date/Time</span><span class="sorting-indicator"></span></a></th>';
               //Primary Librarian THead & TFoot
               if($orderBy == 'librarian')
                  $tableHF .= '<th class="sorted asc"><a href="'.$baseUrl.'&orderby=librarianDesc'.$mode.'"><span>Primary Librarian</span><span class="sorting-indicator"></span></a></th>';
               else if($orderBy == 'librarianDesc')
                  $tableHF .= '<th class="sorted desc"><a href="'.$baseUrl.'&orderby=librarian'.$mode.'"><span>Primary Librarian</span><span class="sorting-indicator"></span></a></th>';
               else
                  $tableHF .= '<th class="sortable desc"><a href="'.$baseUrl.'&orderby=librarian'.$mode.'"><span>Primary Librarian</span><span class="sorting-indicator"></span></a></th>';
               //Instructor THead & TFoot
               if($orderBy == 'instructor')
                  $tableHF .= '<th class="sorted asc"><a href="'.$baseUrl.'&orderby=instructorDesc'.$mode.'"><span>Instructor</span><span class="sorting-indicator"></span></a></th>';
               else if($orderBy == 'instructorDesc')
                  $tableHF .= '<th class="sorted desc"><a href="'.$baseUrl.'&orderby=instructor'.$mode.'"><span>Instructor</span><span class="sorting-indicator"></span></a></th>';
               else
                  $tableHF .= '<th class="sortable desc"><a href="'.$baseUrl.'&orderby=instructor'.$mode.'"><span>Instructor</span><span class="sorting-indicator"></span></a></th>';

               $tableHF .= '<th>Options</th>';

               echo '<thead><tr>'.$tableHF.'</tr></thead>';
               echo '<tfoot><tr>'.$tableHF.'</tr></tfoot>';
               echo '<tbody>';

               //Post a table row for each class in $result.
               foreach($result as $class) {
                  if($class->class_description)
                     echo '<tr class="'.self::SLUG.'-'.$class->id.'" title="'.$class->class_description.'">';
                  else
                     echo '<tr class="'.self::SLUG.'-'.$class->id.'">';

                  echo '<th>&nbsp;</th>
                        <td name="Department-Group">'.$class->department_group.'</td>';

                  //Assign name=skip if not present for details.
                  if($class->course_number)
                     echo '<td name="Course_Number">'.$class->course_number.'</td>';
                  else
                     echo '<td name="skip">&nbsp;</td>';

                  //Class location, class type, audience for details.
                  echo '<td name="Class_Location" class="LIR-hide">'.$class->class_location.'</td>
                        <td name="Class_Type" class="LIR-hide">'.$class->class_type.'</td>
                        <td name="Audience" class="LIR-hide">'.$class->audience.'</td>';

                  //Display start date & time - end date & time.
                  if(substr($class->class_start, 0, 10) == substr($class->class_end, 0, 10)) {
                     echo '<td name="Date-Time">'.date('n/j/Y (D) g:i A - ', strtotime($class->class_start));
                     echo date('g:i A', strtotime($class->class_end)).'</td>';
                  }
                  else { //If the end time is not on the same day as the start time.
                     echo '<td name="Date-Time">'.date('n/j/Y (D) g:i A -', strtotime($class->class_start)).'<br />';
                     echo date('n/j/Y (D) g:i A', strtotime($class->class_end)).'</td>';
                  }

                  echo '<td name="Primary_Librarian">'.$class->librarian_name.'</td>';

                  //Secondary librarian if present (for details).
                  if($class->librarian2_name)
                     echo '<td name="Secondary_Librarian" class="LIR-hide">'.$class->librarian2_name.'</td>';

                  //Instructor name and email.
                  if($class->instructor_name && $class->instructor_email) {
                     $mailto = esc_attr('mailto:'.$class->instructor_name.' <'.$class->instructor_email.'>');
                     echo '<td name="Instructor"><a href="'.$mailto.'">'.$class->instructor_name.'</a></td>';
                  }
                  else {
                     echo '<td name="Instructor">'.$class->instructor_name.'</td>';
                  }

                  //Instructor email for details.
                  if($class->instructor_email)
                     echo '<td name="Instructor_Email" class="LIR-hide">'.$class->instructor_email.'</td>';

                  //Instructor phone for details.
                  if($class->instructor_phone)
                     echo '<td name="Instructor_Phone" class="LIR-hide">'.$class->instructor_phone.'</td>';

                  //Bools for details.
                  if($boolInfo['bool1Enabled']) {
                     echo '<td name="'.esc_attr(str_replace(array('"', '/', ' '), array('', '-', '_'), $boolInfo['bool1Value'])).'" class="LIR-hide">';

                     if($class->bool1)
                        echo 'yes';
                     else
                        echo 'no';

                     echo '</td>';
                  }
                  if($boolInfo['bool2Enabled']) {
                     echo '<td name="'.esc_attr(str_replace(array('"', '/', ' '), array('', '-', '_'), $boolInfo['bool2Value'])).'" class="LIR-hide">';

                     if($class->bool2)
                        echo 'yes';
                     else
                        echo 'no';

                     echo '</td>';
                  }
                  if($boolInfo['bool3Enabled']) {
                     echo '<td name="'.esc_attr(str_replace(array('"', '/', ' '), array('', '-', '_'), $boolInfo['bool3Value'])).'" class="LIR-hide">';

                     if($class->bool3)
                        echo 'yes';
                     else
                        echo 'no';

                     echo '</td>';
                  }

                  //Attendance for details.
                  if($class->attendance != -1)
                     echo '<td name="Attendance" class="LIR-hide">'.$class->attendance.'</td>';
                  else
                     echo '<td name="Attendance" class="LIR-hide">no attendance recorded</td>';

                  //Description for details.
                  if($class->class_description)
                     echo '<td name="Class_Description" class="LIR-hide">'.$class->class_description.'</td>';

                  //Last updated for details.
                  echo '<td name="Last_Updated" class="LIR-hide">'.date('n/j/Y g:i A', strtotime($class->last_updated)).'</td>';

                  //Start Options section.
                  echo '<td name="skip"><a class="detailsLink" href="#" onclick="showDetails(\''.self::SLUG.'-'.$class->id.'\')">Details</a>';

                  //Edit and delete links for classes.
                  if($class->owner_id == $current_user->id || current_user_can('manage_options')) {
                     $extras = '';
                     if($orderBy)    $extras .= '&orderby='.$orderBy;
                     if($mode)      $extras .= $mode;

                     echo '&nbsp;&nbsp;<a href="'.$baseUrl.'-add-a-class&edit='.$class->id.'">Edit</a>&nbsp;&nbsp;<a href="#" class="removeLink" onclick="removeClass(\''.$baseUrl.$extras.'&delete='.$class->id.'&n='.wp_create_nonce(self::SLUG.'-delete-'.$class->id).'\')">Delete</a>';
                  }

                  echo '</td></tr>';
               }//End foreach loop.
               ?>

               </tbody>
            </table>
         </div>
         <?php
      }


      /*
         Function: addClassPage
            The add a class page allows users to add a class to the instruction recorder. This
            page is also used for editing existing entries (from the upcoming classes page).

         Outputs:
            HTML for the add a class page.

         See Also:
            <addUpdateClass>
      */
      public function addClassPage() {
         if(!current_user_can('edit_posts'))
            wp_die('You do not have sufficient permissions to access this page.');

         global $user_identity, $wpdb, $current_user;
         $this->init($wpdb);
         get_currentuserinfo();
         $baseUrl = admin_url('admin.php?page='.self::SLUG.'-add-a-class');
         $classAdded = NULL;
         $error = array();


         /*
            Edit a class setup and permission checking.
         */
         if($_GET['edit'] && !$_POST['edit']) {
            $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$this->table['posts']." WHERE id = %d", $_GET['edit']));

            foreach($class as $x => $y) {
               $_POST[$x] = $y;
            }
         }


         /*
            Permission checking.
         */
         if(!current_user_can('manage_options') && $current_user->id != $class->owner_id) {
            array_push($error, 'You do not have sufficient permissions to edit this class. <a href="'.$baseUrl.'">Add a new class?</a>');
            $_POST['submitted'] = NULL; //Ensures the class is never processed for submission.
         }


         /*
            Submission handling.
         */
         if(isset($_POST['submitted']) && ($debug['nonce verified'] = wp_verify_nonce($_POST[self::SLUG.'_nonce'], self::SLUG.'_add_class'))) {
            $classAdded = false;

            //Check to make sure all required fields have been submitted.
            if(empty($_POST['librarian_name']))      array_push($error, 'Missing Field: Primary Librarian');
            if(empty($_POST['instructor_name']))   array_push($error, 'Missing Field: Instructor Name');
            if(empty($_POST['department_group']))   array_push($error, 'Missing Field: Department/Group');
            if(empty($_POST['class_date']))         array_push($error, 'Missing Field: Class Date');
            if(empty($_POST['class_time']))         array_push($error, 'Missing Field: Class Time');
            if(empty($_POST['class_length']))      array_push($error, 'Missing Field: Class Length');
            if(empty($_POST['class_location']))      array_push($error, 'Missing Field: Class Location');
            if(empty($_POST['class_type']))         array_push($error, 'Missing Field: Class Type');
            if(empty($_POST['audience']))            array_push($error, 'Missing Field: Audience');

            //Go to function to insert data into database.
            if(empty($error)) $classAdded = $this->addUpdateClass($_POST['edit']);
            //If database insert failed, push error.
            if(!$classAdded && empty($error) && $_POST['edit']) array_push($error, 'An error has occurred while trying to update the class. Please try again.');
            else if(!$classAdded && empty($error)) array_push($error, 'An error has occurred while trying to submit the class. Please try again.');
         }

         ?>
         <div class="wrap">
            <h2><?= ($_GET['edit']) ? 'Edit' : 'Add'; ?> a Class</h2>

            <?php
            //Added for debugging (if set).
            if($this->options['debug'] && (!empty($_POST) || !empty($debug))) {
               echo '<div id="message" class="error">';

               if(!empty($_POST)) {
                  echo '<p><strong>POST</strong></p>
                  <pre>'.print_r($_POST, true).'</pre>';
               }


               if(!empty($debug)) {
                  echo '<p><strong>Other</strong></p>';

                  foreach($debug as $x => $y)
                     echo '<p>'.$x.': '.$y.'</p>';
               }

               echo '<p>Last Query: '.$wpdb->last_query.'</p>';

               echo '</div>';
            }

            //Message if class was added.
            if($classAdded && !$_POST['edit']) {
               echo '<div id="message" class="updated">
                  <p><strong>The class has been added!</strong> Need to <a href="'.$baseUrl.'&edit='.$classAdded.'">edit it?</a> Would you like to <a href="'.$baseUrl.'">add a new class?</a></p>
               </div>';
            }
            //Message if class was updated.
            else if($classAdded !== false && $_POST['edit']) {
               echo '<div id="message" class="updated">
                  <p><strong>The class has been updated!</strong> Need to <a href="'.$baseUrl.'&edit='.$_POST['edit'].'">edit it</a> again? Would you like to <a href="'.$baseUrl.'">add a new class?</a></p>
               </div>';
            }
            //Message if an error occurred.
            else if(!empty($error)) {
               echo '<div id="message" class="error">
                  <p><strong>';

                  foreach($error as $e) echo $e.'<br />';

                  echo '</strong></p>
               </div>';
            }

            ?>
            <form action="" method="post">
               <table class="form-table">
                  <tr>
                     <th>*Primary Librarian</th>
                     <td><select name="librarian_name"><option value=""></option>
                     <?php
                     $user = $wpdb->get_results("SELECT display_name FROM ".$wpdb->prefix."users ORDER BY display_name");

                     foreach($user as $u) {
                        if($u->display_name == "admin") continue;
                        echo '<option value="'.$u->display_name.'"';

                        if(!$classAdded && ($u->display_name == $_POST['librarian_name']))
                           echo ' selected="selected"';
                        else if(($classAdded || !isset($_POST['librarian_name'])) && $u->display_name == $user_identity)
                           echo ' selected="selected"';

                        echo '>'.$u->display_name.'</option>';
                     }
                     ?>
                     </select></td>
                  </tr>
                  <tr>
                     <th>Secondary Librarian</th>
                     <td><select name="librarian2_name"><option value=""></option>
                     <?php
                     foreach($user as $u) {
                        if($u->display_name == "admin") continue;

                        if(!$classAdded && ($u->display_name == $_POST['librarian2_name']))
                           echo '<option value="'.$u->display_name.'" selected="selected">'.$u->display_name.'</option>';
                        else
                           echo '<option value="'.$u->display_name.'">'.$u->display_name.'</option>';                     }
                     ?>
                     </select></td>
                  </tr>
                  <tr>
                     <th>*Instructor Name</th>
                     <td><input type="text" name="instructor_name" value="<?php if(!$classAdded && !empty($_POST['instructor_name'])) echo $_POST['instructor_name']; ?>" /></td>
                  </tr>
                  <tr>
                     <th>Instructor Email</th>
                     <td><input type="email" name="instructor_email" value="<?php if(!$classAdded && !empty($_POST['instructor_email'])) echo $_POST['instructor_email']; ?>" /></td>
                  </tr>
                  <tr>
                     <th>Instructor Phone</th>
                     <td><input type="tel" name="instructor_phone" value="<?php if(!$classAdded && !empty($_POST['instructor_phone'])) echo $_POST['instructor_phone']; ?>" /></td>
                  </tr>
                  <tr>
                     <th>Class Description</th>
                     <td><textarea id="classDescription" name="class_description"><?php if(!$classAdded && !empty($_POST['class_description'])) echo $_POST['class_description']; ?></textarea></td>
                  </tr>
                  <tr>
                     <th>*Department/Group</th>
                     <td><select name="department_group">
                        <option value="">&nbsp;</option>
                        <?php
                        $departmentGroup = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'department_group_values'"));

                        foreach($departmentGroup as $x) {
                           if((esc_attr($x) == $_POST['department_group']) && !$classAdded)
                              echo '<option value="'.esc_attr($x).'" selected="selected">'.$x.'</option>';
                           else
                              echo '<option value="'.esc_attr($x).'">'.$x.'</option>';
                        }
                        ?>
                     </select></td>
                  </tr>
                  <tr>
                     <th>Course Number</th>
                     <td><input type="text" name="course_number" value="<?php if(!$classAdded && !empty($_POST['course_number'])) echo $_POST['course_number']; ?>" /></td>
                  </tr>
                  <tr>
                     <th>*Class Date (M/D/YYYY)</th>
                     <td>
                     <?php
                     echo '<input type="text" id="classDate" name="class_date" value="';

                     if(!$classAdded && !empty($_POST['class_date']))
                        echo $_POST['class_date'];
                     else if(!$classAdded && !empty($_POST['class_start'])) {
                        echo date('n/j/Y', strtotime($_POST['class_start']));
                     }
                     else
                        echo date('n/j/Y');

                     echo '" />';
                     ?>
                     </td>
                  </tr>
                  <tr>
                     <th>*Class Time (H:MM AM|PM)</th>
                     <?php
                     if(!$classAdded && !empty($_POST['class_time']))
                        $time = date('g:i A', strtotime($_POST['class_time']));
                     else if(!$classAdded && !empty($_POST['class_start'])) {
                        $time = date('g:i A', strtotime($_POST['class_start']));
                        $_POST['class_length'] = (strtotime($_POST['class_end']) - strtotime($_POST['class_start'])) / 60;
                     }
                     else {
                        date_default_timezone_set('EST5EDT');
                        $minutes = date('i', strtotime("+15 minutes")) - date('i', strtotime("+15 minutes")) % 15;
                        $time = date('g:', strtotime("+15 minutes")).(($minutes) ? $minutes : '00').date(' A');
                     }
                     ?>
                     <td><input type="text" name="class_time" value="<?= $time; ?>" /> <label>*Length</label>
                        <select name="class_length">
                           <option value="0">&nbsp;</option>
                           <option value="15" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '15')) echo 'selected="selected"'; ?>>15 minutes</option>
                           <option value="30" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '30')) echo 'selected="selected"'; ?>>30 minutes</option>
                           <option value="45" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '45')) echo 'selected="selected"'; ?>>45 minutes</option>
                           <option value="60" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '60')) echo 'selected="selected"'; ?>>1 hour</option>
                           <option value="75" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '75')) echo 'selected="selected"'; ?>>1 hour 15 minutes</option>
                           <option value="90" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '90')) echo 'selected="selected"'; ?>>1 hour 30 minutes</option>
                           <option value="105" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '105')) echo 'selected="selected"'; ?>>1 hour 45 minutes</option>
                           <option value="120" <?php if(!$classAdded && !empty($_POST['class_length']) && ($_POST['class_length'] == '120')) echo 'selected="selected"'; ?>>2 hours</option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <th>*Class Location</th>
                     <td><select name="class_location">
                        <option value="">&nbsp;</option>
                        <?php
                        $classLocation = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_location_values'"));

                        foreach($classLocation as $x) {
                           echo '<option value="'.esc_attr($x).'"';

                           if(!$classAdded && $_POST['class_location'] == esc_attr($x))
                              echo ' selected="selected"';

                           echo '>'.$x.'</option>';
                        }
                        ?>
                     </select></td>
                  </tr>
                  <tr>
                     <th>*Class Type</th>
                     <td><select name="class_type">
                        <option value="">&nbsp;</option>
                        <?php
                        $classType = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_type_values'"));

                        foreach($classType as $x) {
                           echo '<option value="'.esc_attr($x).'"';

                           if(!$classAdded && $_POST['class_type'] == esc_attr($x))
                              echo ' selected="selected"';

                           echo '>'.$x.'</option>';
                        }
                        ?>
                     </select></td>
                  </tr>
                  <tr>
                     <th>*Audience</th>
                     <td><select name="audience">
                        <option value="">&nbsp;</option>
                        <?php
                        $audience = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'audience_values'"));

                        foreach($audience as $x) {
                           echo '<option value="'.esc_attr($x).'"';

                           if(!$classAdded && $_POST['audience'] == esc_attr($x))
                              echo ' selected="selected"';

                           echo '>'.$x.'</option>';
                        }
                        ?>
                     </select></td>
                  </tr>
                  <?php
                  $bools = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'bool_info'"));

                  if($bools['bool1Enabled']) {
                  ?>
                  <tr>
                     <th><?= $bools['bool1Value']; ?></th>
                     <td><input type="checkbox" name="bool1" <?php if(!$classAdded && ($_POST['bool1'] == 'on' || $_POST['bool1'] == 1)) echo 'checked="checked"'; ?> />
                  </tr>
                  <?php
                  } if($bools['bool2Enabled']) {
                  ?>
                  <tr>
                     <th><?= $bools['bool2Value']; ?></th>
                     <td><input type="checkbox" name="bool2" <?php if(!$classAdded && ($_POST['bool2'] == 'on' || $_POST['bool2'] == 1)) echo 'checked="checked"'; ?> />
                  </tr>
                  <?php
                  } if($bools['bool3Enabled']) {
                  ?>
                  <tr>
                     <th><?= $bools['bool3Value']; ?></th>
                     <td><input type="checkbox" name="bool3" <?php if(!$classAdded && ($_POST['bool3'] == 'on' || $_POST['bool3'] == 1)) echo 'checked="checked"'; ?> />
                  </tr>
                  <?php
                  }
                  ?>
                  <tr>
                     <th>Number of Students Attended</th>
                     <td><input type="number" name="attendance" value="<?php if(!$classAdded && !empty($_POST['attendance']) && $_POST['attendance'] != -1) echo $_POST['attendance']; ?>" /></td>
                  </tr>
               </table>

               <?php wp_nonce_field(self::SLUG.'_add_class', self::SLUG.'_nonce'); ?>
               <?php if($_GET['edit']) echo '<input type="hidden" name="edit" value="'.$_GET['edit'].'" />'; ?>

               <p class="submit">
                  <input type="submit" name="submitted" class="button-primary" value="Submit" />&nbsp;&nbsp;
                  <input type="button" class="button-primary" value="Cancel" onclick="location.href = '<?= admin_url('admin.php?page='.self::SLUG); ?>'" />
               </p>
            </form>
         </div>
         <?php
      }


      /*
         Function: addUpdateClass
            Adds or updates a class listing in the database.  Handles the sanitation of
            all of the inputs.

         Inputs:
            id   -   The id of the entry being updated (if applicable).

         Returns:
            true   -   True if successfully added or updated an entry.
            false   -   False if entry was not added/updated.
      */
      private function addUpdateClass($id = NULL) {
         global $wpdb, $current_user;
         $this->init($wpdb);
         get_currentuserinfo();

         $data = array(
            'librarian_name'      =>   $_POST['librarian_name'],
            'instructor_name'      =>   $_POST['instructor_name'],
            'class_location'      =>   $_POST['class_location'],
            'class_type'         =>   $_POST['class_type'],
            'audience'            =>   $_POST['audience'],
            'department_group'   =>   $_POST['department_group'],
            'last_updated_by'      =>   $current_user->user_email,
         );

         //Only mess with owner_id if new submission.
         if(!$id) $data['owner_id'] = $current_user->id;

         //$data['class_start'] = date('Y-m-d', strtotime($_POST['class_date'])).' '.$_POST['class_time'];
         $data['class_start'] = date('Y-m-d G:i', strtotime($_POST['class_date'].' '.$_POST['class_time']));
         $data['class_end'] = date('Y-m-d G:i', strtotime($data['class_start'].' +'.$_POST['class_length'].' minutes'));
         $data['bool1'] = isset($_POST['bool1']) ? 1 : 0;
         $data['bool2'] = isset($_POST['bool2']) ? 1 : 0;
         $data['bool3'] = isset($_POST['bool3']) ? 1 : 0;

         if(!empty($_POST['librarian2_name']))      $data['librarian2_name'] = $_POST['librarian2_name'];
         if(!empty($_POST['instructor_email']))      $data['instructor_email'] = $_POST['instructor_email'];
         if(!empty($_POST['instructor_phone']))      $data['instructor_phone'] = $_POST['instructor_phone'];
         if(!empty($_POST['class_description']))   $data['class_description'] = $_POST['class_description'];
         if(!empty($_POST['course_number']))         $data['course_number'] = $_POST['course_number'];
         if(!empty($_POST['attendance']))            $data['attendance'] = $_POST['attendance'];

         if($id)   $success = $wpdb->update($this->table['posts'], $data, array('id' => $id));
         else      $success = $wpdb->insert($this->table['posts'], $data);

         if($success && !$id)   return $wpdb->insert_id; //Returns auto increment number on successful insertion.
         else if($success)      return $success; //Returns number of rows updated on successful update.
         else                   return false; //Returns false if either update or insert failed.
      }


      /*
         Function: reportsPage
            Allows users to download Excel or CSV data for reporting purposes.

         Outputs:
            HTML for the reports page.
      */
      public function reportsPage() {
         if(!current_user_can('edit_posts'))
            wp_die('You do not have sufficient permissions to access this page.');

         global $wpdb;
         $this->init($wpdb);

         if($_POST['submitted']) {
            $result = $wpdb->get_results("SELECT * FROM ".$this->table['posts']." ORDER BY class_start, class_end", ARRAY_A);
            $f = fopen('php://output', 'w');

            foreach($result as $line)
               fputcsv($f, $line);

            fseek($f, 0);
            header('Content-Type: application/csv');
            header('Content-Disposition: attachement; filename="test.csv"');
            fpassthru($f);
         }

         ?>
         <div class="wrap">
            <h2>Reports</h2>
            <form action="" method="post">
               <p class="submit">
                  <input type="submit" name="submitted" class="button-primary" value="Gimme That Report!" />
               </p>
            </form>
         </div>
         <?php
      }


      /*
         Function: fieldsPage
            Allows manipulation of the adjustable fields.

         Outputs:
            HTML for the fields page.
      */
      public function fieldsPage() {
         if(!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
			}

         global $wpdb;
         $this->init($wpdb);


         /*
            Get current fields from database.
         */
         $departmentGroup = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'department_group_values'"));
         $classLocation = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_location_values'"));
         $classType = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_type_values'"));
         $audience = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'audience_values'"));
         $bools = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'bool_info'"));


         /*
            Check for form submission and do appropriate action.
         */
         if(isset($_POST[self::SLUG.'_nonce']) && wp_verify_nonce($_POST[self::SLUG.'_nonce'], self::SLUG.'_fields')) {
            //Add a department / group field to the database.
            if(!empty($_POST['deptGroupAdd']) && !empty($_POST['deptGroupTB'])) { //CHECK FOR WHITESPACE
               if($departmentGroup) {
                  array_push($departmentGroup, $_POST['deptGroupTB']);
                  natcasesort($departmentGroup);
                  $wpdb->update($this->table['meta'], array('value' => serialize($departmentGroup)), array('field' => 'department_group_values'));
               }
               else {
                  $departmentGroup = array();
                  array_push($departmentGroup, $_POST['deptGroupTB']);
                  $wpdb->insert($this->table['meta'], array('field' => 'department_group_values', 'value' => serialize($departmentGroup)));
               }
            }
            //Remove a department / group field from the database.
            else if(!empty($_POST['deptGroupRemove'])) {
               $temp = $_POST['deptGroupSB'] + 1;

               if(!empty($temp)) {
                  unset($departmentGroup[$_POST['deptGroupSB']]);

                  if($departmentGroup)
                     $wpdb->update($this->table['meta'], array('value' => serialize($departmentGroup)), array('field' => 'department_group_values'));
                  else
                     $wpdb->delete($this->table['meta'], array('field' => 'department_group_values'));
               }
            }
            //Add a class location field to the database.
            if(!empty($_POST['classLocAdd']) && !empty($_POST['classLocTB'])) { //CHECK FOR WHITESPACE
               if($classLocation) {
                  array_push($classLocation, $_POST['classLocTB']);
                  natcasesort($classLocation);
                  $wpdb->update($this->table['meta'], array('value' => serialize($classLocation)), array('field' => 'class_location_values'));
               }
               else {
                  $classLocation = array();
                  array_push($classLocation, $_POST['classLocTB']);
                  $wpdb->insert($this->table['meta'], array('field' => 'class_location_values', 'value' => serialize($classLocation)));
               }
            }
            //Remove a class location field from the database.
            else if(!empty($_POST['classLocRemove'])) {
               $temp = $_POST['classLocSB'] + 1;

               if(!empty($temp)) {
                  unset($classLocation[$_POST['classLocSB']]);

                  if($classLocation)
                     $wpdb->update($this->table['meta'], array('value' => serialize($classLocation)), array('field' => 'class_location_values'));
                  else
                     $wpdb->delete($this->table['meta'], array('field' => 'class_location_values'));
               }
            }
            //Add a class type field to the database.
            if(!empty($_POST['classTypeAdd']) && !empty($_POST['classTypeTB'])) { //CHECK FOR WHITESPACE
               if($classType) {
                  array_push($classType, $_POST['classTypeTB']);
                  natcasesort($classType);
                  $wpdb->update($this->table['meta'], array('value' => serialize($classType)), array('field' => 'class_type_values'));
               }
               else {
                  $classType = array();
                  array_push($classType, $_POST['classTypeTB']);
                  $wpdb->insert($this->table['meta'], array('field' => 'class_type_values', 'value' => serialize($classType)));
               }
            }
            //Remove a class type field from the database.
            else if(!empty($_POST['classTypeRemove'])) {
               $temp = $_POST['classTypeSB'] + 1;

               if(!empty($temp)) {
                  unset($classType[$_POST['classTypeSB']]);

                  if($classType)
                     $wpdb->update($this->table['meta'], array('value' => serialize($classType)), array('field' => 'class_type_values'));
                  else
                     $wpdb->delete($this->table['meta'], array('field' => 'class_type_values'));
               }
            }
            //Add an audience field to the database.
            if(!empty($_POST['audienceAdd']) && !empty($_POST['audienceTB'])) { //CHECK FOR WHITESPACE
               if($audience) {
                  array_push($audience, $_POST['audienceTB']);
                  natcasesort($audience);
                  $wpdb->update($this->table['meta'], array('value' => serialize($audience)), array('field' => 'audience_values'));
               }
               else {
                  $audience = array();
                  array_push($audience, $_POST['audienceTB']);
                  $wpdb->insert($this->table['meta'], array('field' => 'audience_values', 'value' => serialize($audience)));
               }
            }
            //Remove an audience field from the database.
            else if(!empty($_POST['audienceRemove'])) {
               $temp = $_POST['audienceSB'] + 1;

               if(!empty($temp)) {
                  unset($audience[$_POST['audienceSB']]);

                  if($audience)
                     $wpdb->update($this->table['meta'], array('value' => serialize($audience)), array('field' => 'audience_values'));
                  else
                     $wpdb->delete($this->table['meta'], array('field' => 'audience_values'));
               }
            }
            //Adds bool options to the database.
            else if(!empty($_POST['boolSave'])) {
               if(empty($bools)) $insert = true; else $insert = false;

               $bools['bool1Value'] = $_POST['bool1Value'];
               $bools['bool1Enabled'] = $_POST['bool1Enabled'];
               $bools['bool2Value'] = $_POST['bool2Value'];
               $bools['bool2Enabled'] = $_POST['bool2Enabled'];
               $bools['bool3Value'] = $_POST['bool3Value'];
               $bools['bool3Enabled'] = $_POST['bool3Enabled'];

               if($insert)
                  $wpdb->insert($this->table['meta'], array('field' => 'bool_info', 'value' => serialize($bools)));
               else
                  $wpdb->update($this->table['meta'], array('value' => serialize($bools)), array('field' => 'bool_info'));
            }
         }
         /*
            End submission of page.
         */


         ?>
         <div class="wrap">
            <h2>Fields</h2>

            <?php
            //Added for debugging (if set).
            if($this->options['debug'] && !empty($_POST)) {
               echo '<div id="message" class="error">';

               if(!empty($_POST)) {
                  echo '<p><strong>POST</strong></p>
                  <pre>'.print_r($_POST, true).'</pre>';
               }

               echo '</div>';
            }
            ?>

            <form action="" method="post">
               <h3>Department/Group</h3>
               <input name="deptGroupTB" type="text" />
               <input name="deptGroupAdd" type="submit" class="button-secondary" value="Add Dept/Group" /><br /><br />
               <select id="deptGroupSB" name="deptGroupSB" size="10">
               <?php
               $i = 1;
               foreach($departmentGroup as $i => $x) {
                  echo '<option value="'.$i.'">'.$x.'</option>';
               }
               ?>
               </select><br /><br />

               <input name="deptGroupRemove" type="submit" class="button-secondary" value="Remove Dept/Group" />
               <?php wp_nonce_field(self::SLUG.'_fields', self::SLUG.'_nonce'); ?>
            </form>

            <form action="" method="post">
               <h3>Class Location</h3>
               <input name="classLocTB" type="text" />
               <input name="classLocAdd" type="submit" class="button-secondary" value="Add Class Location" /><br /><br />
               <select id="classLocSB" name="classLocSB" size="10">
               <?php
               $i = 1;
               foreach($classLocation as $i => $x) {
                  echo '<option value="'.$i.'">'.$x.'</option>';
               }
               ?>
               </select><br /><br />

               <input name="classLocRemove" type="submit" class="button-secondary" value="Remove Class Location" />
               <?php wp_nonce_field(self::SLUG.'_fields', self::SLUG.'_nonce'); ?>
            </form>

            <form action="" method="post">
               <h3>Class Type</h3>
               <input name="classTypeTB" type="text" />
               <input name="classTypeAdd" type="submit" class="button-secondary" value="Add Class Type" /><br /><br />
               <select id="classTypeSB" name="classTypeSB" size="10">
               <?php
               $i = 1;
               foreach($classType as $i => $x) {
                  echo '<option value="'.$i.'">'.$x.'</option>';
               }
               ?>
               </select><br /><br />

               <input name="classTypeRemove" type="submit" class="button-secondary" value="Remove Class Type" />
               <?php wp_nonce_field(self::SLUG.'_fields', self::SLUG.'_nonce'); ?>
            </form>

            <form action="" method="post">
               <h3>Audience</h3>
               <input name="audienceTB" type="text" />
               <input name="audienceAdd" type="submit" class="button-secondary" value="Add Audience" /><br /><br />
               <select id="audienceSB" name="audienceSB" size="10">
               <?php
               $i = 1;
               foreach($audience as $i => $x) {
                  echo '<option value="'.$i.'">'.$x.'</option>';
               }
               ?>
               </select><br /><br />

               <input name="audienceRemove" type="submit" class="button-secondary" value="Remove Audience" />
               <?php wp_nonce_field(self::SLUG.'_fields', self::SLUG.'_nonce'); ?>
            </form>

            <form action="" method="post">
               <h3>Bools</h3>
               <h4>Bool 1</h4>
               <p><label>Name: <input type="text" name="bool1Value" value="<?= $bools['bool1Value']; ?>" /></label>
               <label>Enabled <input type="radio" name="bool1Enabled" value="1" <?php if($bools['bool1Enabled']) echo 'checked="checked "'; ?> /></label>
               <label>Disabled <input type="radio" name="bool1Enabled" value="0" <?php if(!$bools['bool1Enabled']) echo 'checked="checked "'; ?> /></p></label>

               <h4>Bool 2</h4>
               <p><label>Name: <input type="text" name="bool2Value" value="<?= $bools['bool2Value']; ?>" /></label>
               <label>Enabled <input type="radio" name="bool2Enabled" value="1" <?php if($bools['bool2Enabled']) echo 'checked="checked "'; ?> /></label>
               <label>Disabled <input type="radio" name="bool2Enabled" value="0" <?php if(!$bools['bool2Enabled']) echo 'checked="checked "'; ?> /></p></label>

               <h4>Bool 3</h4>
               <p><label>Name: <input type="text" name="bool3Value" value="<?= $bools['bool3Value']; ?>" /></label>
               <label>Enabled <input type="radio" name="bool3Enabled" value="1" <?php if($bools['bool3Enabled']) echo 'checked="checked "'; ?> /></label>
               <label>Disabled <input type="radio" name="bool3Enabled" value="0" <?php if(!$bools['bool3Enabled']) echo 'checked="checked "'; ?> /></p></label>

               <input name="boolSave" type="submit" class="button-secondary" value="Save Bools" />
               <?php wp_nonce_field(self::SLUG.'_fields', self::SLUG.'_nonce'); ?>
            </form>
         </div>
         <?php
      }


      /*
         Function: settingsPage
            Controls what shows up on the settings page of this plugin.

         Outputs:
            HTML for adminstration page settings.

         See Also:
            <sanitizeSettings>
      */
      public function settingsPage() {
         if(!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
			}

         $this->init();

         ?>
         <div class="wrap">
            <h2>Settings</h2>
            <form method="post" action="options.php">
               <?php settings_fields(self::OPTIONS_GROUP); ?>
               <table class="form-table">
                  <tr>
                     <th scope="row">Plugin Name</th>
                     <td><input type="text" name="<?= self::OPTIONS.'[name]'; ?>" value="<?= $this->options['name']; ?>" /></td>
                  </tr>
                  <tr>
                     <th scope="row">Plugin Slug</th>
                     <td><input type="text" name="<?= self::OPTIONS.'[slug]'; ?>" value="<?= $this->options['slug']; ?>" /></td>
                  </tr>
                  <tr>
                     <th scope="row">Debugging</th>
                     <td><input type="checkbox" name="<?= self::OPTIONS.'[debug]'; ?>" <?php checked($this->options['debug'], 'on'); ?> /> Enabled</td>
                  </tr>
               </table>

               <p class="submit">
                  <input type="submit" class="button-primary" value="Save Changes" />
               </p>
            </form>
         </div>
         <?php
      }


      /*
         Function: sanitizeSettings
            Sanitizes all inputs that are run through the settings page.

         Inputs:
            input   -   Array of options from the LIR settings page.

         Returns:
            array   -   Array of sanitized options.
      */
      public function sanitizeSettings($input) {
         $input['name'] = sanitize_text_field($input['name']);
         $input['slug'] = sanitize_text_field($input['slug']);
         $input['debug'] = ($input['debug'] == 'on') ? 'on' : '';

         return $input;
      }


      /*
         Function: easterEgg
            Test filter function that adds text to the end of content.

         Inputs:
            input      -   A string containing content.

         Returns:
            string   -   A modified string of content.
      */
      public function easterEgg($input = '') {
         return $input . "<p id='Lrrr'><i>I am Lrrr, ruler of the planet Omicron Persei 8!</i></p>";
      }
   }


   $LIR = new LIR();  //Create object only if class did not already exist.
}
else {
   //DISPLAY ERROR MESSAGE ABOUT CONFLICTING PLUGIN NAME
   return; //should return to parent script
}
