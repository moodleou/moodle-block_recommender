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
 * Recommender block course service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/simpletest/serviceslib.php');

class test_block_recommender_service_course extends block_recommender_service_testlib {
    public static $includecoverage = array('blocks/recommender/services/course/lib.php');

    public function test_get_course_url() {
        $this->require_courses();

        $service = new block_recommender_service_course($this->course1);

        $url = $service->get_course_url($this->course1);
        $checkagainst = new moodle_url('/course/view.php', array('id' => $this->course1->id));
        $this->assertIdentical($url->out(), $checkagainst->out());
    }

    public function test_get_course_url_with_regex() {
        $this->require_courses();

        set_config('course_course_shortname_pattern', '^(.*)-.*$', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($this->course1);

        $url = $service->get_course_url($this->course1);
        $this->assertIdentical($url->out(), 'http://www.example.org/testcourse');
    }

    public function test_get_course_url_with_complete_regex() {
        $this->require_courses();

        set_config('course_course_shortname_pattern', '/^(.*)-.*$/', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($this->course1);

        $url = $service->get_course_url($this->course1);
        $this->assertIdentical($url->out(), 'http://www.example.org/testcourse');
    }

    public function test_get_course_url_with_unmatched_regex() {
        $this->require_courses();

        set_config('course_course_shortname_pattern', '/^DONTMATCHME$/', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($this->course1);

        $url = $service->get_course_url($this->course1);
        $checkagainst = new moodle_url('/course/view.php', array('id' => $this->course1->id));
        $this->assertIdentical($url->out(), $checkagainst->out());
    }

    public function test_get_course_url_with_unmatched_shortname() {
        $this->require_courses();

        set_config('course_course_shortname_pattern', '/^(.*)-.*$/', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($this->course3);

        $url = $service->get_course_url($this->course3);
        $checkagainst = new moodle_url('/course/view.php', array('id' => $this->course3->id));
        $this->assertIdentical($url->out(), $checkagainst->out());
    }

    protected function require_courses() {
        $course = new stdClass();
        $course->category   = 1;
        $course->fullname   = 'Test Course One';
        $course->shortname  = 'testcourse-one';
        $course->summary    = 'Test Course One';
        $course->id         = $this->testdb->insert_record('course', $course);

        $this->course1 = $this->testdb->get_record('course', array('id' => $course->id));

        $course = new stdClass();
        $course->category   = 1;
        $course->fullname   = 'Test Course Two';
        $course->shortname  = 'testcourse-two';
        $course->summary    = 'Test Course Two';
        $course->id         = $this->testdb->insert_record('course', $course);
        $this->course2 = $this->testdb->get_record('course', array('id' => $course->id));

        $course = new stdClass();
        $course->category   = 1;
        $course->fullname   = 'Test Course Three';
        $course->shortname  = 'somethingdifferent';
        $course->summary    = 'Test Course Three';
        $course->id         = $this->testdb->insert_record('course', $course);
        $this->course3 = $this->testdb->get_record('course', array('id' => $course->id));
    }

}
