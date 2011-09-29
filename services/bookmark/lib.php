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
 * Recommender block bookmark service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define how many bookmarks to show.
 */
define('SERVICE_BOOKMARKS_LIMIT', 3);
define('SERVICE_BOOKMARKS_MAXCATEGORIES', 3);

require_once(dirname(dirname(__FILE__)) . '/lib.php');

class block_recommender_service_bookmark extends block_recommender_service {

    /**
     * Retrieve the block content for this service
     *
     * @return  array   $links  The block content for this service
     */
    public function get_service_content() {
        return $this->get_links();
    }

    /**
     * Check if this service has the content for display
     *
     * @return  boolean
     */
    public function has_content() {
        global $DB;
        $params = array();
        $params['service']  = 'bookmark';
        $params['courseid'] = $this->course->id;

        $sql = 'SELECT COUNT(1)
                FROM {block_recommender_data}
                WHERE   service     = :service
                AND     courseid    = :courseid
                AND     deleted     = 0';
        $totallinks = $DB->count_records_sql($sql, $params);

        if (!(bool)$totallinks) {
            $this->displaymorelink = false;
        }
        return (bool)$totallinks;
    }

    /**
     * Retrieve the list of categories
     *
     * @return   array               The list of categories to use
     */
    public function get_categories() {
        $categories = array();
        for ($i = 1; $i <= SERVICE_BOOKMARKS_MAXCATEGORIES; $i++) {
            if ($title = $this->config->{'category_'.$i}) {
                $categories[$i] = $title;
            }
        }

        return $categories;
    }

    /**
     * Retrieve a list of all categories, including those which are disabled
     *
     * @return  array   An array of objects containing a title and whether
     *                  that category has been explicitly defined
     */
    public function get_all_categories() {
        $categories = array();
        $categories[0] = new stdClass();
        $categories[0]->title   = get_string('bookmark_defaultcategory', 'block_recommender');
        $categories[0]->defined = 1;

        for ($i = 1; $i <= SERVICE_BOOKMARKS_MAXCATEGORIES; $i++) {
            if ($title = $this->config->{'category_'.$i}) {
                $categories[$i] = new stdClass();
                $categories[$i]->title      = $title;
                $categories[$i]->defined    = 1;

                // Hide the default category if any other category is enabled
                $categories[0]->defined     = 0;
            } else {
                $categories[$i] = new stdClass();
                $categories[$i]->title  = get_string('bookmark_categorytitlen',
                    'block_recommender', $i);
                $categories[$i]->defined    = 0;
            }
        }

        return $categories;
    }

    /**
     * Add a new bookmark
     *
     * @param   string  $url    The URL of the bookmark to add
     * @param   string  $title  The title to display for the URL
     * @param   int     $categoryid The ID of the category to place the bookmark in
     * @return  The created bookmark as returned by get_bookmark (@see get_bookmark)
     */
    public function add_bookmark($url, $title, $categoryid) {
        global $DB, $USER;
        $bookmark = new stdClass();
        $bookmark->service      = 'bookmark';
        $bookmark->userid       = $USER->id;
        $bookmark->timemodified = time();
        $bookmark->deleted      = 0;
        $bookmark->courseid     = $this->course->id;

        $bookmark->customfield2 = $title;
        $bookmark->customfield3 = $url;

        // Build parameters to calculate the sortorder field
        $orderparams = array(
            'service'       => 'bookmark',
            'courseid'      => $this->course->id,
            'deleted'       => 0
        );

        $bookmark->customfield1      = $categoryid;
        $orderparams['customfield1'] = $categoryid;

        $transaction = $DB->start_delegated_transaction();

        // Determine the sortorder of the bookmarks
        $bookmark->customfield4 = $DB->count_records('block_recommender_data', $orderparams);

        $bookmark->id = $DB->insert_record('block_recommender_data', $bookmark);

        $transaction->allow_commit();
        return $this->get_bookmark($bookmark->id);
    }

    /**
     * Return the full details of the specified bookmark
     *
     * @param   integer  $id The ID of the bookmark to retrieve
     * @return  stdClass The database record for the specified bookmark
     */
    public function get_bookmark($id) {
        global $DB;

        $sql = 'SELECT
                    id, service, userid, courseid, timemodified, deleted,
                    customfield1 AS categoryid, customfield2 AS title,
                    customfield3 AS url, customfield4 AS sortorder
                FROM {block_recommender_data}
                WHERE   id          = :id
                AND     courseid    = :courseid';
        return $DB->get_record_sql($sql, array('id' => $id, 'courseid' => $this->course->id));
    }

