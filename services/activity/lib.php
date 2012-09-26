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
 * Recommender block Popular Activities service
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../lib.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

/**
 * Define the number of courses to show as standard when viewing more information
 */
define('SERVICE_POPULARACTIVITIES_MORE_LIMIT', 10);

/**
 * Define the number of courses to show in the block
 */
define('SERVICE_POPULARACTIVITIES_BLOCK_LIMIT', 3);

class block_recommender_service_activity extends block_recommender_service {

    /**
     * Retrieve the block content for this service
     *
     * @return  array   $links  The block content for this service
     */
    public function get_service_content() {
        return $this->get_popular_activities();
    }

    /**
     * Builds a popular activities list
     *
     * @param int    $limit      The maximum number of activities to retrieve
     * @param int    $since      When the activies should be displayed from (in seconds since epoch)
     * @param bool   $activity
     * @param string $sortby
     *
     * @return  array   $links  List of links for popular activities
     */
    public function get_popular_activities($limit = SERVICE_POPULARACTIVITIES_BLOCK_LIMIT,
                                           $since = null, $activity = null, $sortby = null) {
        global $USER;
        $info = get_fast_modinfo($this->course);
        $cms = $info->get_cms();

        // Only return visible cms
        $names = array(); //a list of module instance names for use if sorting by module
        foreach ($cms as $cm) {
            if (!$cm->uservisible) {
                unset($cms[$cm->id]);
            }
            if ($activity !== null) {
                $archetype = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE,
                    MOD_ARCHETYPE_OTHER);

                if (($activity == MOD_ARCHETYPE_RESOURCE && $archetype != MOD_ARCHETYPE_RESOURCE) ||
                    ($activity != MOD_ARCHETYPE_RESOURCE && $archetype == MOD_ARCHETYPE_RESOURCE)) {
                    unset($cms[$cm->id]);
                }
            }
            if (isset($cms[$cm->id])) {
                array_push($names, strtolower($cm->name));
            }
        }

        if ($since === null) {
            $since = $this->get_last_access();
        }

        // Limit cms if ordering by module name
        if (!empty($sortby) && strpos($sortby, 'module') !== false && count($names) > 1) {
            if (strpos($sortby, 'DESC')) {
                $sortorder = 'DESC';
                rsort($names, SORT_STRING);
            } else {
                $sortorder = 'ASC';
                sort($names, SORT_STRING);
            }
            $names = array_slice($names, 0, $limit, true);
            foreach ($cms as $cm) {
                if (!in_array(strtolower($cm->name), $names)) {
                    unset($cms[$cm->id]);
                }
            }
            // Adjust the sortby to avoid having to reorder by module instance name after query
            $sortby = $this->get_special_sortby($names, $cms, $sortorder);
        }

        $topmods = $this->build_sql($cms, $since, $limit, $sortby);

        $activitylinks = array();
        foreach ($topmods as $mod) {
            $link =  new stdClass;
            $link->title = $cms[$mod->cmid]->name;
            $link->url   = $cms[$mod->cmid]->get_url();
            $link->icon_url = $cms[$mod->cmid]->get_icon_url();
            $link->module = $mod->module;
            $link->views = $mod->views;
            $link->participations = $mod->participations;
            $activitylinks[] = $link;
        }

