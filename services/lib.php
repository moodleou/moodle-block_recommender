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
 * Recommender block service definition
 *
 * @package     block
 * @subpackage  recommender
 * @copyright   2011 Lancaster University Network Services Limited
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The parent class for all services
 */
abstract class block_recommender_service {

    /** @var object  The instance configuration */
    protected $config = null;
    /** @var object  The course object */
    protected $course = null;
    /** @var boolean  whether add link is displayed */
    public $displayaddlink = false;
    /** @var boolean  whether more link is displayed */
    public $displaymorelink = false;

    /**
     * Class constructor
     *
     * @param   object  $course     The course object
     * @param   object  $config     Optional config object passed in to reduce get_config calls
     * @return  void
     */
    public function __construct($course, $pluginconfig = null) {
        global $CFG;

        $servicename = $this->get_service_name();

        if (!is_object($course)) {
            throw new block_recommender_exception(
                get_string('errorcallingservice', 'block_recommender',
                array('servicename'=>$servicename))
            );
        }
        $this->course = $course;

        // Populate service configuration.
        if ($pluginconfig === null) {
            $pluginconfig = get_config('block_recommender');
        }

        $this->config = new block_recommender_service_config($servicename, $pluginconfig);

        // Check that this service is enabled.
        if ($this->config->enabled != 1) {
            throw new block_recommender_exception(
                get_string('servicedisabled', 'block_recommender',
                array('servicename'=>$servicename))
            );
        }

        // Should we display an add link?
        $pathtoadd = $CFG->dirroot.'/blocks/recommender/services/'.$servicename.'/add.php';
        if (is_readable($pathtoadd)) {
            $this->displayaddlink = true;
        }

        // Should we display more link?
        $pathtoview = $CFG->dirroot.'/blocks/recommender/services/'.$servicename.'/view.php';
        if (is_readable($pathtoview)) {
            $this->displaymorelink = true;
        }
    }
    /**
     * Retrieve the block content for the service
     *
     * @return  array               The block content for this service
     */
    public function get_block_content() {
        global $CFG;

        if (!$this->has_service_capability('view')) {
            return false;
        }

        $links = $this->get_service_content();

        if ($this->displayaddlink
            && $this->has_service_capability('add')) {
            $more = new stdClass();
            $more->title = get_string('add', 'block_recommender');
            $more->url = new moodle_url(
                '/blocks/recommender/services/'.$this->get_service_name().'/add.php',
                array('courseid' => $this->course->id));
            $links[] = $more;
        }

        if ($this->displaymorelink && $this->has_service_capability('more')) {
            $more = new stdClass();
            $more->title = get_string($this->get_service_name().'_more', 'block_recommender');
            $more->url = new moodle_url(
                '/blocks/recommender/services/'.$this->get_service_name().'/view.php',
                array('courseid' => $this->course->id));
            $links[] = $more;
        }

        return $links;
    }

    /**
     * Return the name of this service
     *
     * @return  String  The name of the service
     */
    public function get_service_name() {
        $words = explode('_', get_class($this));
        return $words[3];
    }

    /**
     * Check if service has any content to display. If false, the service item
     * is not diplayed in the block. Should return true for most setvice by default, the
     * actual check will be done in ajax request. However, if you want to ensure
     * that there is a content beforehand, declare this method in service class and
     * perform the required check. Be careful, it might have some serious
     * performance implications.
     *
     * @return  boolean true if the service has content to display
     */
    public function has_content() {
        return true;
    }

    /**
     * Make sure all services have get_service_content()
     *
     * @return  array               The block content for this service
     */
    public abstract function get_service_content();


    /**
     * Checks capability for current service at curent (course) context
     *
     * @param string $capname the name of the capability to check
     *
     * @return  boolean true if the user has this capability or the capability
     * doesn't exist.
     */
    public function has_service_capability($capname) {
        $context = context_course::instance($this->course->id);

        $capabilityname = 'block/recommender:'.$capname.$this->get_service_name();

        if (get_capability_info($capabilityname)) {
            return has_capability($capabilityname, $context);
        } else {
            return true;
        }
    }
}

class block_recommender_service_config {

    /** @var mixed   The configuration hash-like object */
    protected $config = null;

    public function __construct($servicename, $config) {

        $this->config = new stdClass();
        foreach ((array)$config as $key => $value) {
            if (preg_match("/^$servicename\_/", $key)) {
                $key = preg_replace("/^$servicename\_/", '', $key);
                $this->config->$key = $value;
            }
        }
    }

    /**
     * Return the configuration for the specified key in the current service
     *
     * @param   String  $key        The key for this configuration item
     * @return  String              The value of the configuration key
     */
    public function __get($key) {
        return isset($this->config->$key) ? $this->config->$key : null;
    }
}

/*
 * Exceptions
 */

/**
 * Base service exception
 */
class block_recommender_service_exception extends moodle_exception {

}
/**
 * Base service ajax exception
 */
class block_recommender_service_ajax_exception extends block_recommender_service_exception {

}
