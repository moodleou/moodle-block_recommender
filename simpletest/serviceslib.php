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

require_once(dirname(__FILE__) . '/lib.php');

abstract class block_recommender_service_testlib extends block_recommender_testlib {

    private $servicename;
    private $classname;

    public function setUp() {
        parent::setUp();

        $calledby = get_class($this);
        $this->servicename = preg_replace('/^test_block_recommender_service_/', '', $calledby);
        $this->classname = preg_replace('/^test_/', '', $calledby);

        // Ensure that we enable the service
        set_config($this->servicename . '_enabled', '1', 'block_recommender');
    }

    public function test_disabled_exception() {
        $calledby = get_class($this);
        $servicename = preg_replace('/^test_block_recommender_service_/', '', $calledby);
        set_config($servicename . '_enabled', '0', 'block_recommender');

        $class = preg_replace('/^test_/', '', $calledby);

        $this->require_course();
        $this->expectException(new block_recommender_exception(
            get_string('servicedisabled', 'block_recommender',
            array('servicename' => $servicename))));
        $service = new $class($this->course);
    }

    public function test_get_block_content() {
        $calledby = get_class($this);
        $class = preg_replace('/^test_/', '', $calledby);

        if (!class_exists($class)) {
            // Ensure that this service exists
            return;
        }

        // A course is required to create a new service
        $this->require_course();
        $service = new $class($this->course);

        // Create any possible data
        if (method_exists($this, 'create_example_content')) {
            $this->create_example_content();
        }

        // Retrieve content for testing
        $content = $service->get_block_content();

        $this->assertIsA($content, 'array');

        foreach ($content as $link) {
            $this->assertIsA($link, 'stdClass');

            // raw and title/url are mutually exclusive
            if (isset($link->raw)) {
                $this->assertFalse(isset($link->title));
                $this->assertFalse(isset($link->url));
            } else {
                $this->assertFalse(isset($link->raw));
                $this->assertIsA($link->url, 'moodle_url');
            }
        }

    }

    public function test_constructor_with_numeric_course() {
        $this->expectException(new block_recommender_exception(
            get_string('errorcallingservice', 'block_recommender',
            array('servicename'=>$this->servicename))));
        $service = new $this->classname(1);
    }

    public function test_get_service_name() {
        $this->require_course();
        // The abstract service class calculates this in a different fashion to the unit tests
        $service = new $this->classname($this->course);
        $this->assertIdentical($service->get_service_name(), $this->servicename);
    }

}
