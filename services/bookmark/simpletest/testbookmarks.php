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

require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/simpletest/serviceslib.php');

class test_block_recommender_service_bookmark extends block_recommender_service_testlib {
    public static $includecoverage = array('blocks/recommender/services/bookmark/lib.php');

    private $testdata = array(
        '0' => array(
            'url'       => 'http://www.example.org',
            'title'     => 'Example 0',
            'category'  => 1,
        ),
        '1' => array(
            'url'       => 'http://www.example.org/1',
            'title'     => 'Example 1',
            'category'  => 1,
        ),
        '2' => array(
            'url'       => 'http://www.example.org/2',
            'title'     => 'Example 2',
            'category'  => 2,
        ),
        '3' => array(
            'url'       => 'http://www.example.org/3',
            'title'     => 'Example 3',
            'category'  => 1,
        ),
        '4' => array(
            'url'       => 'http://www.example.org/4',
            'title'     => 'Example 4',
            'category'  => 1,
        ),
        '5' => array(
            'url'       => 'http://www.example.org/5',
            'title'     => 'Example 5',
            'category'  => 1,
        ),
        '6' => array(
            'url'       => 'http://www.example.org/6',
            'title'     => 'Example 6',
            'category'  => 1,
        ),
        '7' => array(
            'url'       => 'http://www.example.org/7',
            'title'     => 'Example 7',
            'category'  => 1,
        ),
        '8' => array(
            'url'       => 'http://www.example.org/8',
            'title'     => 'Example 8',
            'category'  => 1,
        ),
        '9' => array(
            'url'       => 'http://www.example.org/9',
            'title'     => 'Example 9',
            'category'  => 2,
        ),
        '10' => array(
            'url'       => 'http://www.example.org/10',
            'title'     => 'Example 10',
            'category'  => 2,
        ),
        '11' => array(
            'url'       => 'http://www.example.org/11',
            'title'     => 'Example 11',
            'category'  => 2,
        ),
        '12' => array(
            'url'       => 'http://www.example.org/12',
            'title'     => 'Example 12',
            'category'  => 0,
        ),
        '13' => array(
            'url'       => 'http://www.example.org/13',
            'title'     => 'Example 13',
            'category'  => 0,
        ),
        '14' => array(
            'url'       => 'http://www.example.org/14',
            'title'     => 'Example 14',
            'category'  => 0,
        ),
        '15' => array(
            'url'       => 'http://www.example.org/15',
            'title'     => 'Example 15',
            'category'  => 0,
        ),
    );

    public function test_bookmark_creation() {
        $this->require_course();

        // Create the first bookmark
        $testdata = $this->testdata[0];

        $service = new block_recommender_service_bookmark($this->course);

        $bookmark = $service->add_bookmark($testdata['url'], $testdata['title'],
            $testdata['category']);
        $this->assertIsA($bookmark, 'stdClass');

        $this->assertEqual($bookmark->url,      $testdata['url']);
        $this->assertEqual($bookmark->title,    $testdata['title']);
        $this->assertEqual($bookmark->courseid, $this->course->id);
        $this->assertEqual($bookmark->categoryid, $testdata['category']);
        $this->assertEqual($bookmark->deleted,  0);
        $this->assertEqual($bookmark->service,  'bookmark');
        $this->assertEqual($bookmark->sortorder,    0);

        // Create the second bookmark
        $testdata = $this->testdata[1];

        $bookmark = $service->add_bookmark($testdata['url'], $testdata['title'],
            $testdata['category']);
        $this->assertIsA($bookmark, 'stdClass');

        $this->assertEqual($bookmark->url,      $testdata['url']);
        $this->assertEqual($bookmark->title,    $testdata['title']);
        $this->assertEqual($bookmark->courseid, $this->course->id);
        $this->assertEqual($bookmark->categoryid, $testdata['category']);
        $this->assertEqual($bookmark->deleted,  0);
        $this->assertEqual($bookmark->service,  'bookmark');

        // Order should have changed
        $this->assertEqual($bookmark->sortorder,    1);
    }

    public function test_bookmark_update() {
        $this->require_bookmarks(false);

        // Retrieve all of the links
        $service = new block_recommender_service_bookmark($this->course);
        $links = $service->get_links();
        $link = array_pop($links);

        $this->assertIsA($link, 'stdClass');

        // Update the link title
        $updatedtitle = 'Updated Title';
        $update = new stdClass();
        $update->id         = $link->id;
        $update->title      = $updatedtitle;
        $updatedbookmark    = $service->update_bookmark($update);
        $this->assertEqual($updatedbookmark->title,         $updatedtitle);

        // Check that the params haven't changed
        $this->assertEqual($updatedbookmark->url,           $link->url);
        $this->assertEqual($updatedbookmark->categoryid,    $link->categoryid);

        // Update the link URL
        $updatedurl = 'http://www.moodle.net';
        $update = new stdClass();
        $update->id         = $link->id;
        $update->url        = $updatedurl;
        $updatedbookmark    = $service->update_bookmark($update);
        $this->assertEqual($updatedbookmark->title,         $updatedtitle);
        $this->assertEqual($updatedbookmark->url,           $updatedurl);

        // Check that the params haven't changed
        $this->assertEqual($updatedbookmark->categoryid,    $link->categoryid);

        // Update the link category
        $updatedcategory = 2;
        $update = new stdClass();
        $update->id         = $link->id;
        $update->categoryid = $updatedcategory;
        $updatedbookmark    = $service->update_bookmark($update);
        $this->assertEqual($updatedbookmark->title,         $updatedtitle);
        $this->assertEqual($updatedbookmark->url,           $updatedurl);
        $this->assertEqual($updatedbookmark->categoryid,    $updatedcategory);
    }

