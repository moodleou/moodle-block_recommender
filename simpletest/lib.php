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
 * Recommender block unit tests
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_recommender_testlib extends UnitTestCaseUsingDatabase {


    public static $includecoverage = array('blocks/recommender/lib.php');

    protected $testtables = array(
        'lib'                   => array(
            'course',
            'config_plugins',
            'context',
            'capabilities',
            'role',
            'role_assignments',
            'role_capabilities',
        ),
        'blocks/recommender'    => array(
            'block_recommender_data',
        ),
    );

    protected $setup = false;

    /**
     * Prepare for testing
     *
     * If adding any additional tables in a child class, these should be
     * added to the $testtables array before calling the parent::setUp()
     * method
     *
     * @return  void
     */
    public function setUp() {
        parent::setUp();

        // Create any required test tables
        foreach ($this->testtables as $dir => $tables) {
            $this->create_test_tables($tables, $dir);
        }

        $this->switch_to_test_db();

        // Load all capabilities in case any tests need to test them
        update_capabilities('block_recommender');
        $this->setup = true;
    }

    protected function add_test_tables($path, $tables) {
        if ($this->setup) {
            return;
        }

        if (!isset($this->testtables[$path])) {
            $this->testtables[$path] = array();
        }

        foreach ($tables as $table) {
            if (!isset($this->testtables[$path][$table])) {
                $this->testtables[$path][] = $table;
            }
        }
    }

    protected function require_course() {
        if (isset($this->courseid)) {
            return $this->courseid;
        }

        $course = new stdClass();
        $course->category   = 1;
        $course->fullname   = 'Test Course';
        $course->shortname  = 'testcourse';
        $course->summary    = 'Test Course';
        $course->id         = $this->testdb->insert_record('course', $course);

        $this->course = $this->testdb->get_record('course', array('id' => $course->id));
    }
}
