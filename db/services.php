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
 * Web services definition.
 *
 * @package    enrol_gapply
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'enrol_gapply_add_group_members' => [
        'classname'   => 'core_group_external',
        'methodname'  => 'add_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'Adds group members.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:managegroups',
    ],
    'enrol_gapply_delete_group_members' => [
        'classname'   => 'core_group_external',
        'methodname'  => 'delete_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'Deletes group members.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:managegroups',
    ],
    'enrol_gapply_create_groups' => [
        'classname'   => 'core_group_external',
        'methodname'  => 'create_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Creates new groups.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:managegroups',
    ],
    'enrol_gapply_assign_grouping' => [
        'classname'   => 'core_group_external',
        'methodname'  => 'assign_grouping',
        'classpath'   => 'group/externallib.php',
        'description' => 'Assign groups to groupings.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:managegroups',
    ],
    'enrol_gapply_get_course_groupings' => [
        'classname'   => 'core_group_external',
        'methodname'  => 'get_course_groupings',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns all groupings in specified course.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/course:managegroups',
    ],
    'enrol_gapply_manage_applications' => [
        'classname'   => 'enrol_gapply\external',
        'methodname'  => 'manage_applications',
        'description' => 'Manage applications (approve, reject, waitlist, delete, withdraw).',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'enrol_gapply_get_user_summary' => [
        'classname'   => 'enrol_gapply\external',
        'methodname'  => 'get_user_summary',
        'description' => 'Get user summary HTML.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'enrol_gapply_get_roles_and_dates' => [
        'classname'   => 'enrol_gapply\external',
        'methodname'  => 'get_roles_and_dates',
        'description' => 'Get roles and dates for an instance.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'enrol_gapply_get_applications' => [
        'classname'   => 'enrol_gapply\external',
        'methodname'  => 'get_applications',
        'description' => 'Get applications for an instance.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'enrol_gapply_get_groups' => [
        'classname'   => 'enrol_gapply\external',
        'methodname'  => 'get_groups',
        'description' => 'Get groups for a course.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'enrol_gapply_get_application_info' => [
        'classname'   => 'enrol_gapply\external',
        'methodname'  => 'get_application_info',
        'description' => 'Get basic info and attachments for a specific application.',
        'type'        => 'read',
        'ajax'        => true,
    ],
];
