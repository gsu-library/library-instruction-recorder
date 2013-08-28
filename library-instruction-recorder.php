<?php
/*
   Plugin Name: Library Instruction Recorder
   Plugin URI: http://bitbucket.org/gsulibwebmaster/library-instruction-recorder
   Description: A plugin for recording library instruction events and their associated data.
   Version: 0.0.1
   Author: Georgia State University Library
   Author URI: http://library.gsu.edu/
   License: GPLv3
*/

/*
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

/*
	Additional Natural Docs info here maybe.
*/


if(!class_exists('LIR')) {
	/*
		Class: LIR
			The LIR class which enables the Library Instruction Recorder functionality
			in WordPress.
	*/
	class LIR {
		//CHANGE TO PULL THIS FROM THE WP_OPTIONS TABLE. ALLOW NAME CHANGING AT SOME POINT.
		const NAME = 'Library Instruction Recorder';
		const SLUG = 'LIR';
		const OPTIONS = 'lir_options';
		const OPTIONS_GROUP = 'lir_options_group';
		private $options;


		/*
			Constructor: __construct
				Adds actions and filters to WP upon construction.
		*/
		public function __construct() {
			register_activation_hook(__FILE__, array(&$this, 'activationHook')); //http://codex.wordpress.org/Function_Reference/register_activation_hook
			register_deactivation_hook(__FILE__, array(&$this, 'deactivationHook'));

			//Actions and filters.
			add_action('admin_menu', array(&$this, 'createMenu'));
			add_action('admin_init', array(&$this, 'adminInit'));
			add_filter('the_content', array(&$this, 'easterEgg')); //For testing purposes.
			
			//Load options.
			$this->options = get_option(self::OPTIONS, NULL);
		}


		/*
			Function: activationHook
				Checks to make sure WordPress is compatible, sets up tables, and sets up options.
		*/
		public static function activationHook() {
			global $wp_version;
			
			if(version_compare($wp_version, '3.6', '<'))
				wp_die('This plugin requires WordPress version 3.6 or higher.');
				
			$options = array(	'debug' => false,
									'name' => 'Library Instruction Recorder', 
									'slug' => 'LIR');

			add_option(self::OPTIONS, $options, '', 'no');
		}


		/*
			Function: deactivationHook
				Used to cleanup items after deactivating the plugin. Also is used for testing
				purposes.
		*/
		public static function deactivationHook() {
			//MAKE SURE DEACTIVATION WAS CALLED BY USER
			//MOVE THIS TO THE UINSTALL HOOK/FILE WHEN READY TO GO LIVE***********
			delete_option(self::OPTIONS);
		}


		/*
			Function: createMenu
				Creates a menu and submenu on the dashboard for plugin usage and administration.

			See Also:
				<defaultPage>, <addClassPage>, and <settingsPage>
		*/
		public function createMenu() {
			add_menu_page('', $this->options['slug'], 'edit_posts', self::SLUG, array(&$this, 'defaultPage'), '', '58.992');
			//Added so the first submenu item does not have the same title as the main menu item.
			add_submenu_page(	self::SLUG, 'Upcoming Classes', 'Upcoming Classes', 'edit_posts', 
									self::SLUG, array(&$this, 'defaultPage'));
			add_submenu_page(	self::SLUG, 'Add a Class', 'Add a Class', 'edit_posts', 
									self::SLUG.'-add-a-class', array(&$this, 'addClassPage'));
			add_submenu_page(	self::SLUG, 'Settings', 'Settings', 'manage_options', 
									self::SLUG.'-settings', array(&$this, 'settingsPage'));
		}
		
		
		/*
		
		*/
		public function adminInit() {
			register_setting(self::OPTIONS_GROUP, self::OPTIONS, array(&$this, 'sanitizeSettings'));
		}


		/*
			Function: defaultPage
				The default page displayed when clicking on the LIR menu item.  This page shows
				a list of upcoming classes while allowing users to edit the entries.

			Outputs:
				HTML for the default page.
		*/
		public function defaultPage() {
			if(!current_user_can('edit_posts')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}

			echo '<div class="wrap">
						<h2>'.$this->options['name'].'</h2>
						<h3>Upcoming Classes</h3>
					</div>';
		}


		/*
			Function: addClassPage
				The add a class page allows users to add a class to the instruction recorder.

			Outputs:
				HTML for the add a class page.
		*/
		public function addClassPage() {
			if(!current_user_can('edit_posts')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}

			echo '<div class="wrap">
						<h2>Add a Class</h2>
					</div>';
		}


		/*
			Function: settingsPage
				Controls what shows up on the admin page of this plugin.

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
							<th scope="row"><label for="<?php echo self::OPTIONS.'[name]'; ?>">Plugin Name</label></th>
							<td><input type="text" name="<?php echo self::OPTIONS.'[name]'; ?>" value="<?php echo $this->options['name']; ?>" /></td>
						</tr>
						<tr>
							<th scope="row">Plugin Slug</th>
							<td><input type="text" name="<?php echo self::OPTIONS.'[slug]'; ?>" value="<?php echo $this->options['slug']; ?>" /></td>
						</tr>
						<tr>
							<th scope="row">Debugging</th>
							<td><input type="checkbox" name=<?php echo '"'.self::OPTIONS.'[debug]" '.checked($this->options['debug'], 'on', false); ?> /> Enabled</td>
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
				Sanitizes all inputs that are run through the options page.
				
			Inputs:
				input	-	Array of options from the LIR settings page.
				
			Returns:
				array	-	Array of sanitized options.
		*/
		public function sanitizeSettings($input) {
			$input['name'] = sanitize_text_field($input['name']);
			$input['slug'] = sanitize_text_field($input['slug']);
			
			return $input;
		}


		/*
			Function: easterEgg
				Test filter function that adds text to the end of content.

			Inputs:
				input		-	A string containing content.

			Returns:
				string	-	A modified string.
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
