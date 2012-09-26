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
 * Recommender block renderer
 *
 * Class for rendering various block_recommender objects
 *
 * @package    blocks
 * @subpackage recommender
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_recommender_renderer extends plugin_renderer_base {

    /**
     * Render the block content
     *
     * @param   mixed   $services array of objects containing information on services
     * @return  string      The rendered content
     */
    public function block_display($services) {
        $content = $this->navigation_tree($services, array('class'=>'block_tree list'));
        if (!empty($content)) {
            $content = $this->output->box($content, 'block_tree_box');
        }
        return $content;
    }

    /**
     * Render the main navigation tree
     *
     * @param   mixed   $services array of objects containing information on services
     * @param   array   $attrs array of attributes to apply to ul tag
     * @return  string      The rendered content
     */
    protected function navigation_tree($services, $attrs=array()) {

        // exit if empty, we don't want an empty ul element
        if (empty($services)) {
            return '';
        }

        // non-js fallback: determine the requested service
        $requestedservice = optional_param('block_recommender_display', null, PARAM_ALPHAEXT);

        // prepare the array of nested li elements
        $lis = array();
        foreach ($services as $service) {
            $blockcontent = '';
            $divclasses = array('tree_item', 'branch');

            // non-js fallback: get the service block content
            if ($service->name == $requestedservice) {
                $blockcontent = $service->object->get_block_content();
                if (empty($blockcontent)) {
                    $divclasses = array('tree_item', 'emptybranch');
                }
            }

            $title = get_string($service->name.'_servicetitle', 'block_recommender');
            //add tab support to span but still maintain character stream sequence.
            $content = html_writer::tag('span', $title, array('tabindex' => '0'));
            // non-js fallback: create the link to expand the branch (but not for expanded branch)
            $linkparam = array();
            if (empty($blockcontent)) {
                $linkparam = array('block_recommender_display' => $service->name);
            }
            $content = html_writer::link($this->page->url->out(false, $linkparam), $content);
            $pattr = array('class'=>join(' ', $divclasses), 'id'=>'recommender_'.$service->name);
            $content = html_writer::tag('p', $content, $pattr);

            // this applies to the li item which contains all child lists too
            $liclasses = array('type_service', 'depth_2', 'contains_branch');

            // non-js fallback: open this branch
            if (!empty($blockcontent)) {
                $content .= $this->navigation_service_tree($blockcontent);
            } else {
                $liclasses[] = 'collapsed';
            }

            $liattr = array('class'=>join(' ', $liclasses));
            $content = html_writer::tag('li', $content, $liattr);
            $lis[] = $content;
        }

        if (count($lis)) {
            return html_writer::tag('ul', implode("\n", $lis), $attrs);
        } else {
            return '';
        }
    }

    /**
     * Render the tree items for the service
     *
     * @param   mixed   $blockcontent array of objects containing block content
     * @return  string      The rendered content
     */
    public function navigation_service_tree($blockcontent) {
        // exit if empty, we don't want an empty ul element
        if (empty($blockcontent) || !is_array($blockcontent)) {
            return '';
        }

        // prepare the array of nested li elements
        $lis = array();
        foreach ($blockcontent as $item) {
            // format the content
            if (empty($item->raw)) {
                $content = $item->title;
                // add icon
                if (isset($item->module)) {
                    $alt = get_string('modulename', $item->module);
                    $item->icon = new pix_icon('icon', $alt, $item->module);
                    $icon = html_writer::empty_tag('img', array('src' => $item->icon_url,
                            'class' => 'navicon', 'alt' => $alt));
                } else {
                    $alt = get_string('pluginname', 'url');
                    $item->icon = new pix_icon('i/navigationitem', $alt);
                    $item->icon->attributes['class'] = 'navicon';
                    $icon = $this->output->render($item->icon);
                }
                $content = $icon.$content;

                $attributes = array('title'=>$alt);
                //add tab support to span but still maintain character stream sequence:
                $attributes['tabindex'] = '0';
                $content = html_writer::link($item->url, $content, $attributes);
            } else {
                $content = $item->raw;
            }

            // add "paragraph" and wrap in "list item"
            $liclasses = array('type_service_itmes', 'depth_3', 'collapsed', 'item_with_icon');
            $liattr = array('class'=>join(' ', $liclasses));
            $divclasses = array('tree_item', 'leaf');
            $pattr = array('class'=>join(' ', $divclasses));
            $content = html_writer::tag('p', $content, $pattr);
            $content = html_writer::tag('li', $content, $liattr);
            $lis[] = $content;
        }

        if (count($lis)) {
            return html_writer::tag('ul', implode("\n", $lis));
        } else {
            return '';
        }
    }
}
