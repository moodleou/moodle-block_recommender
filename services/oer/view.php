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
 * View resources in the recommender block OER service
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
$courseid  = required_param('courseid', PARAM_INT);

// Require login and capability
require_login($courseid, false);
require_capability('block/recommender:viewoer', $PAGE->context);

$pageparams = array('courseid' => $courseid);

$title = get_string('oer_servicetitle', 'block_recommender');
$PAGE->set_url('/blocks/recommender/services/oer/view.php', $pageparams);
$PAGE->set_title($title.' - '.get_string('recommendertitle', 'block_recommender'));
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add breadcrumbs
$PAGE->navbar->add(get_string('recommendertitle', 'block_recommender'));
$PAGE->navbar->add($title, $PAGE->url);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$service = new block_recommender_service_oer($course);
$links = $service->get_links(true);

$renderer = $PAGE->get_renderer('block_recommender_service_oer');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('oer_servicetitle', 'block_recommender'));
echo $renderer->display_more_details($links);
echo $OUTPUT->footer();