    public function test_bookmark_creation_category() {
        // Require all bookmarks including their categories
        $this->require_bookmarks(true);

        // Retrieve all of the links
        $service = new block_recommender_service_bookmark($this->course);
        $links = $service->get_links();

        // Check the sortorder of bookmarks
        $this->confirm_order($service);
    }

    public function test_get_top_links() {
        $this->require_bookmarks();

        $service = new block_recommender_service_bookmark($this->course);

        // Retrieve overall top links
        $links = $service->get_links();

        $testdata = $this->testdata[0];

        $this->assertIsA($links, 'array');

        $order = 0;
        foreach ($links as $link) {
            $this->assertEqual($link->courseid, $this->course->id);
            $this->assertEqual($link->deleted,  0);
            $this->assertEqual($link->service,  'bookmark');
            $this->assertEqual($link->sortorder,    $order);
        }

        // Retrieve overall top for one category
        $links = $service->get_links(1);

        $testdata = $this->testdata[0];

        $this->assertIsA($links, 'array');

        $order = 0;
        foreach ($links as $link) {
            $this->assertEqual($link->courseid, $this->course->id);
            $this->assertEqual($link->deleted,  0);
            $this->assertEqual($link->service,  'bookmark');
            $this->assertEqual($link->sortorder,    $order);
            $order++;
        }
    }

    public function test_bookmark_delete() {
        $this->require_course();

        $service = new block_recommender_service_bookmark($this->course);

        $links = $service->get_links();
        $this->assertEqual(count($links), 0);

        $data = $this->testdata[0];

        // Add a test bookmark
        $link = $service->add_bookmark($data['url'], $data['title'], $data['category']);

        // It should be the only link returned by get_links
        $links = $service->get_links();
        $this->assertEqual(count($links), 1);

        // This bookmark should exist and should not be deleted
        $result = $service->get_bookmark($link->id);
        $this->assertIsA($result, 'stdClass');
        $this->assertEqual($result->deleted, 0);

        // Test deletion
        $service->delete_bookmark($link);

        // Confirm that the bookmark has been marked as deleted
        $result = $service->get_bookmark($link->id);
        $this->assertIsA($result, 'stdClass');
        $this->assertEqual($result->deleted, 1);

        // And that get_links no longer returns the link
        $links = $service->get_links();
        $this->assertEqual(count($links), 0);
    }

    public function test_get_categories() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        $categories = $service->get_categories();

