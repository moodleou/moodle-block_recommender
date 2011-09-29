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
 * Settings for the recommender course service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$roles = $DB->get_records_menu('role', null, 'sortorder');

$settings->add(new admin_setting_configselect('block_recommender/course_role',
        get_string('course_role',        'block_recommender'),
        get_string('course_roledescription',  'block_recommender'), 5, $roles));

$settings->add(new admin_setting_configtext('block_recommender/course_course_shortname_pattern',
        get_string('course_course_shortname_pattern_title',        'block_recommender'),
        get_string('course_course_shortname_pattern_description',  'block_recommender'), ''));

$settings->add(new admin_setting_configtext('block_recommender/course_course_url',
        get_string('course_course_url_title',       'block_recommender'),
        get_string('course_course_url_description', 'block_recommender'), ''));
