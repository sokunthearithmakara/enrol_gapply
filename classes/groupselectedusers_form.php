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
 * The form to collect required information when group users.
 *
 * @package enrol_gapply
 * @copyright 2024 Sokunthearith Makara
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/enrol/bulkchange_forms.php");

/**
 * The form to collect required information when group users.
 *
 * @package enrol_gapply
 * @copyright 2024 Sokunthearith Makara
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_gapply_groupselectedusers_form extends moodleform {

    /**
     * Defines the standard structure of the form
     */
    protected function definition() {
        global $CFG;
        $form = $this->_form;
        $customdata = (object)$this->_customdata;
        $users = $customdata->users;
        $usersarray = array_values($users)[0]->enrolments;
        $enrolmentinstance = (object)array_values($usersarray)[0]->enrolmentinstance;
        $courseid = $enrolmentinstance->courseid;
        require_once($CFG->dirroot . '/group/lib.php');

        $groups = groups_get_all_groups($courseid, 0, 0, 'g.id, g.name');
        $groupsdata = [];
        foreach ($groups as $group) {
            $groupsdata[$group->id] = $group->name;
        }

        $form->addElement('select', 'groupid', get_string('group'), $groupsdata, ['multiple' => 'multiple']);

        $this->add_action_buttons();
    }
}
