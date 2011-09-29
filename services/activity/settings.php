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
 * Recommender block Popular Activities Settings
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once('adminlib.php');
global $DB;


$modrecords = $DB->get_records('modules', array('visible' => 1), 'name');

foreach ($modrecords as $r) {
    $settingstring = get_string('activity_moduleincluded', 'block_recommender',
        array('modname' => get_string('modulename', $r->name)));
    $settings->add( new admin_setting_recommender_activity('block_recommender/activity_'.$r->name,
        $settingstring, '', 1));
}