        $this->assertIsA($categories, 'array');
        foreach ($categories as $catid => $category) {
            $this->assertIsA($catid,    'integer');
            $this->assertIsA($category, 'String');
        }
    }

    public function test_get_all_categories() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        $categories = $service->get_all_categories();

        $this->assertIsA($categories, 'array');
        $this->assertEqual(count($categories), 4);
        foreach ($categories as $catid => $category) {
            $this->assertIsA($catid,    'integer');
            $this->assertIsA($category, 'stdClass');
            $this->assertTrue(isset($category->title));
            $this->assertTrue(isset($category->defined));
        }
    }

    public function test_search_bookmarks() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Our initial search terms
        $find   = new stdClass();
        $ignore = new stdClass();

        // Searching with no find and/or ignore should return all results
        $results = $service->search_bookmarks($find, $ignore);
        $this->assertIsA($results, 'array');
        $this->assertEqual(count($results), count($this->testdata));

        // Search for all those in category 1
        $find->category = 1;
        $shouldfind = 0;
        $foundcat = null;
        foreach ($this->testdata as $id => $d) {
            if ($d['category'] == 1) {
                $shouldfind++;
                $foundcat = $d;
            }
        }
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertIsA($results, 'array');
        $this->assertEqual(count($results), $shouldfind);

        // And test using the customfield name instead of the friendlyname
        unset($find->category);
        $find->customfield1 = 1;
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertIsA($results, 'array');
        $this->assertEqual(count($results), $shouldfind);

        // And ignore one of those
        $ignore->title = $foundcat['title'];
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertIsA($results, 'array');
        $this->assertEqual(count($results), $shouldfind - 1);

        // And test using the customfield name instead of the friendlyname
        unset($ignore->title);
        $ignore->customfield2 = $foundcat['title'];
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertIsA($results, 'array');
        $this->assertEqual(count($results), $shouldfind - 1);
    }

    public function test_get_block_content_with_displaytype() {
        // Call parent::test_get_block_content again adjusting the displaytype to cover all cases
        set_config('bookmark_displaytype', '1', 'block_recommender');
        parent::test_get_block_content();
    }

    public function test_get_all_links() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        $links = $service->get_all_links();

        $this->assertIsA($links, 'array');

        // Check the sortorder of bookmarks
        $this->confirm_order($service);
    }

    public function test_reset_order() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Get the initial data
        $start = $service->get_all_links();

        // Test resetting order on a null category
        $service->reset_order(null);
        $links = $service->get_all_links();

        // There should be no change
        $this->assertIdentical($start, $links);

        // Test resetting sortorder on a used category
        $service->reset_order(1);
        $links = $service->get_all_links();

        // There should be no change
        $this->assertIdentical($start, $links);

        // Now make some changes in the database to the 'null' category sortorder
        $this->testdb->set_field('block_recommender_data', 'customfield4', '-1',
                array('service' => 'bookmark', 'customfield1' => null));

        // Test resetting sortorder on a null category
        $service->reset_order(null);

        // Check the sortorder of bookmarks
        $this->confirm_order($service);

        // Now make some changes in the database to the category 1
        $this->testdb->set_field('block_recommender_data', 'customfield4', '999',
                array('service' => 'bookmark', 'customfield1' => '1'));

        // Test resetting sortorder on category 1
        $service->reset_order(1);
        $links = $service->get_all_links();

        // Check the sortorder of bookmarks
        $this->confirm_order($service);
    }

    public function test_move_order_nowhere() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 1
        $service->move_bookmark($start, $start->sortorder, 1);

        $this->assertFalse($this->testdb->is_transaction_started());
    }

    public function test_move_order_up() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 1
        $service->move_bookmark($start, 1, 1);

        // Confirm the order is still correct
        $this->confirm_order($service);
    }

    public function test_move_order_down() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 1
        $service->move_bookmark($start, 6, 1);

        // Confirm the sortorder is still correct
        $this->confirm_order($service);
    }

    public function test_move_order_above_max() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }
        // And move it to position 1
        $service->move_bookmark($start, 100, 1);

        // Confirm the sortorder is still correct
        $this->confirm_order($service);
    }

    public function test_move_order_up_new_category() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }
        // And move it to position 1 on category 2
        $service->move_bookmark($start, 1, 2);

        // Confirm the sortorder is still correct
        $this->confirm_order($service);
    }

    public function test_move_order_down_new_category() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the first link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 1) {
                $start = $link;
            }
        }
        // And move it to position 3 on category 2
        $service->move_bookmark($start, 1, 2);

        // Confirm the sortorder is still correct
        $this->confirm_order($service);
    }

    public function test_move_order_new_category_above_max() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 2
        $service->move_bookmark($start, 100, 2);

        // Confirm the sortorder is still correct
        $this->confirm_order($service);
    }

    public function test_move_order_empty_category() {
        $this->require_bookmarks(true);

        $service = new block_recommender_service_bookmark($this->course);

        // Confirm that we're starting with a clean slate
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 2
        $service->move_bookmark($start, 100, 3);

        // Confirm the sortorder is still correct
        $this->confirm_order($service);
    }

    protected function confirm_order($service, $comparewith = null) {
        // Get the data
        $links = $service->get_all_links();

        if ($comparewith) {
            $this->assertIdentical($links, $comparewith);
        }

        // Confirm the sortorder are correct initially
        $order = array();
        $order[0] = 0;
        foreach ($links as $link) {
            $this->assertIsA($link, 'stdClass');
            if (!isset($order[$link->categoryid])) {
                $order[$link->categoryid] = 0;
            }
            $this->assertEqual($link->sortorder,    $order[$link->categoryid]);
            $this->assertIsA($link->url,        'moodle_url');
            $order[$link->categoryid]++;
        }
    }

    protected function create_example_content() {
        $this->require_bookmarks(true);
    }

    private function require_bookmarks($includecategories = false) {
        $this->require_course();
        $service = new block_recommender_service_bookmark($this->course);

        if ($includecategories) {
            set_config('bookmark_category_1', 'Example Title 1', 'block_recommender');
            set_config('bookmark_category_2', 'Example Title 2', 'block_recommender');
            set_config('bookmark_category_2', 'Example Title 3', 'block_recommender');
        }

        foreach ($this->testdata as $data) {
            if ($includecategories) {
                $service->add_bookmark($data['url'], $data['title'], $data['category']);
            } else {
                $service->add_bookmark($data['url'], $data['title'], 0);
            }
        }
    }

}
