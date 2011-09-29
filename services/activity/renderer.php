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
 * Recommender block activity service libraries
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * block_recommender_service_activities renderer
 *
 * Class for rendering components of the activities recommender service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_recommender_service_activity_renderer extends plugin_renderer_base {

    public function display_filter($baseurl, $selecteddate = '', $selectedactivity = '') {
        $display = '';

        // Build the date filter
        $dateoptions = array(
            'lastvisit' => get_string('activity_lastvisit', 'block_recommender'),
            'lastmonth' => get_string('activity_lastmonth', 'block_recommender'),
            'lastweek'  => get_string('activity_lastweek', 'block_recommender'),
            'yesterday' => get_string('activity_yesterday', 'block_recommender'),
        );

        // We need to ensure that the activity is set, but not the daterange -- otherwise
        // the URL on submission gets broken
        if ($selectedactivity) {
            $baseurl->params(array('activity' => $selectedactivity));
        }
        $baseurl->remove_params('daterange');

        $daterangeform = new single_select($baseurl, 'daterange',  $dateoptions,
            $selecteddate, false);
        $daterangeform->set_label(get_string('activity_filter_daterange', 'block_recommender'));
        $daterangeform->set_help_icon('activity_filter_daterange', 'block_recommender');

        $display .= $this->output->render($daterangeform);

        // Build the activity filter
        $activityoptions = array(
            -1 => get_string('activity_filter_all', 'block_recommender'),
            MOD_ARCHETYPE_OTHER => get_string('activity_filter_activities', 'block_recommender'),
            MOD_ARCHETYPE_RESOURCE => get_string('activity_filter_resources', 'block_recommender'),
        );

        // We need to ensure that the daterange is set, but not the activity -- otherwise
        // the URL on submission gets broken
        if ($selecteddate) {
            $baseurl->params(array('daterange' => $selecteddate));
        }
        $baseurl->remove_params('activity');

        $activityform = new single_select($baseurl, 'activity',  $activityoptions,
            $selectedactivity, false);
        $activityform->set_label(get_string('activity_filter_activity', 'block_recommender'));
        $activityform->set_help_icon('activity_filter_activity', 'block_recommender');

        $display .= $this->output->render($activityform);

        return $display;
    }

}
