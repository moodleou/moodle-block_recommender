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
 * View a bookmark in the recommender bookmark service
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
$courseid   = required_param('courseid',            PARAM_INT);
$moveid     = optional_param('moveid'   ,   null,   PARAM_INT);
$categoryid = optional_param('categoryid',  null,   PARAM_INT);
$moveafter  = optional_param('moveafter',   null,   PARAM_INT);

// Require login and capability
require_login($courseid, false);
require_capability('block/recommender:viewbookmark', $PAGE->context);

$pageparams = array('courseid' => $courseid);

$title = get_string('bookmark_bookmarktitle', 'block_recommender');
$PAGE->set_url('/blocks/recommender/services/bookmark/view.php', $pageparams);
$PAGE->set_title($title.' - '.get_string('recommendertitle', 'block_recommender'));
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add breadcrumbs
$PAGE->navbar->add(get_string('recommendertitle', 'block_recommender'));
$PAGE->navbar->add($title, $PAGE->url);

$service = new block_recommender_service_bookmark($PAGE->course);

$moving = null;
if ($moveid) {
    $original = $service->get_bookmark($moveid);

    if ($moveafter !== null) {
        require_sesskey();
        $after = $service->get_bookmark($moveafter);
        $service->move_bookmark($original, $after->sortorder+1, $after->categoryid);
        redirect(new moodle_url('view.php', array('courseid' => $courseid)));
    } else if ($categoryid !== null) {
        require_sesskey();
        $service->move_bookmark($original, 0, $categoryid);
        redirect(new moodle_url('view.php', array('courseid' => $courseid)));
    }

    $moving             = new stdClass();
    $moving->courseid   = $courseid;
    $moving->moveid     = $moveid;
}

$renderer = $PAGE->get_renderer('block_recommender_service_bookmark');

$jsmodule = array(
    'name' => 'moodle-block_recommender_service_bookmark-dragdrop',
    'fullpath' => '/blocks/recommender/services/bookmark/yui/dragdrop/dragdrop.js',
    'requires' => array('base', 'node', 'io', 'dom', 'dd', 'moodle-core-notification'),
    'strings' => array(array('move', 'moodle')),
     );
$PAGE->requires->js_init_call('M.block_recommender_service_bookmark.init_dragdrop',
     array(array('courseid'=>$courseid)), false, $jsmodule);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bookmark_servicetitle', 'block_recommender'));
echo $renderer->display_more_details($service, $moving);
echo $OUTPUT->footer();
