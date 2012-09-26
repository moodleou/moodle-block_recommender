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
 * Recommender block
 *
 * @package    blocks
 * @subpackage recommender
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot.'/blocks/recommender/lib.php');

class block_recommender extends block_base {
    /**
     * Block initialisation
     *
     * @return  void
     */
    public function init() {
        global $OUTPUT;

        $this->title = get_string('recommendertitle', 'block_recommender');
        $this->title.= ' ' . $OUTPUT->help_icon('recommendertitle', 'block_recommender');
    }

    /**
     * Retrieve the contents of the block
     *
     * @return   StdClass    containing the block's content
     */
    public function get_content() {

        // Save loops if we have the content ready
        if ($this->content !== null) {
            return $this->content;
        }
        $services = get_recommender_services($this->page->course);

        if (empty($services) && $this->page->user_is_editing()) {
            $url = new moodle_url('/admin/settings.php',
                array('section' => 'blocksettingrecommender'));
            $url = html_writer::link($url, get_string('blocksettings', 'block'));
            $this->content->text = get_string('notifynoenabledservices', 'block_recommender', $url);
            return $this->content;
        }

        $renderer = $this->page->get_renderer('block_recommender');

        $this->content         = new stdClass;
        $this->content->text   = $renderer->block_display($services);
        return $this->content;
    }

    /**
     * Whether to allow multiple instance of the block
     *
     * @return   boolean     Whether to allow multiple instance of the block
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Whether the block has configuration
     *
     * @return   boolean     Whether the block has configuration
     */
    public function has_config() {
        return true;
    }

    /**
     * Include required javascript
     *
     * @return   void
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        $arguments = array(
            'instanceid'     => $this->instance->id,
            'courseid'       => $this->page->course->id,
            'candock'        => $this->instance_can_be_docked(),
            'expansions'     => array_keys(get_recommender_services($this->page->course)),
        );
        $this->page->requires->string_for_js('viewallcourses', 'moodle');
        $this->page->requires->yui_module(array('moodle-block_recommender-navigation'),
            'M.block_recommender.init_add_tree', array($arguments));
    }
}
