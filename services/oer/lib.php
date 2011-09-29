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
 * Recommender block OER service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define how many bookmarks to show in the block.
 */
define('SERVICE_OER_LIMIT', 3);
define('SERVICE_OER_MORE_LIMIT', 10);

require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->libdir . '/filelib.php');

class block_recommender_service_oer extends block_recommender_service {

    /**
     * Retrieve the block content for this service
     *
     * @return  array   $links  The block content for this service
     */
    public function get_service_content() {
        $links = $this->get_links();

        if (empty($links)) {
            // there will be no 'more' content to display
            $this->displaymorelink = false;
        }

        return $links;
    }

    /**
     * Retrieve the content object for the service
     *
     * @param   boolean $view       Whether the links are retrieved for view page
     * @return  mixed   $links      The array of objects with links data
     */
    public function get_links($view = false) {
        global $CFG;

        $links = array();

        // inital settings
        $limit = SERVICE_OER_LIMIT;
        $details = false;

        // settings for the view page
        if ($view) {
            $limit = SERVICE_OER_MORE_LIMIT;
            $details = true;
        }

        $indexed = $this->config->indexed;

        if ($indexed) {
            $url = $CFG->wwwroot.'/course/view.php?name='.$this->course->shortname;
            $response = $this->get_recommendations($url, $limit, $details);
        } else {
            $searchstring = $this->course->fullname;
            $response = $this->search_recommendations($searchstring, $limit);
        }

        if (empty($response)) {
            return false;
        }

        // Let's capture errors
        $olderrormode = libxml_use_internal_errors(true);
        // Load XML
        $xml = simplexml_load_string($response);

        // Is XML valid?
        if (!$xml) {
            $errors = get_string('oer_xmlloadfailed', 'block_recommender');
            // we probably have curl error in the response output
            $errors .= ': '.clean_param((string)$response, PARAM_TEXT);
            throw new block_recommender_service_oer_exception($errors);
        }

        // Stop capturing errors
        libxml_clear_errors();
        libxml_use_internal_errors($olderrormode);

        // process results
        $results = array();
        if ($indexed && isset($xml->recommendation)) {
            $results = $xml->recommendation;
        } else if (isset($xml->result)) {
            $results = $xml->result;
        }

        // populate the links array
        foreach ($results as $node) {
            $link = new stdClass();
            $link->title = $this->clean_and_check_field('title', $node, true);
            if ($indexed) {
                $link->url = $this->clean_and_check_field('link', $node, true, true);
            } else {
                $link->url = $this->clean_and_check_field('uri', $node, true, true);
            }
            $link->url = new moodle_url($link->url);
            if ($view) {
                $link->description = $this->clean_and_check_field('description', $node);
                $link->collection = $this->clean_and_check_field('collection', $node);
            }
            $links[] = $link;
        }

        return $links;
    }

    /**
     * Perform the curl query
     *
     * @param   string  $request    The requested URL
     * @return  string  $response   The http request response (xml text)
     */
    private function fetch_request($request) {
        $c =  new curl(array('cache' => true, 'module_cache'=> 'recommender_service_oer'));

        $response = $c->get($request);

        return $response;
    }

    /**
     * Perform the OER search request
     *
     * @param   string  $searchstring   The search string (usually course shortname)
     * @param   integer $limit          The results limit
     * @return  string  $response       The xml output of the search
     */
    private function search_recommendations($searchstring, $limit) {

        $params = array(
            'terms' => $searchstring,
            'per_page' => $limit,
        );
        $request = new moodle_url('http://www.oerrecommender.org/search/results.xml', $params);

        $response = $this->fetch_request($request->out());

        return $response;
    }

    /**
     * Get OER recommendations for indexed sites
     *
     * @param   string  $url        The u parameter
     * @param   integer $limit      The results limit
     * @param   boolean $details    Whether to fetch details (we need that for view page)
     * @return  string  $response   The xml output of the request
     */
    private function get_recommendations($url, $limit, $details = false) {

        $params = array(
            'u' => $url,
            'order' => 'relevance',
            'limit' => $limit,
        );
        if ($details) {
            $params['details'] = 'true';
        }

        $request = new moodle_url('http://www.oerrecommender.org/recommendations.xml', $params);
        $response = $this->fetch_request($request);

        return $response;
    }

    /**
     * Helper function to clean data and validate xml fields
     *
     * @param   string $fieldname  The fieldname to process
     * @param   object $xml        XML data object
     * @param   bool   $noempy     Ensure the string is not empty
     * @param   bool   $isurl      Do extra validation for url
     * @return  string $field      The checked and (potentially) modified text
     */
    private function clean_and_check_field($fieldname, $xml, $noempty = false, $isurl = false) {
        if (!isset($xml->$fieldname)) {
            throw new block_recommender_service_oer_exception(
                get_string('errormissingfield', 'block_recommender', $fieldname));
        }

        if ($isurl) {
            $field = clean_param((string)$xml->$fieldname, PARAM_URL);
        } else {
            $field = clean_param((string)$xml->$fieldname, PARAM_TEXT);
        }

        $field = s(trim($field));
        $field = stripslashes($field);

        if ($noempty && empty($field)) {
            throw new block_recommender_service_oer_exception(
                get_string('erroremptyfield', 'block_recommender', $fieldname));
        }

        return $field;
    }
}

/*
 * Exceptions
 */

/**
 * Base OER service exception
 */
class block_recommender_service_oer_exception      extends block_recommender_service_exception {

}
