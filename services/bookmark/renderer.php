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
 * Recommender block bookmark service libraries
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * block_recommender_service_bookmark renderer
 *
 * Class for rendering components of the bookmark recommender service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_recommender_service_bookmark_renderer extends plugin_renderer_base {

    private function display_move_target($id, $courseid, $categoryid = '0', $moveafter = null) {
        static $movelink = array();

        if (!isset($movelink['courseid'])) {
            $movelink['courseid']   = $courseid;
            $movelink['sesskey']    = sesskey();
        }

        $movelink['moveid']     = $id;
        $movelink['categoryid'] = $categoryid;

        if ($moveafter) {
            $movelink['moveafter'] = $moveafter;
        } else {
            unset($movelink['moveafter']);
        }

        $url  = new moodle_url('/blocks/recommender/services/bookmark/view.php', $movelink);
        $movetarget = html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('movehere'),
                    'class' => 'movetarget',
                    'title' => get_string('bookmark_movehere', 'block_recommender'),
                    'alt'   => get_string('bookmark_movehere', 'block_recommender')
                    )));
        return $movetarget;
    }

    public function display_more_details($service, $moving = null) {
        global $PAGE;

        $canedit = $service->has_service_capability('add');

        $display = '';

        $display .= html_writer::start_tag('div',
            array('class' => 'block_recommender_service_bookmark_area'));

        $seencategory = false;
        foreach ($service->get_all_categories() as $categoryid => $category) {
            // Add the title for this one
            $display .= html_writer::tag('h3', $category->title);

            $links = $service->get_all_links($categoryid);

            if (count($links)) {
                // Add the start tag
                $display .= html_writer::start_tag('ul', array(
                    'class' => 'block_recommender_service_bookmark_list',
                    'id' => 'bookmark_categoryid-'.$categoryid));
            }

            if ($moving && $categoryid == null) {
                $display .= $this->display_move_target($moving->moveid, $moving->courseid);
            } else if ($moving) {
                $display .= $this->display_move_target($moving->moveid, $moving->courseid,
                    $categoryid);
            }

            foreach ($links as $link) {
                // Calculate Category Information
                if ($moving && $link->id == $moving->moveid) {
                    continue;
                }

                $linkproperties = array(
                    'id'        => $link->id,
                    'courseid'  => $link->courseid,
                );

                // The actual link
                $target = html_writer::link(new moodle_url($link->url), $link->title);

                if ($canedit) {
                    // Move Link
                    $movelink               = $linkproperties;
                    $movelink['moveid']     = $link->id;
                    $url = new moodle_url('/blocks/recommender/services/bookmark/view.php',
                        $movelink);
                    $move = html_writer::link($url,
                        html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('t/move'),
                            'class' => 'iconsmall',
                            'title' => get_string('bookmark_move', 'block_recommender'),
                            'alt'   => get_string('bookmark_move', 'block_recommender')
                            )), array('class' => 'bookmark_handle'));

                    $url = new moodle_url('/blocks/recommender/services/bookmark/delete.php',
                        $linkproperties);
                    $delete = html_writer::link($url,
                            html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('t/delete'),
                            'class' => 'iconsmall',
                            'title' => get_string('bookmark_delete', 'block_recommender'),
                            'alt'   => get_string('bookmark_delete', 'block_recommender')
                            )));

                    $url = new moodle_url('/blocks/recommender/services/bookmark/add.php',
                        $linkproperties);
                    $edit = html_writer::link($url,
                            html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('t/edit'),
                            'class' => 'iconsmall',
                            'title' => get_string('bookmark_edit', 'block_recommender'),
                            'alt'   => get_string('bookmark_edit', 'block_recommender')
                            )));

                    $li = html_writer::tag('li', $move . $delete . $edit . $target, array(
                        'class' => 'bookmark_item',
                        'id' => 'bookmark_itemid-'.$link->id));
                } else {
                    $li = html_writer::tag('li', $target);
                }

                $display .= $li;
                if ($moving) {
                    $display .= $this->display_move_target($moving->moveid, $moving->courseid,
                        $categoryid, $link->id);
                }
            }
            if (count($links)) {
                $display .= html_writer::end_tag('ul');
            }
        }

        $display .= html_writer::end_tag('div');

        if ($canedit) {
            $url = new moodle_url('/blocks/recommender/services/bookmark/add.php',
                array('courseid' => $PAGE->course->id));

            $display .= html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/add'),
                    'class' => 'iconsmall',
                    'title' => get_string('bookmark_create_alt', 'block_recommender'),
                    'alt'   => get_string('bookmark_create_alt', 'block_recommender')
                    )));
            $create = html_writer::link($url, get_string('bookmark_create', 'block_recommender'));
            $display .= get_string('bookmark_createbookmark_text', 'block_recommender', $create);
        }

        return $display;
    }

}
