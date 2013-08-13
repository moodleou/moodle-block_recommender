<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Recommender block settings
 *
 * @package    blocks
 * @subpackage recommender
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/blocks/recommender/locallib.php');

	// Display enable/disable settings
    $settings->add(new admin_setting_heading('block_recommender_enabledservices',
        get_string('enabledservices', 'block_recommender'), null));

    foreach (get_recommender_services_list() as $servicename) {
        $settings->add(
            new admin_setting_configcheckbox('block_recommender/'.$servicename.'_enabled',
            get_string($servicename.'_servicetitle', 'block_recommender'), null, 1)
        );
    }

    // Display individual block settings  defined in service files
    foreach (get_recommender_services_list() as $servicename) {
        $settingsfile = $CFG->dirroot.'/blocks/recommender/services/'.$servicename.'/settings.php';
        if (is_readable($settingsfile)) {
            $settings->add(new admin_setting_heading('block_recommender/'.$servicename,
                get_string($servicename.'_servicetitle', 'block_recommender'), null));
            include($settingsfile);
        }
    }
}