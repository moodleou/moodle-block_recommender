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
 * Recommender block OER service libraries
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * block_recommender_service_oer renderer
 *
 * Class for rendering components of the oer recommender service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_recommender_service_oer_renderer extends plugin_renderer_base {

    /**
     * Render the more page links
     *
     * @param  mixed   $links      The array of objects with links data
     * @return  string      The rendered content
     */
    public function display_more_details($links) {
        global $PAGE;

        $output = '';

        // add disclaimer
        $disclaimer = html_writer::tag('p', get_string('oer_disclaimer', 'block_recommender'),
            array('class' => 'notifytiny'));

        if (empty($links)) {
            $output .= html_writer::tag('p', get_string('oer_noresults', 'block_recommender'));
            $output .= $disclaimer;
            return $output;
        }

        // generate the output
        foreach ($links as $link) {
            $title = $link->title;
            if ($link->collection) {
                $title .= ' ('.$link->collection.')';
            }
            $content = html_writer::tag('p', html_writer::link($link->url, $title));
            $content .= html_writer::tag('p', $link->description);
            $output .= html_writer::tag('div', $content);
        }
        $output .= $disclaimer;
        return $output;
    }

}
