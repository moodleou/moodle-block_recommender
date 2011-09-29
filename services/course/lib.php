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
 * Recommender block Popular Courses service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../lib.php');

/**
 * Define the number of courses to show in the block
 */
define('SERVICE_POPULARCOURSES_BLOCK_LIMIT', 3);

class block_recommender_service_course extends block_recommender_service {

    private $pattern = null;

    /**
     * Sets the default regular expression options
     *
     * @param object $course course object
     * @param object $pluginconfig plugin config
     */
    public function __construct($course, $pluginconfig = null) {
        parent::__construct($course, $pluginconfig);

        $this->pattern = $this->config->course_shortname_pattern;
        if (!empty($this->pattern) && strpos($this->pattern, '/') === false) {
            $this->pattern = '/' . $this->pattern . '/';
        }
    }


    /**
     * Retrieve the block content for this service
     *
     * @return  array   $links  The block content for this service
     */
    public function get_service_content() {
        global $DB;

        // select courses with the most users in common with ours
        $sql = 'SELECT c.id, c.shortname, c.fullname, COUNT(contextid) AS usercount
                FROM {role_assignments} ra
                JOIN {context} t ON ra.contextid = t.id
                JOIN {course} c ON t.instanceid = c.id
                WHERE roleid = :roleid
                AND userid in
                    (SELECT DISTINCT userid
                    FROM {role_assignments}
                    WHERE roleid = :roleid2 and contextid = :contextid)
                AND contextid != :contextid2 AND c.visible = 1
                GROUP BY c.id, c.shortname, c.fullname
                ORDER BY usercount DESC';

        $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $params = array('roleid' => $this->config->role,
                        'roleid2' => $this->config->role,
                        'contextid' => $context->id,
                        'contextid2' =>$context->id
                        );

        $courses = $DB->get_recordset_sql($sql, $params, 0, SERVICE_POPULARCOURSES_BLOCK_LIMIT);

        // populate links
        $courselinks = array();
        foreach ($courses as $c) {
            $link = new stdClass();
            $link->title    = $c->fullname;
            $link->url      = $this->get_course_url($c);

            $courselinks[] = $link;
        }

        return $courselinks;
    }

    /**
     * Build an appropriate URL for the specified course.
     * Applies configured regular expression to course shortname
     *
     * @param   object  $course The course to build a URL for
     * @return  object          The generated moodle_url
     */
    public function get_course_url($course) {
        if (!empty($this->pattern)) {
            if (preg_match($this->pattern, $course->shortname, $matches)) {
                $linkurl = new moodle_url(preg_replace($this->pattern,
                    $this->config->course_url, $course->shortname));
            }
        }

        if (!isset($linkurl)) {
            $linkurl = new moodle_url('/course/view.php', array('id' => $course->id));
        }

        return $linkurl;
    }
}
