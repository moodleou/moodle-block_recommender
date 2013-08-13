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

require_once('blocks/recommender/locallib.php');
require_once('blocks/recommender/services/bookmark/lib.php');
require_once('blocks/recommender/services/course/lib.php');

class test_block_recommender_service extends advanced_testcase {

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
                    )
    );

    protected function setupbookmarks($course1 = null) {
        $this->resetAfterTest();

        set_config('bookmark_enabled', '1', 'block_recommender');
        if (is_null($course1)) {
            $course1 = $this->getDataGenerator()->create_course();
        }

        $service = new block_recommender_service_bookmark($course1);
        set_config('bookmark_category_1', 'Example Title 1', 'block_recommender');
        set_config('bookmark_category_2', 'Example Title 2', 'block_recommender');
        set_config('bookmark_category_2', 'Example Title 3', 'block_recommender');

        foreach ($this->testdata as $data) {
            $service->add_bookmark($data['url'], $data['title'], $data['category']);
        }
        return $service;
    }

    protected function confirm_order($service, $comparewith = null) {
        // Get the data.
        $links = $service->get_all_links();

        if ($comparewith) {
            $this->assertSame($links, $comparewith);
        }

        // Confirm the sortorder are correct initially.
        $order = array();
        $order[0] = 0;
        foreach ($links as $link) {
            $this->assertInstanceOf('stdClass', $link);
            if (!isset($order[$link->categoryid])) {
                $order[$link->categoryid] = 0;
            }
            $this->assertEquals($link->sortorder,    $order[$link->categoryid]);
            $this->assertInstanceOf('moodle_url', $link->url);
            $order[$link->categoryid]++;
        }
    }

    public function test_disabled_exception() {
        $this->resetAfterTest(true);

        set_config('bookmark_enabled', '0', 'block_recommender');
        $course1 = $this->getDataGenerator()->create_course();

        $this->setExpectedException('block_recommender_exception',
                        get_string('servicedisabled', 'block_recommender', array('servicename' => 'bookmark')));
        $service = new block_recommender_service_bookmark($course1);
    }

    public function test_constructor_with_numeric_course() {
        $this->resetAfterTest(true);

        set_config('bookmark_enabled', '1', 'block_recommender');
        $course1 = $this->getDataGenerator()->create_course();
        $this->setExpectedException('block_recommender_exception',
                        get_string('errorcallingservice', 'block_recommender', array('servicename' => 'bookmark')));
        $service = new block_recommender_service_bookmark(1);
    }

    public function test_get_block_content() {
        // Retrieve content for testing.
        $service = $this->setupbookmarks();
        $content = $service->get_block_content();

        $this->assertEquals('array', gettype($content));

        foreach ($content as $link) {
            $this->assertInstanceOf('stdClass', $link);

            // Raw and title/url are mutually exclusive.
            if (isset($link->raw)) {
                $this->assertFalse((bool)isset($link->title));
                $this->assertFalse((bool)isset($link->url));
            } else {
                $this->assertFalse((bool)isset($link->raw));
                $this->assertInstanceOf('moodle_url', $link->url);
            }
        }
    }

    public function test_bookmark_creation() {
        $this->resetAfterTest(true);
        set_config('bookmark_enabled', '1', 'block_recommender');
        $course1 = $this->getDataGenerator()->create_course();
        $service = new block_recommender_service_bookmark($course1);

        // Create the first bookmark.
        $testdata = $this->testdata[0];

        $bookmark = $service->add_bookmark($testdata['url'], $testdata['title'],
                        $testdata['category']);
        $this->assertInstanceOf('stdClass', $bookmark);

        $this->assertEquals($bookmark->url,      $testdata['url']);
        $this->assertEquals($bookmark->title,    $testdata['title']);
        $this->assertEquals($bookmark->courseid, $course1->id);
        $this->assertEquals($bookmark->categoryid, $testdata['category']);
        $this->assertEquals($bookmark->deleted,  0);
        $this->assertEquals($bookmark->service,  'bookmark');
        $this->assertEquals($bookmark->sortorder,    0);

        // Create the second bookmark.
        $testdata = $this->testdata[1];

        $bookmark = $service->add_bookmark($testdata['url'], $testdata['title'],
                        $testdata['category']);
        $this->assertInstanceOf('stdClass', $bookmark);

        $this->assertEquals($bookmark->url,      $testdata['url']);
        $this->assertEquals($bookmark->title,    $testdata['title']);
        $this->assertEquals($bookmark->courseid, $course1->id);
        $this->assertEquals($bookmark->categoryid, $testdata['category']);
        $this->assertEquals($bookmark->deleted,  0);
        $this->assertEquals($bookmark->service,  'bookmark');

        // Order should have changed.
        $this->assertEquals($bookmark->sortorder,    1);

        $links = $service->get_links();
        $link = array_pop($links);

        $this->assertInstanceOf('stdClass', $link);

        // Update the link title.
        $updatedtitle = 'Updated Title';
        $update = new stdClass();
        $update->id         = $link->id;
        $update->title      = $updatedtitle;
        $updatedbookmark    = $service->update_bookmark($update);
        $this->assertEquals($updatedbookmark->title,         $updatedtitle);

        // Check that the params haven't changed.
        $this->assertEquals($updatedbookmark->url,           $link->url);
        $this->assertEquals($updatedbookmark->categoryid,    $link->categoryid);

        // Update the link URL.
        $updatedurl = 'http://www.moodle.net';
        $update = new stdClass();
        $update->id         = $link->id;
        $update->url        = $updatedurl;
        $updatedbookmark    = $service->update_bookmark($update);
        $this->assertEquals($updatedbookmark->title,         $updatedtitle);
        $this->assertEquals($updatedbookmark->url,           $updatedurl);

        // Check that the params haven't changed.
        $this->assertEquals($updatedbookmark->categoryid,    $link->categoryid);

        // Update the link category.
        $updatedcategory = 2;
        $update = new stdClass();
        $update->id         = $link->id;
        $update->categoryid = $updatedcategory;
        $updatedbookmark    = $service->update_bookmark($update);
        $this->assertEquals($updatedbookmark->title,         $updatedtitle);
        $this->assertEquals($updatedbookmark->url,           $updatedurl);
        $this->assertEquals($updatedbookmark->categoryid,    $updatedcategory);
    }

    public function test_bookmark_delete() {
        $this->resetAfterTest(true);
        set_config('bookmark_enabled', '1', 'block_recommender');
        $course1 = $this->getDataGenerator()->create_course();
        $service = new block_recommender_service_bookmark($course1);

        $links = $service->get_links();
        $this->assertEquals(count($links), 0);

        $data = $this->testdata[0];

        // Add a test bookmark.
        $link = $service->add_bookmark($data['url'], $data['title'], $data['category']);

        // It should be the only link returned by get_links.
        $links = $service->get_links();
        $this->assertEquals(count($links), 1);

        // This bookmark should exist and should not be deleted.
        $result = $service->get_bookmark($link->id);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals($result->deleted, 0);

        // Test deletion.
        $service->delete_bookmark($link);

        // Confirm that the bookmark has been marked as deleted.
        $result = $service->get_bookmark($link->id);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals($result->deleted, 1);

        // And that get_links no longer returns the link.
        $links = $service->get_links();
        $this->assertEquals(count($links), 0);
    }

    public function test_get_categories() {
        $service = $this->setupbookmarks();
        $categories = $service->get_categories();

        $this->assertEquals('array', gettype($categories));
        foreach ($categories as $catid => $category) {
            $this->assertInstanceOf('integer', $catid);
            $this->assertInstanceOf('String', $category);
        }
    }

    public function test_get_all_categories() {
        $service = $this->setupbookmarks();
        $categories = $service->get_all_categories();

        $this->assertEquals('array', gettype($categories));
        $this->assertEquals(count($categories), 4);
        foreach ($categories as $catid => $category) {
            $this->assertInternalType('integer', $catid);
            $this->assertInstanceOf('stdClass', $category);
            $this->assertTrue((bool)isset($category->title));
            $this->assertTrue((bool)isset($category->defined));
        }
    }

    public function test_search_bookmarks() {
        $service = $this->setupbookmarks();

        // Our initial search terms.
        $find   = new stdClass();
        $ignore = new stdClass();

        // Searching with no find and/or ignore should return all results.
        $results = $service->search_bookmarks($find, $ignore);
        $this->assertEquals('array', gettype($results));
        $this->assertEquals(count($results), count($this->testdata));

        // Search for all those in category 1.
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

        $this->assertEquals('array', gettype($results));
        $this->assertEquals(count($results), $shouldfind);

        // And test using the customfield name instead of the friendlyname.
        unset($find->category);
        $find->customfield1 = 1;
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertEquals('array', gettype($results));
        $this->assertEquals(count($results), $shouldfind);

        // And ignore one of those.
        $ignore->title = $foundcat['title'];
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertEquals('array', gettype($results));
        $this->assertEquals(count($results), $shouldfind - 1);

        // And test using the customfield name instead of the friendlyname.
        unset($ignore->title);
        $ignore->customfield2 = $foundcat['title'];
        $results = $service->search_bookmarks($find, $ignore);

        $this->assertEquals('array', gettype($results));
        $this->assertEquals(count($results), $shouldfind - 1);
    }

    public function test_bookmark_creation_category() {
        $service = $this->setupbookmarks();
        $links = $service->get_links();

        // Check the sortorder of bookmarks.
        $this->confirm_order($service);
    }

    public function test_get_block_content_with_displaytype() {
        // Call parent::test_get_block_content again adjusting the displaytype to cover all cases.
        set_config('bookmark_displaytype', '1', 'block_recommender');
        $this->test_get_block_content();
    }

    public function test_get_all_links() {
        $service = $this->setupbookmarks();
        $links = $service->get_all_links();
        $this->assertEquals('array', gettype($links));

        // Check the sortorder of bookmarks.
        $this->confirm_order($service);
    }

    public function test_move_order_up() {
        $service = $this->setupbookmarks();

        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 1.
        $service->move_bookmark($start, 1, 1);

        // Confirm the order is still correct.
        $this->confirm_order($service);
    }

    public function test_move_order_down() {
        $service = $this->setupbookmarks();
        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 1.
        $service->move_bookmark($start, 6, 1);

        // Confirm the sortorder is still correct.
        $this->confirm_order($service);
    }

    public function test_move_order_above_max() {
        $service = $this->setupbookmarks();

        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }
        // And move it to position 1.
        $service->move_bookmark($start, 100, 1);

        // Confirm the sortorder is still correct.
        $this->confirm_order($service);
    }

    public function test_move_order_up_new_category() {
        $service = $this->setupbookmarks();

        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }
        // And move it to position 1 on category 2.
        $service->move_bookmark($start, 1, 2);

        // Confirm the sortorder is still correct.
        $this->confirm_order($service);
    }

    public function test_move_order_down_new_category() {
        $service = $this->setupbookmarks();

        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the first link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 1) {
                $start = $link;
            }
        }
        // And move it to position 3 on category 2.
        $service->move_bookmark($start, 1, 2);

        // Confirm the sortorder is still correct.
        $this->confirm_order($service);
    }

    public function test_move_order_new_category_above_max() {
        $service = $this->setupbookmarks();

        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 2.
        $service->move_bookmark($start, 100, 2);

        // Confirm the sortorder is still correct.
        $this->confirm_order($service);
    }

    public function test_get_top_links() {
        $course = $this->getDataGenerator()->create_course();
        $service = $this->setupbookmarks($course);
        $links = $service->get_links();

        $testdata = $this->testdata[0];

        $this->assertEquals('array', gettype($links));

        $order = 0;
        foreach ($links as $link) {
            $this->assertEquals($link->courseid, $course->id);
            $this->assertEquals($link->deleted,  0);
            $this->assertEquals($link->service,  'bookmark');
            $this->assertEquals($link->sortorder,    $order);
        }

        // Retrieve overall top for one category.
        $links = $service->get_links(1);
        $testdata = $this->testdata[0];
        $this->assertEquals('array', gettype($links));

        $order = 0;
        foreach ($links as $link) {
            $this->assertEquals($link->courseid, $course->id);
            $this->assertEquals($link->deleted,  0);
            $this->assertEquals($link->service,  'bookmark');
            $this->assertEquals($link->sortorder,    $order);
        }
    }

    public function test_move_order_empty_category() {
        $service = $this->setupbookmarks();

        // Confirm that we're starting with a clean slate.
        $this->confirm_order($service);

        $categorylinks = $service->get_all_links(1);

        // Take the fourth link.
        foreach ($categorylinks as $link) {
            if ($link->sortorder == 4) {
                $start = $link;
            }
        }

        // And move it to position 2.
        $service->move_bookmark($start, 100, 3);

        // Confirm the sortorder is still correct.
        $this->confirm_order($service);
    }

    // Course service related tests.

    public function test_get_course_url() {
        $this->resetAfterTest(true);
        $course = new stdclass();
        $course->shortname  = 'testcourse-one';
        $course1 = $this->getDataGenerator()->create_course($course);
        $service = new block_recommender_service_course($course1);

        $url = $service->get_course_url($course1);
        $checkagainst = new moodle_url('/course/view.php', array('id' => $course1->id));
        $this->assertSame($url->out(), $checkagainst->out());
    }

    public function test_get_course_url_with_regex() {
        $this->resetAfterTest(true);
        $course = new stdclass();
        $course->shortname  = 'testcourse-one';
        $course1 = $this->getDataGenerator()->create_course($course);

        set_config('course_course_shortname_pattern', '^(.*)-.*$', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($course1);

        $url = $service->get_course_url($course1);
        $this->assertSame($url->out(), 'http://www.example.org/testcourse');
    }

    public function test_get_course_url_with_complete_regex() {
        $this->resetAfterTest(true);
        $course = new stdclass();
        $course->shortname  = 'testcourse-one';
        $course1 = $this->getDataGenerator()->create_course($course);

        set_config('course_course_shortname_pattern', '/^(.*)-.*$/', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($course1);

        $url = $service->get_course_url($course1);
        $this->assertSame($url->out(), 'http://www.example.org/testcourse');
    }

    public function test_get_course_url_with_unmatched_regex() {
        $this->resetAfterTest(true);
        $course = new stdclass();
        $course->shortname  = 'testcourse-one';
        $course1 = $this->getDataGenerator()->create_course($course);

        set_config('course_course_shortname_pattern', '/^DONTMATCHME$/', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($course1);

        $url = $service->get_course_url($course1);
        $checkagainst = new moodle_url('/course/view.php', array('id' => $course1->id));
        $this->assertSame($url->out(), $checkagainst->out());
    }

    public function test_get_course_url_with_unmatched_shortname() {
        $this->resetAfterTest(true);
        $course = new stdclass();
        $course->shortname  = 'somethingdifferent';
        $course3 = $this->getDataGenerator()->create_course($course);

        set_config('course_course_shortname_pattern', '/^(.*)-.*$/', 'block_recommender');
        set_config('course_course_url', 'http://www.example.org/$1', 'block_recommender');

        $service = new block_recommender_service_course($course3);

        $url = $service->get_course_url($course3);
        $checkagainst = new moodle_url('/course/view.php', array('id' => $course3->id));
        $this->assertSame($url->out(), $checkagainst->out());
    }
}