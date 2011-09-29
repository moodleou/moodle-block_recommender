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
 * Delete a bookmark in the recommender bookmark service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/renderer.php');

// Determine the course
$courseid   = required_param('courseid',    PARAM_INT);
$id         = required_param('id',          PARAM_INT);
$confirm    = optional_param('confirm', false, PARAM_BOOL);

// Require login and permissions
require_login($courseid, false);
require_capability('block/recommender:addbookmark', $PAGE->context);

$service    = new block_recommender_service_bookmark($PAGE->course);
$bookmark   = $service->get_bookmark($id);

$pageparams = array('courseid' => $courseid);

// The confirmation strings
$confirmstr = get_string('bookmark_confirmdeletionfull', 'block_recommender', $bookmark);
$confirmurl = new moodle_url('/blocks/recommender/services/bookmark/delete.php',
        array('id' => $id, 'courseid' => $courseid, 'confirm' => 1));
$returnurl  = new moodle_url('/blocks/recommender/services/bookmark/view.php',
    array('courseid' => $courseid));

// Set page url
$PAGE->set_url('/blocks/recommender/services/bookmark/delete.php',
    array('id' => $id, 'courseid' => $courseid));

// Set the heading and page title
$title = get_string('bookmark_deletetitle', 'block_recommender', $bookmark);
$PAGE->set_title($title.' - '.get_string('recommendertitle', 'block_recommender'));
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('recommendertitle', 'block_recommender'));
$PAGE->navbar->add($title);

if ($confirm) {
    // Confirm the session key to stop CSRF
    require_sesskey();

    // Delete the bookmark
    $service->delete_bookmark($bookmark);

    // Redirect
    redirect($returnurl);
}

// Display the delete confirmation dialogue
echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmurl, $returnurl);
echo $OUTPUT->footer();