    /**
     * Update an existing bookmark
     *
     * @param   object  $data       An updated object
     * @return  The updated bookmark as returned by get_bookmark (@see get_bookmark)
     */
    public function update_bookmark($data) {
        global $DB, $USER;

        $bookmark = new stdClass();
        $bookmark->userid       = $USER->id;
        $bookmark->timemodified = time();
        $bookmark->id           = $data->id;

        $original = $this->get_bookmark($data->id);

        if (isset($data->title)) {
            $bookmark->customfield2 = $data->title;
        }
        if (isset($data->url)) {
            $bookmark->customfield3 = $data->url;
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('block_recommender_data', $bookmark);

        if (isset($data->categoryid)) {
            $this->move_bookmark($original, $original->sortorder, $data->categoryid);
        }
        $transaction->allow_commit();

        // Return the updated bookmark
        return $this->get_bookmark($data->id);
    }

    /**
     * Move a bookmark to a new location in the specified category
     *
     * @param   object $original    The bookmark to move. This should be a record as retrieved
     *                              by get_bookmark (@see get_bookmark)
     * @param   integer $neworder   The new sortorder of the bookmark
     * @param   integer $categoryid The category ID for the bookmark
     */
    public function move_bookmark($original, $neworder, $categoryid) {
        global $DB, $USER;

        $transaction = $DB->start_delegated_transaction();

        $orderparams = array(
            'service'       => 'bookmark',
            'courseid'      => $this->course->id,
            'customfield1'  => $categoryid,
            'deleted'       => 0
        );

        $max = $DB->count_records('block_recommender_data', $orderparams);

        if ($neworder < 0) {
            //sortorder starts at 0
            $neworder = 0;
        } else if ($neworder > $max) {
            // sortorder ends at the number of bookmarks
            $neworder = $max;
        }

        if ($original->categoryid == $categoryid && $neworder == $original->sortorder) {
            $transaction->allow_commit();
            // nothing has canged, return without doing anything
            return true;
        }

        // SQL to cast customfield4 to an INT so we can do integer operations on it
        $castsql = $DB->sql_cast_char2int('customfield4');

        $sql = 'UPDATE {block_recommender_data} SET
                    customfield4 = '.$castsql.' +1
                WHERE '.$castsql.' >= :neworder AND service = :service
                    AND courseid = :courseid AND customfield1 = :categoryid
                    AND deleted = 0';

        $DB->execute($sql, array(
            'neworder' => $neworder,
            'service' => 'bookmark',
            'courseid' => $original->courseid,
            'categoryid'=>$categoryid)
        );

        $sql = 'UPDATE {block_recommender_data}
                SET customfield4 = :neworder, customfield1 = :categoryid
                WHERE id = :id';

        $DB->execute($sql, array(
            'neworder' => $neworder,
            'id' => $original->id,
            'categoryid' => $categoryid)
        );

        // shuffle the links down to prevent a gap
        $sql = 'UPDATE {block_recommender_data}
                SET customfield4 = '.$castsql.' -1
                WHERE '.$castsql.' > :originalorder AND service = :service
                    AND courseid = :courseid AND customfield1 = :originalcategoryid
                    AND deleted = 0';

        $DB->execute($sql, array(
            'originalorder' => $original->sortorder,
            'service' => 'bookmark',
            'courseid' => $original->courseid,
            'originalcategoryid' => $original->categoryid)
        );

        $transaction->allow_commit();
    }

    /**
     * Mark the selected record as deleted
     *
     * @param   object $bookmark    The bookmark to move. This should be a record as retrieved by
     *                              get_bookmark (@see get_bookmark)
     * @return  boolean true if the delete was successful
     */
    public function delete_bookmark($bookmark) {
        global $DB, $USER;

        $bookmark->userid       = $USER->id;
        $bookmark->timemodified = time();
        $bookmark->deleted      = 1;

        $transaction = $DB->start_delegated_transaction();
        $return = $DB->update_record('block_recommender_data', $bookmark);

        $this->reset_order($bookmark->categoryid);
        $transaction->allow_commit();
        return $return;
    }

    /**
     * Retrieve all links for the current courseid
     *
     * This function will only retrieve the top links as specified in the settings page
     *
     * @return  array   The links stored in the database for this course
     */
    public function get_links() {
        global $DB;

        // General params
        $params = array();
        $params['service']  = 'bookmark';
        $params['courseid'] = $this->course->id;

        $sql = 'SELECT
                    id, service, userid, courseid, timemodified, deleted,
                    customfield1 AS categoryid, customfield2 AS title,
                    customfield3 AS url, customfield4 AS sortorder
                FROM {block_recommender_data}
                WHERE   service     = :service
                AND     courseid    = :courseid
                AND     deleted     = 0 ';

        if ($displaytype = $this->config->displaytype) {
            // Display the top results from one category
            $sql .= 'AND customfield1 = :customfield1 ';
            $params['customfield1'] = $displaytype;

            $limit = SERVICE_BOOKMARKS_LIMIT;
        } else {
            // Display the top result from each category
            $sql .= 'AND customfield4 = :customfield4 ';
            $params['customfield4'] = 0;
        }

        $sql .= 'ORDER BY customfield4 ASC, customfield1 ASC ';

        if (isset($limit)) {
            $bookmarks = $DB->get_records_sql($sql, $params, 0, $limit);
        } else {
            $bookmarks = $DB->get_records_sql($sql, $params);
        }

        // Convert each URL to a moodle_url
        foreach ($bookmarks as $bookmark) {
            $bookmark->url = new moodle_url($bookmark->url);
        }

        return $bookmarks;
    }

    /**
     * Retrieve all undeleted links for all categories sorted by category, and then order
     *
     * The url will be converted to a moodle_url
     *
     * @param   integer $categoryid The ID of a single category to retrieve links for
     * @return  array   The complete list of bookmarks
     */
    public function get_all_links($categoryid = null) {
        global $DB;

        // General params
        $params = array();
        $params['service']  = 'bookmark';
        $params['courseid'] = $this->course->id;

        $sql = 'SELECT
                    id, service, userid, courseid, timemodified, deleted,
                    customfield1 AS categoryid, customfield2 AS title,
                    customfield3 AS url, customfield4 AS sortorder
                FROM {block_recommender_data}
                WHERE   service     = :service
                AND     courseid    = :courseid
                AND     deleted     = 0 ';
        if ($categoryid !== null) {
            $sql .= 'AND customfield1 = :categoryid ';
            $params['categoryid'] = $categoryid;
        }

        $sql .= 'ORDER BY customfield1 ASC, customfield4 ASC';

        $bookmarks = $DB->get_records_sql($sql, $params);

        // Convert each URL to a moodle_url
        foreach ($bookmarks as $bookmark) {
            $bookmark->url = new moodle_url($bookmark->url);
        }

        return $bookmarks;
    }

    /**
     * Reset the ordering of bookmarks within a courseid and/or category
     *
     * @param   int       $courseid   The course
     * @param   int       $category   The category
     * @return  void
     */
    public function reset_order($categoryid) {
        global $DB;

        $params = array();
        $params['courseid']     = $this->course->id;
        $params['customfield1'] = $categoryid;
        $params['deleted']      = 0;

        $transaction = $DB->start_delegated_transaction();
        $bookmarks = $DB->get_records('block_recommender_data', $params,
            'customfield4 ASC, timemodified ASC');

        $order = 0;
        foreach ($bookmarks as $bookmark) {
            if ($bookmark->customfield4 != $order) {
                $bookmark->customfield4 = $order;
                $DB->update_record('block_recommender_data', $bookmark);
            }
            $order++;
        }

        $transaction->allow_commit();
    }

    /**
     * Basic search of all bookmaraks using the supplies search terms and ignore terms
     *
     * All search and ignore terms may be specified using both their friendly name
     * (e.g. sortorder) and their database name (e.g. customfield4).
     *
     * @param   array   $searchterms The terms to include within the search
     * @param   array   $ignoreterms The terms to exclude within the search
     * @return  array   The complete list of bookmarks as returned by the search
     */
    public function search_bookmarks($searchterms, $ignoreterms = array()) {
        global $DB;

        // These are the valid field mappings
        $valid_fields = array(
            'id'            => 'id',
            'customfield1'  => 'category',
            'customfield2'  => 'title',
            'customfield3'  => 'url',
            'customfield4'  => 'sortorder',
        );

        $sql = 'SELECT
                    id, customfield2 AS title, customfield3 AS url
                FROM {block_recommender_data}
                WHERE   service     = :service
                AND     courseid    = :courseid
                AND     deleted     = 0 ';

        $searchparams = array(
            'service'   => 'bookmark',
            'courseid'  => $this->course->id,
        );

        // Build the search parameters from the submitted data
        foreach ($valid_fields as $key => $field) {
            if (isset($searchterms->$field)) {
                $searchparams[$key] = $searchterms->$field;
                $sql .= "AND {$key} = :{$key} ";
            } else if (isset($searchterms->$key)) {
                $searchparams[$key] = $searchterms->$key;
                $sql .= "AND {$key} = :{$key} ";
            }
        }

        // Build the ignore parameters from the submitted data
        foreach ($valid_fields as $key => $field) {
            if (isset($ignoreterms->$field)) {
                $searchparams[$key] = $ignoreterms->$field;
                $sql .= "AND {$key} != :{$key} ";
            } else if (isset($ignoreterms->$key)) {
                $searchparams[$key] = $ignoreterms->$key;
                $sql .= "AND {$key} != :{$key} ";
            }
        }

        return $DB->get_records_sql($sql, $searchparams);
    }

}
