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
 * Recommender block AJAX script
 *
 * @package    blocks
 * @subpackage recommender
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/recommender/locallib.php');
require_once($CFG->dirroot.'/blocks/recommender/services/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$servicename  = required_param('servicename', PARAM_ALPHANUM);

// Must be logged in and have the sesskey
require_login($courseid, false);
require_sesskey();

echo $OUTPUT->header(); // send headers

$outcome = new stdClass;
$outcome->success = true;
$outcome->response = new stdClass;
$outcome->error = '';

// reach the service library and create an object instance
$servicelib = $CFG->dirroot.'/blocks/recommender/services/'.$servicename.'/lib.php';

if (is_readable($servicelib)) {
    include_once($servicelib);
    if (!$service = get_service($servicename, $PAGE->course)) {
        throw new block_recommender_ajax_exception(
            get_string('errorcallingservice', 'block_recommender', $servicename)
        );
    }
} else {
    throw new block_recommender_ajax_exception(
        get_string('errornosuchservice', 'block_recommender', $servicename)
    );
}

// get the content
$renderer = $PAGE->get_renderer('block_recommender');
$content = $service->object->get_block_content();
$outcome->response->content = $renderer->navigation_service_tree($content);

echo json_encode($outcome);
die();
