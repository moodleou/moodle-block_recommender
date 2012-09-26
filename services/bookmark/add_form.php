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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir.'/validateurlsyntax.php');

class bookmark_add_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // Add/Edit bookmark
        if ($this->_customdata['edit']) {
            $mform->addElement('header', 'general',
                get_string('bookmark_editbookmark', 'block_recommender'));
        } else {
            $mform->addElement('header', 'general',
                get_string('bookmark_addbookmark', 'block_recommender'));
        }

        // List the categories
        if (is_array($this->_customdata['categories'])
            && count($this->_customdata['categories']) > 0) {

            $mform->addElement('select', 'categoryid',
                get_string('bookmark_category', 'block_recommender'),
                $this->_customdata['categories']);
            $mform->setType('categoryid', PARAM_INT);
            $mform->addRule('categoryid', null, 'required',  null, 'client');
        } else {
            $mform->addElement('hidden', 'categoryid');
            $mform->setType('categoryid', PARAM_INT);
            $mform->setDefault('categoryid', 0);
        }

        // The title
        $mform->addElement('text', 'title', get_string('bookmark_title', 'block_recommender'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required',  null, 'client');
        $mform->addRule('title', null, 'maxlength', 255);

        // The URL
        $mform->addElement('text', 'url', get_string('bookmark_url', 'block_recommender'));
        $mform->setType('url', PARAM_TEXT);
        $mform->addRule('url', null, 'required',  null, 'client');

        // ID of the bookmark being edited
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // The id of the course this bookmark belongs to
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    public function definition_after_data() {
        $mform = $this->_form;
        $mform->applyFilter('url', 'bookmark_add_form::strip_trailing_slash');
    }

    public static function strip_trailing_slash($url) {
        return rtrim($url, '/');
    }

    public function validation($data, $files) {
        global $PAGE;
        $errors = parent::validation($data, $files);

        $service = new block_recommender_service_bookmark($PAGE->course);

        $ignoreterms = new stdClass();
        if (isset($data['id'])) {
            $ignoreterms->id = $data['id'];
        }

        $searchterms = new stdClass();
        $searchterms->delete = 0;

        if (isset($data['category'])) {
            $searchterms->category = $data['category'];
        }

        // Check that this title isn't already in use within the same course
        $searchterms->title = $data['title'];
        $results = $service->search_bookmarks($searchterms, $ignoreterms);
        unset($searchterms->title);
        if ($result = array_shift($results)) {
            $errors['title'] = get_string('bookmark_titleinuse',
                'block_recommender', (array) $result);
        }

        // validate the URL against rfc allow http, https, ftp, but no user section
        if (!validateUrlSyntax($data['url'], 's+H?S?F?u-')) {
            $errors['url'] = get_string('bookmark_urlinvalid', 'block_recommender');
        }

        // Check that this URL isn't already in use within the same course
        $searchterms->url = $data['url'];
        $results = $service->search_bookmarks($searchterms, $ignoreterms);
        unset($searchterms->url);
        if ($result = array_shift($results)) {
            $errors['url'] = get_string('bookmark_urlinuse',
                'block_recommender', (array) $result);
        }
        return $errors;
    }
}
