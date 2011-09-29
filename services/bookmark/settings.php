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
 * Settings for the recommender bookmark service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/lib.php');

$options = array();
$options[''] = get_string('bookmark_displaytype_top', 'block_recommender');
$options[0] = get_string('bookmark_displaytype_defaultcategory', 'block_recommender');
for ($i = 1; $i <= SERVICE_BOOKMARKS_MAXCATEGORIES; $i++) {
    $options[$i] = get_string('bookmark_displaytype_categoryn', 'block_recommender', $i);
    $settings->add(new admin_setting_configtext('block_recommender/bookmark_category_' . $i,
            get_string('bookmark_categoryn_title', 'block_recommender', $i),
            get_string('bookmark_categoryn_description', 'block_recommender', $i), ''));
}

$settings->add(new admin_setting_configselect('block_recommender/bookmark_displaytype',
        get_string('bookmark_displaytype_title', 'block_recommender'),
        get_string('bookmark_displaytype_description', 'block_recommender'), '', $options));
