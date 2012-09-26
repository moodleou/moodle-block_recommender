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
 * View additional information for the recommender activity service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/display_form.php');

// Determine the course and other params
$courseid   = required_param('courseid',  PARAM_INT);
$daterange  = optional_param('daterange', null, PARAM_RAW);
$selectedactivity = optional_param('activity',  -1, PARAM_INT);
$sort = optional_param('sort', 'views_most', PARAM_ALPHAEXT);

// Require login
require_login($courseid, false);
require_capability('block/recommender:viewactivity', $PAGE->context);

$pageparams = array('courseid' => $courseid);

// Check the daterange and convert to a valid period
$pageparams['daterange'] = $daterange;
switch ($daterange) {
    case 'yesterday':
        $withinperiod = strtotime('-1 day');
        break;
    case 'lastweek':
        $withinperiod = strtotime('-1 week');
        break;
    case 'lastmonth':
        $withinperiod = strtotime('-1 month');
        break;
    default:
        $withinperiod = null;
}

// Check the activity to show
$pageparams['activity'] = $selectedactivity;
if ($selectedactivity === -1) {
    $activity = null;
} else {
    $activity = $selectedactivity;
}

// Set the page settings
$title = get_string('activity_popularactivitiestitle', 'block_recommender');
$PAGE->set_url('/blocks/recommender/services/activity/view.php', $pageparams);
$PAGE->set_title($title.' - '.get_string('recommendertitle', 'block_recommender'));
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add breadcrumbs
$PAGE->navbar->add(get_string('recommendertitle', 'block_recommender'));
$PAGE->navbar->add($title, $PAGE->url);

// Output the header before displaying the filter section
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('activity_servicetitle', 'block_recommender'));

$mform = new block_recommender_activity_display_form();
$mform->set_data($pageparams);
$mform->display();

// Build the table
$service = new block_recommender_service_activity($PAGE->course);

$table = new recommender_flexible_table('activity_course_' . $PAGE->course->id);
$table->define_baseurl($PAGE->url);

// Table headers and columns
$headers = array();
$headers[] = get_string('activity_items', 'block_recommender');
$headers[] = get_string('activity_views', 'block_recommender');
$headers[] = get_string('activity_participations', 'block_recommender');
$table->define_columns(array('module', 'views', 'participations'));
$table->column_class('module', 'thleft');
$table->define_headers($headers);

// Run the table setup
$table->setup();

// Calculate sorting
$sortarray = explode('_', $sort);
if ($sortarray[1] == 'least') {
    $sortdirection = 'ASC';
} else {
    $sortdirection = 'DESC';
}
$sortby = $sortarray[0] . ' ' . $sortdirection;

// Retrieve the links
$links = $service->get_popular_activities(SERVICE_POPULARACTIVITIES_MORE_LIMIT,
    $withinperiod, $activity, $sortby);

// Add all of the links to the data
foreach ($links as $link) {
    $row = array();
    $icon = html_writer::empty_tag('img', array('src' => $link->icon_url,
                                                'class' => 'activityicon',
                                                'alt' => get_string('modulename', $link->module)));
    $title = html_writer::tag('span', $link->title, array('class' => 'instancename'));
    $row['module'] = html_writer::link($link->url, $icon . $title);
    $row['views'] = $link->views;
    $row['participations'] = $link->participations;
    $table->add_data_keyed($row);
}

// Display the table itself
echo $table->finish_output();

echo $OUTPUT->footer();
