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
 * Add a new bookmark to the recommender bookmark service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/add_form.php');

// Retrieve submitted parameters
$data = new stdClass();
$data->courseid     = required_param('courseid', PARAM_INT);
$data->id           = optional_param('id', 0, PARAM_INT);

// Require login and permissions
require_login($data->courseid, false);
require_capability('block/recommender:addbookmark', $PAGE->context);

$service = new block_recommender_service_bookmark($PAGE->course);

if ($data->id) {
    $title = get_string('bookmark_editbookmark', 'block_recommender');
    $data = $service->get_bookmark($data->id);
} else {
    $title = get_string('bookmark_addbookmark', 'block_recommender');
}

// Set the page parameters
$pageparams = (array) $data;
$PAGE->set_url('/blocks/recommender/services/bookmark/add.php', $pageparams);
$PAGE->set_title($title.' - '.get_string('recommendertitle', 'block_recommender'));
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add breadcrumbs
$PAGE->navbar->add(get_string('recommendertitle', 'block_recommender'));
$PAGE->navbar->add($title);

// Determine the available categories required for form creation
$categories = $service->get_categories();
if (isset($data->categoryid) && $data->categoryid == 0) {
    $categories = array_merge(
        array('0' => get_string('bookmark_defaultcategory', 'block_recommender')),
        $categories);
}
$addform = new bookmark_add_form(null, array('categories' => $categories, 'edit' => $data->id));

$redirectto = new moodle_url('/blocks/recommender/services/bookmark/view.php',
    array('courseid' => $data->courseid));

if ($addform->is_cancelled()) {
    // Form was cancelled. Redirect back to the course page
    redirect($redirectto);
} else if ($newdata = $addform->get_data()) {
    // Form was submitted
    if (isset($newdata->id) && $newdata->id) {
        // We're updating an existing bookmark
        $service->update_bookmark($newdata);
    } else {
        if (!isset($newdata->categoryid)) {
            $newdata->category = 0;
        }
        // We're creating a new bookmark
        $service->add_bookmark(
            $newdata->url,
            $newdata->title,
            $newdata->categoryid
        );
    }
    redirect($redirectto);
}

// Set the defaults
$addform->set_data($data);

// Display the page
echo $OUTPUT->header();
$addform->display();
echo $OUTPUT->footer();