        return $activitylinks;
    }

    /**
     * Create a special sortby for returning results in module instance name order
     * @param array $names
     * @param object $cms
     * @param string $sortorder
     * @return string $orderby a snippet of sql for the order by clause
     */
    private function get_special_sortby($names, $cms, $sortorder) {
        $orderby = 'case l.cmid ';
        foreach ($cms as $cm) {
            $orderby .= ' when ' . $cm->id . ' then ' . array_search(strtolower($cm->name), $names);
        }
        $orderby .= 'else null end ' . $sortorder;
        return $orderby;
    }

    /**
     * Builds SQL to get the list of popular acitivties
     *
     * @param array $cms an array of course_modules to query the log table for
     * @param int $since The earliest time (in seconds since epoch) to return activity from
     * @param int $limit How many items to return
     * @param String what to order the list on
     *
     * @return object recordset of popular activities
     */
    private function build_sql($cms, $since = null, $limit = null, $order = null) {
        global $DB;

        $params = array();
        $params['courseid'] = $this->course->id;

        $vtests = array();
        $ptests = array();
        $cmids = array();

        $i = 0;
        foreach ($cms as $cm) {
            if (!$set = $this->get_mod_settings($cm->modname)) {
                continue;
            }

            // Build up a list of all view actions including the default (view)
            // and those chosen by the user
            $view_actions = array('view');
            if (isset($set['view']) && !empty($set['view'])) {
                $view_actions = array_merge($view_actions, explode(',', $set['view']));
                $view_actions = array_map('trim', $view_actions);
            }

            list($insql, $viewparams) = $DB->get_in_or_equal($view_actions, SQL_PARAMS_NAMED,
                'lvaction' . $i);

            $vtests[] = '(lv.cmid = :lvcmid' . $i . ' AND lv.action ' . $insql . ')';
            $params['lvcmid' . $i] = $cm->id;
            $cmids[$cm->id] = $cm->id;
            $params = array_merge($params, $viewparams);

            // Build up the participant list. There is no default for this so only use
            // those supplied on the config page
            if (isset($set['participate']) && !empty($set['participate'])) {
                $participateactions = explode(',', $set['participate']);
                $participateactions = array_map('trim', $participateactions);
                list($insql, $participateparams) = $DB->get_in_or_equal($participateactions,
                                                SQL_PARAMS_NAMED, 'lpaction' . $i);
                $ptests[] = '(lp.cmid = :lpcmid' . $i . ' AND lp.action ' . $insql . ')';
                $params['lpcmid' . $i] = $cm->id;
                $cmids[$cm->id] = $cm->id;
                $params = array_merge($params, $participateparams);
            }

            $i++;
        }

        if (!count($vtests) && !count($ptests)) {
            // No modules are enabled - return early
            return array();
        }

        // Calculate the joins and notnulls
        $joins = '';
        $notnull = '';

        // Calculate the SQL and $select statements for the view tests
        // We always have view tests
        $selects[] = 'COUNT(lv.id) AS views';
        $joins .= '
        LEFT JOIN {log} lv
            ON lv.id = l.id
            AND (';
        $joins .= implode(' OR ', $vtests);
        $joins .= ')';

        // Calculate the SQL and $select statements for the participate tests
        // N.B. We only have participate tests if these have been defined in config
        if (count($ptests)) {
            $selects[] = 'COUNT(lp.id) AS participations';
            $joins .= '
            LEFT JOIN {log} lp
                ON lp.id = l.id
                AND (';
            $joins .= implode(' OR ', $ptests);
            $joins .= ')';
            $lpcount = '+ COUNT(lp.id)';
            $notnull = ' OR lp.cmid IS NOT NULL';
        } else {
            $lpcount = '';
            $selects[] = '0 AS participations';
        }

        // Calculate the time limit
        if ($since !== null) {
            $withinperiodsql = 'AND l.time > :since';
            $params['since'] = $since;
        } else {
            $withinperiodsql = '';
        }

        // Which cmids are being tested
        list ($insql, $cmids) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');
        $params = array_merge($params, $cmids);

        if ($order) {
            $orderby = $order;
        } else {
            $orderby = '(COUNT(lv.id)' . $lpcount . ') DESC';
        }

        /**
         * Build the actual SQL
         *
         * This query selects all log entries within the specified time period and then joins
         * those log entries again for any view, and participate checks. These LEFT JOINs are
         * used to count the number of view and participate counts within the time period.
         *
         * Only moodle logs which have a known view, or participation action are included.
         *
         * We must include the limit on l.cmid because of the use of LEFT JOINs -- otherwise
         * any coursemodule in the  logs will be shown. A LEFT JOIN is required because we
         * *might* have contributions without views, or vice-versa.
         *
         * We order by the sum of the two counts.
         */
        $sql = '
            SELECT
                l.cmid AS cmid,
                l.module AS module,
                ' .  implode(', ', $selects) . '
                FROM {log} l
                ' . $joins . '
            WHERE
                l.course = :courseid
            ' . $withinperiodsql . '
            AND l.cmid ' . $insql . '
            AND (lv.cmid IS NOT NULL ' . $notnull . ')
            GROUP BY l.cmid, l.module
            ORDER BY ' . $orderby;

        if ($limit) {
            return $DB->get_recordset_sql($sql, $params, 0, $limit);
        } else {
            return $DB->get_recordset_sql($sql, $params);
        }
    }

    /**
     * Return the module specific settings for determining action
     * upon.
     *
     * @param string @modulename Name of the module to retrieve settings for
     *
     * @param return mixed index aray where view contains view actions,
     * 'participate' returns partcipate actions. Or false if the module is
     * disabled
     */
    private function get_mod_settings($modulename) {
        $key = $modulename . '_';

        if (!$this->config->{$key.'enabled'}) {
            return false;
        }

        return array('view' => $this->config->{$key.'view'},
                     'participate' => $this->config->{$key . 'participate'}
                 );
    }

    /**
     * Get the timestamp for the most recent access of this course
     *
     * If the most recent access was more than one month ago, return a
     * timestamp for one month ago instead.
     *
     * @return  int A timestamp representing the last access
     */
    private function get_last_access() {
        global $USER;

        $lastmonth = strtotime('-1 month');
        if (isset($USER->lastcourseaccess[$this->course->id])) {
            $lastvisit = $USER->lastcourseaccess[$this->course->id];
            if ($lastvisit > $lastmonth) {
                return $lastvisit;
            }
        }
        return $lastmonth;
    }

}

class recommender_flexible_table extends flexible_table {
    public function wrap_html_start() {
        global $OUTPUT;
        $title = get_string('activity_sort', 'block_recommender');
        echo html_writer::tag('h2', $title, array('class'=>'main headerwithhelp'));
    }
}
