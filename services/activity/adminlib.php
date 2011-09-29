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
 * Recommender block Popular Activities Admin Setting  Itms
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Custom admin setting icon to enable the configuration of modules for the
 * activity module
 */

class admin_setting_recommender_activity extends admin_setting {
    public function get_setting() {
        $enabled = $this->config_read($this->name.'_enabled');
        $view = $this->config_read($this->name.'_view');
        $participate = $this->config_read($this->name.'_participate');

        if (is_null($enabled) or is_null($view) or is_null($participate)) {
            return null;
        }

        return array('enabled' => $enabled, 'view' => $view, 'participate' => $participate);
    }

    public function write_setting($data) {

        $enabled = empty($data['enabled']) ? 0 : 1;
        $view = $data['view'];
        $participate = $data['participate'];

        $enabledupdated = $this->config_write($this->name.'_enabled', $enabled);
        $viewupdated = $this->config_write($this->name.'_view', $view);
        $participateupdated = $this->config_write($this->name.'_participate', $participate);

        if ($enabledupdated and $viewupdated and $participateupdated) {
            return '';
        } else {
            return get_string('errorsetting', 'admin');
        }
    }

    public function get_defaultsetting() {
        return array('enabled' => 1, 'view' => '', 'participate' => '');
    }

    public function output_html($data, $query='') {
        $defaults = $this->get_defaultsetting();

        if (isset($data['enabled'])) {
            $enabled = !empty($data['enabled']);
        } else {
            $enabled = $defaults['enabled'];
        }
        $view = $data['view'];
        $participate = $data['participate'];

        $return = html_writer::start_tag('div', array(
            'class' => 'form-group',
            'id'  => $this->get_id()
        ));

        $rows = '';
        $cells = '';
        $cells.= html_writer::tag('td',
            html_writer::checkbox($this->get_full_name().'[enabled]', "1", $enabled, '',
                array('class' => 'form-checkbox', 'id' => $this->get_id().'enabled')
            )
        );
        $cells.= html_writer::tag('td',
            html_writer::empty_tag('input', array(
                'size' => '30',
                'id' => $this->get_id().'view',
                'name' => $this->get_full_name().'[view]',
                'value' => s($view)
                )
            )
        );
        $cells.= html_writer::tag('td',
            html_writer::empty_tag('input', array(
                'size' => '30',
                'id' => $this->get_id().'participate',
                'name' => $this->get_full_name().'[participate]',
                'value' => s($participate)
                )
            )
        );
        $rows.= html_writer::tag('tr', $cells);

        $cells = '';
        $cells.= html_writer::tag('td', '');
        $cells.= html_writer::tag('td',
            html_writer::label(get_string('activity_viewactions', 'block_recommender'),
                               $this->get_id().'view')
        );
        $cells.= html_writer::tag('td',
            html_writer::label(
                get_string('activity_participateactions', 'block_recommender'),
               $this->get_id().'participate')
        );
        $rows.= html_writer::tag('tr', $cells);

        $return.= html_writer::tag('table', $rows);
        $return.= html_writer::end_tag('div');

        return format_admin_setting($this, $this->visiblename, $return,
                    $this->description, true, '', null, $query);
    }
}