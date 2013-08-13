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
 * Recommender block Popular Activities service
 *
 * @package     block
 * @subpackage  recommender
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');

class block_recommender_activity_display_form extends moodleform {
    public function definition() {
        global $COURSE;
        $mform =& $this->_form;
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        // Build the date filter.
        $dateoptions = array(
            'lastvisit' => get_string('activity_lastvisit', 'block_recommender'),
            'lastmonth' => get_string('activity_lastmonth', 'block_recommender'),
            'lastweek'  => get_string('activity_lastweek', 'block_recommender'),
            'yesterday' => get_string('activity_yesterday', 'block_recommender'),
        );
        $datelabel = get_string('activity_filter_daterange', 'block_recommender');
        $daterange = $mform->addElement('select', 'daterange', $datelabel, $dateoptions);
        $mform->addHelpButton('daterange', 'activity_filter_daterange', 'block_recommender');

        // Build the activity filter.
        $activityoptions = array(
            -1 => get_string('activity_filter_all', 'block_recommender'),
            MOD_ARCHETYPE_OTHER => get_string('activity_filter_activities', 'block_recommender'),
            MOD_ARCHETYPE_RESOURCE => get_string('activity_filter_resources', 'block_recommender'),
        );
        $activitylabel = get_string('activity_filter_activity', 'block_recommender');
        $activityrange = $mform->addElement('select', 'activity', $activitylabel, $activityoptions);
        $mform->addHelpButton('activity', 'activity_filter_activity', 'block_recommender');

        // Sort filter.
        $sortlabel = get_string('activity_filter_sort', 'block_recommender');
        $sortoptions = array(
            'views_most' => get_string('activity_filter_sort_mostviewed', 'block_recommender'),
            'views_least' => get_string('activity_filter_sort_leastviewed', 'block_recommender'),
            'participations_most' => get_string('activity_filter_sort_mostp', 'block_recommender'),
            'participations_least' => get_string('activity_filter_sort_leastp',
                    'block_recommender'),
            'module_least' => get_string('activity_filter_sort_az', 'block_recommender'),
            'module_most' => get_string('activity_filter_sort_za', 'block_recommender')
        );
        $mform->addElement('select', 'sort', $sortlabel, $sortoptions);
        $mform->addHelpButton('sort', 'activity_filter_sort', 'block_recommender');

        $buttonlabel = get_string('activity_filter_apply', 'block_recommender');
        $att = array('class'=>'recommender_apply');
        $button = $mform->addElement('submit', 'submitbutton', $buttonlabel, $att);
    }
}
