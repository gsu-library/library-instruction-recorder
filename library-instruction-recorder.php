<?php
/*
   Plugin Name: Library Instruction Recorder
   Plugin URI: http://bitbucket.org/gsulibwebmaster/library-instruction-recorder
   Description: A plugin for recording library instruction events and their associated data.
   Version: 0.1.0
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
		//Do not change these variables. The plugin name and slug can be changed in the LIR settings.
		const NAME = 'Library Instruction Recorder';
		const SLUG = 'LIR';
		const OPTIONS = 'lir_options';
		const OPTIONS_GROUP = 'lir_options_group';
		const VERSION = '0.1.0';
		private $options;
		private $table;


		/*
			Constructor: __construct
				Adds register hooks, actions, and filters to WP upon construction. Also loads the options for the plugin.
		*/
		public function __construct() {
			//Registration hooks.
			register_activation_hook(__FILE__, array(&$this, 'activationHook'));
			register_deactivation_hook(__FILE__, array(&$this, 'deactivationHook'));
			register_uninstall_hook(__FILE__, array(&$this, 'uninstallHook'));

			//Actions and filters.
			add_action('admin_menu', array(&$this, 'createMenu'));
			add_action('admin_init', array(&$this, 'adminInit'));
			add_action('admin_enqueue_scripts', array(&$this, 'addJSCSS'));
			add_filter('the_content', array(&$this, 'easterEgg')); //For testing purposes.


			//MOVE BELOW THIS LINE INTO AN INIT?
			//Load options.
			$this->options = get_option(self::OPTIONS, NULL);

			//Prep table names.
			global $wpdb;
			$this->table = array(
				'posts'	=>	$wpdb->prefix.self::SLUG.'_posts',
				'meta'	=>	$wpdb->prefix.self::SLUG.'_meta'
			);
		}


		/*
			Function: activationHook
				Checks to make sure WordPress is compatible, sets up tables, and sets up options.
		*/
		public static function activationHook() {
			if(!current_user_can('manage_options'))
				wp_die('You do not have sufficient permissions to access this page.');

			global $wp_version;
			if(version_compare($wp_version, '3.6', '<'))
				wp_die('This plugin requires WordPress version 3.6 or higher.');

			//Create default options for wp_options if they don't already exist.
			$options = array(	'debug'		=> false,
									'version'	=> self::VERSION,
									'name'		=> self::NAME,
									'slug'		=> self::SLUG );

			add_option(self::OPTIONS, $options, '', 'no');
			$options = get_option(self::OPTIONS);

			//CHECK VERSION NUMBER AND UPDATE DATABASE IF NEEDED
			//Add LIR tables to the database if they do not exist.
			global $wpdb;
			require_once(ABSPATH.'wp-admin/includes/upgrade.php'); //Required for dbDelta.

			//Post table.
			$query =	"CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.self::SLUG.'_posts'." (
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
			$query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.self::SLUG.'_meta'." (
							field varchar(255) NOT NULL,
							value mediumtext NOT NULL,
							PRIMARY KEY  (field)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

			dbDelta($query);
		}


		/*
			Function: deactivationHook
				Used to cleanup items after deactivating the plugin. Also is used for testing
				purposes.
		*/
		public static function deactivationHook() {
			if(!current_user_can('manage_options'))
				wp_die('You do not have sufficient permissions to access this page.');

			//MAKE THIS A DEBUG STATEMENT?
			//Remove options saved in wp_options table.
			//delete_option(self::OPTIONS);

			//Remove custom database tables (post & meta).
			//global $wpdb;
			//$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.self::SLUG.'_posts'.", ".$wpdb->prefix.self::SLUG.'_meta');
		}


		/*
			Function: uninstallHook
				Used to cleanup items after uninstalling the plugin (databases, wp_options, &c.).
		*/
		public static function uninstallHook() {
			if(!current_user_can('manage_options') || !defined('WP_UNINSTALL_PLUGIN'))
				wp_die('You do not have sufficient permissions to access this page.');

			//Remove options saved in wp_options table.
			delete_option(self::OPTIONS);

			//Remove custom database tables (post & meta).
			global $wpdb;
			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.self::SLUG.'_posts'.", ".$wpdb->prefix.self::SLUG.'_meta');
		}


		/*
			Function: createMenu
				Creates a menu and submenu on the dashboard for plugin usage and administration.

			See Also:
				<defaultPage>, <addClassPage>, <fieldsPage>, and <settingsPage>
		*/
		public function createMenu() {
			add_menu_page('', $this->options['slug'], 'edit_posts', self::SLUG, array(&$this, 'defaultPage'), '', '58.992');
			//Added so the first submenu item does not have the same title as the main menu item.
			add_submenu_page(	self::SLUG, 'Upcoming Classes', 'Upcoming Classes', 'edit_posts',
									self::SLUG, array(&$this, 'defaultPage'));
			add_submenu_page(	self::SLUG, 'Add a Class', 'Add a Class', 'edit_posts',
									self::SLUG.'-add-a-class', array(&$this, 'addClassPage'));
			add_submenu_page(	self::SLUG, 'Fields', 'Fields', 'manage_options',
									self::SLUG.'-fields', array(&$this, 'fieldsPage'));
			add_submenu_page(	self::SLUG, 'Settings', 'Settings', 'manage_options',
									self::SLUG.'-settings', array(&$this, 'settingsPage'));
		}


		/*
			Function: adminInit
				Registers an option group so that the settings page works and can be sanitized.
		*/
		public function adminInit() {
			register_setting(self::OPTIONS_GROUP, self::OPTIONS, array(&$this, 'sanitizeSettings'));
		}


		/*
			Function: addJSCSS
				Adds custom CSS and JavaScript links to LIR pages.  Also makes sure jquery is loaded on
				those pages as well.
		*/
		public function addJSCSS() {
			global $parent_file;

			if($parent_file != self::SLUG) return;

			wp_enqueue_script(self::SLUG.'_admin_JS', plugins_url('js/admin.js', __FILE__));
			wp_enqueue_script('jquery');
			wp_enqueue_style(self::SLUG.'_admin_CSS', plugins_url('css/admin.css', __FILE__));
		}


		/*
			Function: defaultPage
				The default page displayed when clicking on the LIR menu item.  This page shows
				a list of upcoming classes while allowing users to see the details, edit the entries,
				and delete entries.

			Outputs:
				HTML for the default page.
		*/
		public function defaultPage() {
			if(!current_user_can('edit_posts')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}

			global $wpdb;
			$query = "SELECT * FROM ".$this->table['posts']." WHERE NOW() <= class_end ORDER BY class_start, class_end, librarian_name";
			$result = $wpdb->get_results($query);

			?>
			<div class="wrap">
				<h2><?php echo $this->options['name']; ?> <a href="<?php echo 'admin.php?page='.self::SLUG.'-add-a-class'; ?>" class="add-new-h2">Add a Class</a></h2>
				<h3>Upcoming Classes</h3>
				<table class="widefat fixed">
					<thead><tr><th class="check-column">&nbsp;</th><th class="sortable"><a href="#">Department/Group</a></th><th>Course #</th>
						<th class="date-column">Date/Time</th><th>Primary Librarian</th><th>Instructor</th><th>Details</th></tr></thead>
					<tfoot><tr><th class="check-column">&nbsp;</th><th class="sortable"><a href="#">Department/Group</a></th><th>Course #</th>
						<th class="date-column">Date/Time</th><th>Primary Librarian</th><th>Instructor</th><th>Details</th></tr></tfoot>
					<tbody>

					<?php
					//Post a table row for each class in $result.
					foreach($result as $class) {
						if($class->class_description)
							echo '<tr title="'.$class->class_description.'">';
						else
							echo '<tr>';

						echo '<th>&nbsp;</th><td>'.$class->department_group.'</td><td>'.$class->course_number.'</td>';

						//Display start date & time - end date & time.
						if(substr($class->class_start, 0, 10) == substr($class->class_end, 0, 10)) {
							echo '<td>'.date('n/j/Y (D) g:i A - ', strtotime($class->class_start));
							echo date('g:i A', strtotime($class->class_end)).'</td>';
						}
						else { //If the end time is not on the same day as the start time.
							echo '<td>'.date('n/j/Y (D) g:i A -', strtotime($class->class_start)).'<br />';
							echo date('n/j/Y (D) g:i A', strtotime($class->class_end)).'</td>';
						}

						echo '<td>'.$class->librarian_name.'</td>';

						//Instructor name and email.
						if($class->instructor_name && $class->instructor_email) {
							$mailto = esc_attr('mailto:'.$class->instructor_name.' <'.$class->instructor_email.'>');
							echo '<td><a href="'.$mailto.'">'.$class->instructor_name.'</a></td>';
						}
						else {
							echo '<td>'.$class->instructor_name.'</td>';
						}

						echo '<td><a href="#">Details</a></td>';
						echo '</tr>';
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
				page will also be used for editing existing entries.

			Outputs:
				HTML for the add a class page.

			See Also:
				<addUpdateClass>
		*/
		public function addClassPage() {
			if(!current_user_can('edit_posts')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}

			global $user_identity, $wpdb;
			get_currentuserinfo();

			$classAdded = false;
			//If form has been submitted and has a valid nonce.
			if(isset($_POST['submitted']) && ($debug['nonce verified'] = wp_verify_nonce($_POST[self::SLUG.'_nonce'], self::SLUG.'_add_class'))) {
				$error = array();

				//Check to make sure all required fields have been submitted.
				if(empty($_POST['librarian_name']))		array_push($error, 'Missing Field: Primary Librarian');
				if(empty($_POST['instructor_name']))	array_push($error, 'Missing Field: Instructor Name');
				if(empty($_POST['department_group']))	array_push($error, 'Missing Field: Department/Group');
				if(empty($_POST['class_date']))			array_push($error, 'Missing Field: Class Date');
				if(empty($_POST['class_time']))			array_push($error, 'Missing Field: Class Time');
				if(empty($_POST['class_length']))		array_push($error, 'Missing Field: Class Length');
				if(empty($_POST['class_location']))		array_push($error, 'Missing Field: Class Location');
				if(empty($_POST['class_type']))			array_push($error, 'Missing Field: Class Type');
				if(empty($_POST['audience']))				array_push($error, 'Missing Field: Audience');

				//Go to function to insert data into database.
				if(empty($error)) $classAdded = $this->addUpdateClass();
				//If database insert failed, push error.
				if(!$classAdded && empty($error)) array_push($error, 'An error has occurred while trying to submit the class. Please try again.');
			}

			?>
			<div class="wrap">
				<h2>Add a Class</h2>

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

					echo '</div>';
				}

				//Message if class was added.
				if($classAdded) {
					echo '<div id="message" class="updated">
						<p><strong>The class has been added!</strong></p>
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
								if($u->display_name == $user_identity) echo ' selected="selected"';
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
								echo '<option value="'.$u->display_name.'">'.$u->display_name.'</option>';
							}
							?>
							</select></td>
						</tr>
						<tr>
							<th>*Instructor Name</th>
							<td><input type="text" name="instructor_name" /></td>
						</tr>
						<tr>
							<th>Instructor Email</th>
							<td><input type="email" name="instructor_email" /></td>
						</tr>
						<tr>
							<th>Instructor Phone</th>
							<td><input type="tel" name="instructor_phone" /></td>
						</tr>
						<tr>
							<th>Class Description</th>
							<td><textarea name="class_description"></textarea></td>
						</tr>
						<tr>
							<th>*Department/Group</th>
							<td><select name="department_group">
								<option value="">&nbsp;</option>
								<?php
								$departmentGroup = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'department_group_values'"));

								foreach($departmentGroup as $x)
									echo '<option value="'.esc_attr($x).'">'.$x.'</option>';
								?>
							</select></td>
						</tr>
						<tr>
							<th>Course Number</th>
							<td><input type="text" name="course_number" /></td>
						</tr>
						<tr>
							<th>*Class Date</th>
							<td><input type="date" name="class_date" value="<?php echo date('Y-m-d'); ?>" /></td>
						</tr>
						<tr>
							<th>*Class Time</th>
							<?php
							date_default_timezone_set('EST5EDT');
							$minutes = date('i', strtotime("+15 minutes")) - date('i', strtotime("+15 minutes")) % 15;
							$time = date('H:', strtotime("+15 minutes")).(($minutes) ? $minutes : '00');
							?>
							<td><input type="time" name="class_time" value="<?php echo $time; ?>" /> <label>*Length</label>
								<select name="class_length">
									<option value="0">&nbsp;</option>
									<option value="15">15 minutes</option>
									<option value="30">30 minutes</option>
									<option value="45">45 minutes</option>
									<option value="60">1 hour</option>
									<option value="75">1 hour 15 minutes</option>
									<option value="90">1 hour 30 minutes</option>
									<option value="105">1 hour 45 minutes</option>
									<option value="120">2 hours</option>
								</select>
							</td>
						</tr>
						<tr>
							<th>*Class Location</th>
							<td><select name="class_location">
								<option value="">&nbsp;</option>
								<?php
								$classLocation = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_location_values'"));

								foreach($classLocation as $x)
									echo '<option value="'.esc_attr($x).'">'.$x.'</option>';
								?>
							</select></td>
						</tr>
						<tr>
							<th>*Class Type</th>
							<td><select name="class_type">
								<option value="">&nbsp;</option>
								<?php
								$classType = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_type_values'"));

								foreach($classType as $x)
									echo '<option value="'.esc_attr($x).'">'.$x.'</option>';
								?>
							</select></td>
						</tr>
						<tr>
							<th>*Audience</th>
							<td><select name="audience">
								<option value="">&nbsp;</option>
								<?php
								$audience = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'audience_values'"));

								foreach($audience as $x)
									echo '<option value="'.esc_attr($x).'">'.$x.'</option>';
								?>
							</select></td>
						</tr>
						<?php
						$bools = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'bool_info'"));

						if($bools['bool1Enabled']) {
						?>
						<tr>
							<th><?php echo $bools['bool1Value']; ?></th>
							<td><input type="checkbox" name="bool1" />
						</tr>
						<?php
						} if($bools['bool2Enabled']) {
						?>
						<tr>
							<th><?php echo $bools['bool2Value']; ?></th>
							<td><input type="checkbox" name="bool2" />
						</tr>
						<?php
						} if($bools['bool3Enabled']) {
						?>
						<tr>
							<th><?php echo $bools['bool3Value']; ?></th>
							<td><input type="checkbox" name="bool3" />
						</tr>
						<?php
						}
						?>
						<tr>
							<th>Number of Students Attended</th>
							<td><input type="number" name="attendance" /></td>
						</tr>
					</table>

					<?php wp_nonce_field(self::SLUG.'_add_class', self::SLUG.'_nonce'); ?>

					<p class="submit">
						<input type="submit" name="submitted" class="button-primary" value="Add Class" />
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
				id	-	The id of the entry being updated (if applicable).

			Returns:
				true	-	True if successfully added or updated an entry.
				false	-	False if entry was not added/updated.
		*/
		private function addUpdateClass($id = NULL) {
			global $wpdb, $current_user;
			get_currentuserinfo();

			$data = array(
				'librarian_name'		=>	$_POST['librarian_name'],
				'instructor_name'		=>	$_POST['instructor_name'],
				'class_location'		=>	$_POST['class_location'],
				'class_type'			=>	$_POST['class_type'],
				'audience'				=>	$_POST['audience'],
				'department_group'	=>	$_POST['department_group'],
				'last_updated_by'		=>	$current_user->user_email,
				'owner_id'				=>	$current_user->id
			);

			$data['class_start'] = $_POST['class_date'].' '.$_POST['class_time'];
			$data['class_end'] = date('Y-m-d G:i', strtotime($data['class_start'].' +'.$_POST['class_length'].' minutes'));
			$data['bool1'] = isset($_POST['bool1']) ? 1 : 0;
			$data['bool2'] = isset($_POST['bool2']) ? 1 : 0;
			$data['bool3'] = isset($_POST['bool3']) ? 1 : 0;

			if(!empty($_POST['librarian2_name']))		$data['librarian2_name'] = $_POST['librarian2_name'];
			if(!empty($_POST['instructor_email']))		$data['instructor_email'] = $_POST['instructor_email'];
			if(!empty($_POST['instructor_phone']))		$data['instructor_phone'] = $_POST['instructor_phone'];
			if(!empty($_POST['class_description']))	$data['class_description'] = $_POST['class_description'];
			if(!empty($_POST['course_number']))			$data['course_number'] = $_POST['course_number'];
			if(!empty($_POST['attendance']))				$data['attendance'] = $_POST['attendance'];

			return $wpdb->insert($this->table['posts'], $data);
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
			//CREATE AND POPULATE VARS FOR EACH SECTION BELOW, UPDATE THEM IF POST
			$departmentGroup = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'department_group_values'"));
			$classLocation = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_location_values'"));
			$classType = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'class_type_values'"));
			$audience = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'audience_values'"));
			$bools = unserialize($wpdb->get_var("SELECT value FROM ".$this->table['meta']." WHERE field = 'bool_info'"));

			//Check for form submission and do appropriate action.
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
			}// End if nonce is set.

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
					<p><label>Name: <input type="text" name="bool1Value" value="<?php echo $bools['bool1Value']; ?>" /></label>
					<label>Enabled <input type="radio" name="bool1Enabled" value="1" <?php if($bools['bool1Enabled']) echo 'checked="checked "'; ?> /></label>
					<label>Disabled <input type="radio" name="bool1Enabled" value="0" <?php if(!$bools['bool1Enabled']) echo 'checked="checked "'; ?> /></p></label>

					<h4>Bool 2</h4>
					<p><label>Name: <input type="text" name="bool2Value" value="<?php echo $bools['bool2Value']; ?>" /></label>
					<label>Enabled <input type="radio" name="bool2Enabled" value="1" <?php if($bools['bool2Enabled']) echo 'checked="checked "'; ?> /></label>
					<label>Disabled <input type="radio" name="bool2Enabled" value="0" <?php if(!$bools['bool2Enabled']) echo 'checked="checked "'; ?> /></p></label>

					<h4>Bool 3</h4>
					<p><label>Name: <input type="text" name="bool3Value" value="<?php echo $bools['bool3Value']; ?>" /></label>
					<label>Enabled <input type="radio" name="bool3Enabled" value="1" <?php if($bools['bool3Enabled']) echo 'checked="checked "'; ?> /></label>
					<label>Disabled <input type="radio" name="bool3Enabled" value="0" <?php if(!$bools['bool3Enabled']) echo 'checked="checked "'; ?> /></p></label>

					<input name="boolSave" type="submit" class="button-secondary" value="Save Bools" />
					<?php wp_nonce_field(self::SLUG.'_fields', self::SLUG.'_nonce'); ?>

					<!--<p class="submit">
						<input type="submit" name="submitted" class="button-primary" value="Save Changes" />
					</p>-->
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

			?>
			<div class="wrap">
				<h2>Settings</h2>
				<form method="post" action="options.php">
					<?php settings_fields(self::OPTIONS_GROUP); ?>
					<table class="form-table">
						<tr>
							<th scope="row">Plugin Name</th>
							<td><input type="text" name="<?php echo self::OPTIONS.'[name]'; ?>" value="<?php echo $this->options['name']; ?>" /></td>
						</tr>
						<tr>
							<th scope="row">Plugin Slug</th>
							<td><input type="text" name="<?php echo self::OPTIONS.'[slug]'; ?>" value="<?php echo $this->options['slug']; ?>" /></td>
						</tr>
						<tr>
							<th scope="row">Debugging</th>
							<td><input type="checkbox" name="<?php echo self::OPTIONS.'[debug]'; ?>" <?php checked($this->options['debug'], 'on'); ?> /> Enabled</td>
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
				input	-	Array of options from the LIR settings page.

			Returns:
				array	-	Array of sanitized options.
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
				input		-	A string containing content.

			Returns:
				string	-	A modified string of content.
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
