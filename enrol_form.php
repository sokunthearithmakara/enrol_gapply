<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Enrollment form
 *
 * @package     enrol_gapply
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/enrol/gapply/lib.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to enrol a user in a course
 */
class enrol_gapply_form extends moodleform {
    /**
     * instance of enrol plugin
     *
     * @var object
     */
    protected $instance;

    /**
     * Overriding this function to get unique form id for multiple apply enrolments
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata["instance"]->id;
        return $formid;
    }

    /**
     * Form definition
     */
    public function definition() {
        global $USER, $PAGE, $CFG;
        $mform = $this->_form;
        $instance = $this->_customdata["instance"];
        if ($CFG->version < 2024100700) {
            $context = context_system::instance();
            $PAGE->set_context($context);
        }
        $this->instance = $instance;
        $plugin = enrol_get_plugin('gapply');

        $heading = $plugin->get_instance_name($instance);
        if ($instance->name != null) {
            $heading = $instance->name;
        }

        $mform->addElement('header', 'selfheader-' . parent::get_form_identifier(), format_text($heading, FORMAT_HTML));

        $enrolledusers = count_enrolled_users(context_course::instance($instance->courseid), 'mod/assign:submit');
        $availableseats = $instance->customchar1;
        $seat = $availableseats - $enrolledusers;
        $seat = $seat < 0 ? 0 : $seat;

        $infohtml = '<div class="alert alert-info" role="alert">';

        if ($instance->customtext1 != null) {
            $infohtml .= format_text($instance->customtext1, FORMAT_HTML);
        }
        if ($instance->customint8 > 0) {
            $infohtml .= get_string('applicationends', 'enrol_gapply', userdate($instance->customint8));
        }

        $infohtml .= '<div>' . get_string('availableseats', 'enrol_gapply') . ': <b>' . ($instance->customchar1 == 0 ?
            get_string('unlimitedseats', 'enrol_gapply') : $seat)
            . '</b></div>';

        $infohtml .= '</div>';

        $mform->addElement('html', $infohtml);

        if (($instance->customint1 == 0 && $instance->customint3 == 1) || $instance->customint1 == 1) {
            $mform->addElement(
                'editor',
                'applytext',
                get_string('applicationtext', 'enrol_gapply'),
                ['rows' => 10, 'cols' => 100, 'class' => 'w-100']
            );
            $mform->setType('applytext', PARAM_RAW);
            if ($instance->customint1 == 1) {
                $mform->addRule('applytext', null, 'required', null, 'client');
            }
        }

        if (($instance->customint2 == 0 && $instance->customint4 == 1) || $instance->customint2 == 1) {
            $options = [
                'subdirs' => 0,
                'maxbytes' => $instance->customint6,
                'maxfiles' => $instance->customint5,
                'accepted_types' => !empty($instance->customtext2) ? explode(',', $instance->customtext2) : '*',
            ];

            $mform->addElement('filemanager', 'applyfile', get_string('applicationattachment', 'enrol_gapply'), null, $options);

            if ($instance->customint2 == 1) {
                $mform->addRule('applyfile', null, 'required', null, 'client');
            }
        }

        $mform->addElement(
            'html',
            '<div class="d-flex align-items-center justify-content-center">
        <button type="submit" name="submit" value="" class="text-uppercase font-weight-bold btn btn-primary mb-3">'
                . '<i class="fa fa-send mr-2"></i><span class="text-uppercase font-weight-bold">'
                . get_string('apply', 'enrol_gapply') . '</span></button></div>'
        );

        $this->set_display_vertical();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $USER->id);
    }
}
