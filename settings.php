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
 * Plugin administration pages are defined here.
 *
 * @package     enrol_gapply
 * @category    admin
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/user/profile/lib.php');

// Basic fields available in user table.
$fields = [
    'username'    => new lang_string('username'),
    'idnumber'    => new lang_string('idnumber'),
    'email'       => new lang_string('email'),
    'phone1'      => new lang_string('phone1'),
    'phone2'      => new lang_string('phone2'),
    'department'  => new lang_string('department'),
    'institution' => new lang_string('institution'),
    'city'        => new lang_string('city'),
    'country'     => new lang_string('country'),
];

// Custom profile fields.
$profilefields = profile_get_custom_fields();
foreach ($profilefields as $field) {
    $fields['profile_field_' . $field->shortname] = format_string(
        $field->name,
        true
    ) . ' *';
}

if ($hassiteconfig) {
    $settings = new admin_settingpage('enrol_gapply_settings', new lang_string('pluginname', 'enrol_gapply'));

    if ($ADMIN->fulltree) {
        // Set as default enrolment for new courses.
        $settings->add(new admin_setting_configcheckbox(
            'enrol_gapply/defaultenrol',
            get_string('defaultenrol', 'enrol'),
            get_string('defaultenrol_desc', 'enrol'),
            1
        ));
        // Create a multiple select box for the list of profile fields both core and custom profile fields.
        $settings->add(new admin_setting_configmultiselect(
            'enrol_gapply/showuseridentity',
            new lang_string('showuseridentity', 'enrol_gapply'),
            new lang_string('showuseridentity_desc', 'enrol_gapply'),
            ['department', 'idnumber', 'institution'],
            $fields
        ));

        // Send notification in recipient's preferred language.
        $settings->add(new admin_setting_configcheckbox(
            'enrol_gapply/sendnotificationinrecipientlang',
            new lang_string('sendnotificationinrecipientlang', 'enrol_gapply'),
            new lang_string('sendnotificationinrecipientlang_desc', 'enrol_gapply'),
            0
        ));

        if (!during_initial_install()) {
            $options = get_default_enrol_roles(context_system::instance());
            $student = get_archetype_roles('student');
            $student = reset($student);
            $settings->add(new admin_setting_configselect('enrol_gapply/roleid',
                get_string('defaultrole', 'role'), '', $student->id ?? null, $options));
        }
    }
}
