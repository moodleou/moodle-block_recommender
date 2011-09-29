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
 * AJAX script for the recommender bookmark service
 *
 * @package    blocks
 * @subpackage recommender
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/renderer.php');

// Determine the course
$courseid   = required_param('courseid',            PARAM_INT);
$moveid     = optional_param('moveid'   ,   null,   PARAM_INT);
$categoryid = optional_param('categoryid',  null,   PARAM_INT);
$moveafter  = optional_param('moveafter',   null,   PARAM_INT);

// Require login, capability and sesskey
require_login($courseid, false);
require_capability('block/recommender:viewbookmark', $PAGE->context);
require_sesskey();

echo $OUTPUT->header(); // send headers

$outcome = new stdClass;
$outcome->success = false;

$service = new block_recommender_service_bookmark($PAGE->course);

if ($moveid) {
    $original = $service->get_bookmark($moveid);
    if ($moveafter !== null) {
        $after = $service->get_bookmark($moveafter);
        $service->move_bookmark($original, $after->sortorder+1, $after->categoryid);
        $outcome->success = true;
    } else if ($categoryid !== null) {
        $service->move_bookmark($original, 0, $categoryid);
        $outcome->success = true;
    }
}

if (!$outcome->success) {
    throw new block_recommender_service_ajax_exception(
        get_string('bookmark_errormoving', 'block_recommender'));
}

echo json_encode($outcome);
die();
