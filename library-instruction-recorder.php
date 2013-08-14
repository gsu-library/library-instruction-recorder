<?php
/*
   Plugin Name: Library Instruction Recorder
   Plugin URI: http://bitbucket.org/gsulibwebmaster/library-instruction-recorder
   Description: A plugin for recording library instruction events and their associated data.
   Version: 0.0.1
   Author: Matt Brooks
   Author URI: http://library.gsu.edu/
   License: GPLv3
   Tested: 3.5.1
*/

/*
   Put license information here.
*/

/*
Natural Docs Info Maybe.
*/


if(!class_exists('LIR')) {
	/*
		Class: LIR
			The Library Instruction Recorder class which enables the LIR functionality
			in WordPress.
	*/
	class LIR {
		const NAME = 'Library Instruction Recorder';
		const SLUG = 'LIR';


		public function __construct() {
			add_filter('the_content', array(&$this, 'addToContent'));
			add_action('admin_menu', array(&$this, 'adminMenu'));
		}


		//Adds menu item for administration.
		public function adminMenu() {
			//add_options_page(self::NAME . ' Settings', self::NAME, 'manage_options', 'LIR', array(&$this, 'adminSettings'));

			//manage_options value will need to be adjusted later
			add_menu_page(self::NAME, self::SLUG, 'manage_options', self::SLUG, array(&$this, adminSettings));
		}


		//Code for the administration page (options).
		public function adminSettings() {
			if(!current_user_can('manage_options')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}

			echo '<div class="wrap">
						<h2>'.self::NAME.'</h2>
						<p>Put something in here!</p>
					</div>';

					echo '<pre>';
					print_r($GLOBALS['menu']);
					//var_dump($GLOBALS['menu']);
					echo '</pre>';
		}


		//Test function.
		public function addToContent($input = '') {
			return $input . "<p id='Lrrr'><i>I am Lrrr, ruler of the planet Omicron Persei 8!</i></p>";
		}
	}
}
else {
	//DISPLAY ERROR MESSAGE ABOUT CONFLICTING PLUGIN NAME
	return; //should return to parent script
}

$LIR = new LIR();

//add_filter('the_content', 'lrrr_print');
