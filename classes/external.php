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
 * External API for enrol_gapply.
 *
 * @package    enrol_gapply
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_gapply;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/enrol/gapply/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/group/lib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_format_value;
use context_course;
use stdClass;
use moodle_url;
use html_writer;

/**
 * External API class.
 */
class external extends external_api {
    /**
     * Parameters for manage_applications.
     */
    public static function manage_applications_parameters() {
        return new external_function_parameters([
            'action' => new external_value(PARAM_ALPHA, 'Action to perform: approve, waitlist, reject, delete, withdraw'),
            'ids' => new external_multiple_structure(new external_value(PARAM_INT, 'Application IDs')),
            'instanceid' => new external_value(PARAM_INT, 'Enrol instance ID'),
            'roleid' => new external_value(PARAM_INT, 'Role ID for enrolment', VALUE_DEFAULT, 0),
            'start' => new external_value(PARAM_INT, 'Start time for enrolment', VALUE_DEFAULT, 0),
            'end' => new external_value(PARAM_INT, 'End time for enrolment', VALUE_DEFAULT, 0),
            'groups' => new external_multiple_structure(new external_value(PARAM_INT, 'Group IDs'), 'Group IDs', VALUE_DEFAULT, []),
            'reason' => new external_value(PARAM_RAW, 'Reason for withdrawal', VALUE_DEFAULT, ''),
            'message' => new external_value(PARAM_RAW, 'Outcome message', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Manage applications (approve, reject, etc).
     * @param string $action Action to perform: approve, waitlist, reject, delete, withdraw
     * @param array $ids Array of application IDs to manage
     * @param int $instanceid Enrol instance ID
     * @param int $roleid Role ID for enrolment
     * @param int $start Start time for enrolment
     * @param int $end End time for enrolment
     * @param array $groups Array of group IDs to add users to
     * @param string $reason Reason for withdrawal
     * @param string $message Message to send to users
     * @throws \moodle_exception
     */
    public static function manage_applications(
        $action,
        $ids,
        $instanceid,
        $roleid = 0,
        $start = 0,
        $end = 0,
        $groups = [],
        $reason = '',
        $message = ''
    ) {
        global $DB, $USER, $SESSION;

        $params = self::validate_parameters(self::manage_applications_parameters(), [
            'action' => $action,
            'ids' => $ids,
            'instanceid' => $instanceid,
            'roleid' => $roleid,
            'start' => $start,
            'end' => $end,
            'groups' => $groups,
            'reason' => $reason,
            'message' => $message,
        ]);

        $instance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);
        if ($params['action'] === 'withdraw') {
            self::validate_context(\context_system::instance());
        } else {
            self::validate_context($context);
        }

        if (!has_capability('enrol/gapply:manage', $context) && $params['action'] !== 'withdraw') {
            throw new \moodle_exception('nopermissions', 'error', '', 'manage applications');
        }

        $enrol = enrol_get_plugin('gapply');
        $course = get_course($instance->courseid);
        $sendnotificationinrecipientlang = get_config('enrol_gapply', 'sendnotificationinrecipientlang');
        $fs = get_file_storage();
        $currentlang = current_language();

        switch ($params['action']) {
            case 'approve':
                [$insql, $inparams] = $DB->get_in_or_equal($params['ids']);
                $userfields = \core_user\fields::for_name()->get_sql('u', false, 'u');
                $sql = "SELECT g.*, u.lang {$userfields->selects}
                        FROM {enrol_gapply} g
                        JOIN {user} u ON u.id = g.userid
                        WHERE g.id $insql
                          AND g.instance = ?";
                $records = $DB->get_records_sql($sql, array_merge($inparams, [$instance->id], $userfields->params));

                foreach ($records as $record) {
                    $enrol->enrol_user($instance, $record->userid, $params['roleid'], $params['start'], $params['end']);

                    if (!empty($params['groups'])) {
                        foreach ($params['groups'] as $groupid) {
                            groups_add_member($groupid, $record->userid);
                        }
                    }

                    // Map joined user fields to a temporary user object.
                    $user = (object)[
                        'id' => $record->userid,
                        'lang' => $record->lang,
                    ];
                    foreach ($userfields->mappings as $field => $alias) {
                        $user->$field = $record->$alias;
                    }

                    $message = new stdClass();
                    $message->subject = get_string(
                        'applicationapproved',
                        'enrol_gapply',
                        format_text($course->fullname, FORMAT_HTML)
                    );
                    $message->text = get_string(
                        'applicationapproved',
                        'enrol_gapply',
                        format_text($course->fullname, FORMAT_HTML)
                    );
                    $message->contexturl = new moodle_url('/course/view.php', ['id' => $course->id]);
                    $message->contexturlname = get_string('viewcourse', 'enrol_gapply');

                    if ($sendnotificationinrecipientlang) {
                        $SESSION->lang = $user->lang;
                        $message->subject = get_string(
                            'applicationapproved',
                            'enrol_gapply',
                            format_text($course->fullname, FORMAT_HTML)
                        );
                        $message->text = get_string(
                            'applicationapproved',
                            'enrol_gapply',
                            format_text($course->fullname, FORMAT_HTML)
                        );
                        $message->contexturlname = get_string('viewcourse', 'enrol_gapply');
                    }

                    if (!empty($params['message'])) {
                        $custommessage = trim(self::replace_tags($params['message'], $user, $course, $instance));
                        $message->text .= "\n\n" . $custommessage;
                    }

                    $enrol->send_notification($user, $USER, $message);
                }
                $SESSION->lang = $currentlang;

                $message = trim($params['message']);
                $DB->execute(
                    "UPDATE {enrol_gapply}
                        SET status = ?, outcomemessage = ?, timemodified = ?, usermodified = ?
                      WHERE id $insql AND instance = ?",
                    array_merge(['approved', $message, time(), $USER->id], $inparams, [$instance->id])
                );
                break;

            case 'waitlist':
            case 'reject':
                [$insql, $inparams] = $DB->get_in_or_equal($params['ids']);
                $userfields = \core_user\fields::for_name()->get_sql('u', false, 'u');
                $sql = "SELECT g.*, u.lang {$userfields->selects}
                        FROM {enrol_gapply} g
                        JOIN {user} u ON u.id = g.userid
                        WHERE g.id $insql
                          AND g.instance = ?";
                $records = $DB->get_records_sql($sql, array_merge($inparams, [$instance->id], $userfields->params));

                foreach ($records as $record) {
                    // Map joined user fields to a temporary user object.
                    $user = (object)[
                        'id' => $record->userid,
                        'lang' => $record->lang,
                    ];
                    foreach ($userfields->mappings as $field => $alias) {
                        $user->$field = $record->$alias;
                    }

                    $message = new stdClass();
                    $message->subject = get_string(
                        'application' . $params['action'],
                        'enrol_gapply',
                        format_text($course->fullname, FORMAT_HTML)
                    );
                    $message->text = get_string(
                        'application' . $params['action'],
                        'enrol_gapply',
                        format_text($course->fullname, FORMAT_HTML)
                    );
                    $message->contexturl = new moodle_url('/course/view.php', ['id' => $course->id]);
                    $message->contexturlname = get_string('viewcourse', 'enrol_gapply');

                    if ($sendnotificationinrecipientlang) {
                        $SESSION->lang = $user->lang;
                        $message->subject = get_string(
                            'application' . $params['action'],
                            'enrol_gapply',
                            format_text($course->fullname, FORMAT_HTML)
                        );
                        $message->text = get_string(
                            'application' . $params['action'],
                            'enrol_gapply',
                            format_text($course->fullname, FORMAT_HTML)
                        );
                        $message->contexturlname = get_string('viewcourse', 'enrol_gapply');
                    }

                    if (!empty($params['message'])) {
                        $custommessage = trim(self::replace_tags($params['message'], $user, $course, $instance));
                        $message->text .= "\n\n" . $custommessage;
                    }

                    $enrol->send_notification($user, $USER, $message);
                }
                $SESSION->lang = $currentlang;

                $messagecontent = trim($params['message']);
                $DB->execute(
                    "UPDATE {enrol_gapply}
                        SET status = ?, outcomemessage = ?, timemodified = ?, usermodified = ?
                      WHERE id $insql AND instance = ?",
                    array_merge([$params['action'] . 'ed', $messagecontent, time(), $USER->id], $inparams, [$instance->id])
                );
                break;

            case 'delete':
                [$insql, $inparams] = $DB->get_in_or_equal($params['ids']);
                $records = $DB->get_records_select(
                    'enrol_gapply',
                    "id $insql AND instance = ?",
                    array_merge($inparams, [$instance->id]),
                    '',
                    'id'
                );
                foreach ($records as $record) {
                    $fs->delete_area_files($context->id, 'enrol_gapply', 'applyfile', $record->id);
                }
                $DB->delete_records_select(
                    'enrol_gapply',
                    "id $insql AND instance = ?",
                    array_merge($inparams, [$instance->id])
                );
                break;
            case 'withdraw':
                $records = $DB->get_records('enrol_gapply', ['instance' => $instance->id, 'userid' => $USER->id]);
                if ($records) {
                    $coursecontacts = $enrol->get_application_notification_recipients($instance, $context);

                    if ($coursecontacts) {
                        $reason = trim($params['reason']);
                        if (empty($reason)) {
                            $reason = get_string('notprovided', 'enrol_gapply');
                        }
                        $msg = new stdClass();
                        $msg->subject = get_string('applicationwithdrawn', 'enrol_gapply');
                        $msg->text = get_string('applicationwithdrawntext', 'enrol_gapply', (object)[
                            'coursefullname' => format_string($course->fullname),
                            'username' => fullname($USER),
                            'reason' => $reason,
                        ]);
                        $msg->contexturl = new moodle_url('/enrol/gapply/manage.php', ['id' => $instance->id]);
                        $msg->contexturlname = get_string('manageapplications', 'enrol_gapply');

                        $currentlang = current_language();
                        foreach ($coursecontacts as $contact) {
                            if ($sendnotificationinrecipientlang) {
                                $SESSION->lang = $contact->lang;
                                $msg->subject = get_string('applicationwithdrawn', 'enrol_gapply');
                                $msg->text = get_string('applicationwithdrawntext', 'enrol_gapply', (object)[
                                    'coursefullname' => format_string($course->fullname),
                                    'username' => fullname($USER),
                                    'reason' => $reason,
                                ]);
                            }

                            $enrol->send_notification($contact, $USER, $msg);
                        }
                        $SESSION->lang = $currentlang;
                    }
                }
                foreach ($records as $record) {
                    $fs->delete_area_files($context->id, 'enrol_gapply', 'applyfile', $record->id);
                }
                $DB->delete_records('enrol_gapply', ['instance' => $instance->id, 'userid' => $USER->id]);
                break;
        }

        return true;
    }

    /**
     * Returns manage_applications result.
     */
    public static function manage_applications_returns() {
        return new external_value(PARAM_BOOL, 'Success');
    }

    /**
     * Parameters for get_user_summary.
     */
    public static function get_user_summary_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'instanceid' => new external_value(PARAM_INT, 'Enrol instance ID'),
        ]);
    }

    /**
     * Get user summary HTML.
     * @param int $userid User ID
     * @param int $instanceid Enrol instance ID
     * @throws \moodle_exception
     * @return string HTML summary
     */
    public static function get_user_summary($userid, $instanceid) {
        global $DB, $OUTPUT, $CFG;

        $params = self::validate_parameters(self::get_user_summary_parameters(), [
            'userid' => $userid,
            'instanceid' => $instanceid,
        ]);

        $instance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $course = get_course($instance->courseid);
        $context = context_course::instance($instance->courseid);
        self::validate_context($context);
        require_capability('enrol/gapply:manage', $context);

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $showuseridentity = ["firstname", 'lastname'];
        $settingidentity = explode(',', get_config('enrol_gapply', 'showuseridentity'));
        if ($settingidentity) {
            $showuseridentity = array_merge($showuseridentity, $settingidentity);
        }
        $showuseridentity = array_diff($showuseridentity, ['picture']);

        $corefields = ['id'];
        $customfields = [];
        foreach ($showuseridentity as $field) {
            if (strpos($field, 'profile_field_') !== false) {
                $customfields[] = $field;
            } else {
                $corefields[] = $field;
            }
        }

        $picfields = \core_user\fields::get_picture_fields();
        $corefields = array_unique(array_merge($corefields, $picfields));
        $userfieldaliases = [];
        $userselects = [];
        foreach ($corefields as $field) {
            if ($field === 'id') {
                continue;
            }
            $alias = 'user_' . $field;
            $userfieldaliases[$field] = $alias;
            $userselects[] = "u.$field AS $alias";
        }

        $userfields = \core_user\fields::for_name()->get_sql('m', false, 'mod');
        $userselect = $userselects ? ', ' . implode(', ', $userselects) : '';
        $sql = "SELECT u.id AS userid {$userselect},
                       g.timecreated, g.timemodified, g.usermodified, g.outcomemessage {$userfields->selects}
                  FROM {user} u
             LEFT JOIN {enrol_gapply} g ON g.userid = u.id AND g.instance = ?
             LEFT JOIN {user} m ON m.id = g.usermodified
                 WHERE u.id = ?";
        $records = $DB->get_records_sql($sql, array_merge([$instance->id, $params['userid']], $userfields->params), 0, 1);
        $application = reset($records);
        if (!$application) {
            throw new \moodle_exception('invaliduser', 'error');
        }

        $user = new stdClass();
        $user->id = $application->userid;
        foreach ($userfieldaliases as $field => $alias) {
            $user->$field = $application->$alias ?? '';
        }
        if (!empty($customfields)) {
            profile_load_custom_fields($user);
        }

        $user->picture = $OUTPUT->user_picture($user, ['size' => 50, 'class' => 'mr-2', 'link' => false]);
        $user->fullname = fullname($user);
        $user->appliedon = !empty($application->timecreated) ?
            userdate($application->timecreated, get_string('strftimedatetime', 'langconfig')) : '-';

        $modifiername = null;
        $modifiedon = null;
        if ($application && $application->timemodified) {
            $modifiedon = userdate($application->timemodified, get_string('strftimedatetime', 'langconfig'));
            if ($application->usermodified) {
                // Map the joined fields back to a user object for fullname().
                $moduser = new stdClass();
                foreach (
                    [
                        'firstname',
                        'lastname',
                        'firstnamephonetic',
                        'lastnamephonetic',
                        'middlename',
                        'alternatename',
                    ] as $field
                ) {
                    $alias = 'mod' . $field;
                    $moduser->$field = $application->$alias ?? '';
                }
                $modifiername = fullname($moduser);
                if (empty($modifiername)) {
                    $modifiername = get_string('unknownuser', 'core');
                }
            }
        }

        $identity = [];
        foreach ($showuseridentity as $field) {
            $item = new stdClass();
            if (in_array($field, $customfields)) {
                $item->name = \core_user\fields::get_display_name($field);
                $item->value = format_string($user->profile[str_replace('profile_field_', '', $field)], true);
            } else {
                $item->name = get_string($field);
                $item->value = format_string($user->$field, true);
            }
            $identity[] = $item;
        }

        return [
            'user' => [
                'fullname' => $user->fullname,
                'picture' => $user->picture,
                'appliedon' => $user->appliedon,
                'modifiedon' => $modifiedon,
                'modifiername' => $modifiername,
                'outcomemessage' => $application && !empty($application->outcomemessage) ?
                    nl2br(s(trim(self::replace_tags($application->outcomemessage, $user, $course, $instance)))) : '',
            ],
            'identity' => $identity,
        ];
    }

    /**
     * Returns get_user_summary result.
     */
    public static function get_user_summary_returns() {
        return new external_single_structure([
            'user' => new external_single_structure([
                'fullname' => new external_value(PARAM_RAW, 'Full name'),
                'picture' => new external_value(PARAM_RAW, 'User picture HTML'),
                'appliedon' => new external_value(PARAM_RAW, 'Applied on date'),
                'modifiedon' => new external_value(PARAM_RAW, 'Modified on date', VALUE_OPTIONAL),
                'modifiername' => new external_value(PARAM_RAW, 'Modifier fullname', VALUE_OPTIONAL),
                'outcomemessage' => new external_value(PARAM_RAW, 'Outcome message', VALUE_OPTIONAL),
            ]),
            'identity' => new external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_RAW, 'Field name'),
                'value' => new external_value(PARAM_RAW, 'Field value'),
            ])),
        ]);
    }

    /**
     * Parameters for get_groups.
     */
    public static function get_groups_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get groups for a course.
     * @param int $courseid Course ID
     * @throws \moodle_exception
     * @return array Array of group arrays
     */
    public static function get_groups($courseid) {
        $params = self::validate_parameters(self::get_groups_parameters(), [
            'courseid' => $courseid,
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('enrol/gapply:manage', $context);

        $groups = groups_get_all_groups($params['courseid'], 0, 0, 'g.id, g.name');

        $results = [];
        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->id,
                'name' => format_text($group->name, FORMAT_PLAIN),
            ];
        }

        return $results;
    }

    /**
     * Returns get_groups result.
     */
    public static function get_groups_returns() {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Group ID'),
            'name' => new external_value(PARAM_RAW, 'Group name'),
        ]));
    }

    /**
     * Parameters for get_roles_and_dates.
     */
    public static function get_roles_and_dates_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Enrol instance ID'),
        ]);
    }

    /**
     * Get roles and dates for an instance.
     * @param int $instanceid Enrol instance ID
     * @throws \moodle_exception
     * @return array Array of role arrays
     */
    public static function get_roles_and_dates($instanceid) {
        global $DB;

        $params = self::validate_parameters(self::get_roles_and_dates_parameters(), [
            'instanceid' => $instanceid,
        ]);

        $instance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);
        self::validate_context($context);
        require_capability('enrol/gapply:manage', $context);

        $roles = get_assignable_roles($context, ROLENAME_BOTH);

        $formattedroles = [];
        foreach ($roles as $id => $name) {
            $formattedroles[] = ['id' => $id, 'name' => $name];
        }

        return [
            'roles' => $formattedroles,
            'defaultrole' => $instance->roleid,
            'startdate' => (int)$instance->enrolstartdate,
            'enddate' => (int)$instance->enrolenddate,
        ];
    }

    /**
     * Returns get_roles_and_dates result.
     */
    public static function get_roles_and_dates_returns() {
        return new external_single_structure([
            'roles' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Role ID'),
                'name' => new external_value(PARAM_RAW, 'Role name'),
            ])),
            'defaultrole' => new external_value(PARAM_INT, 'Default role ID'),
            'startdate' => new external_value(PARAM_INT, 'Start date'),
            'enddate' => new external_value(PARAM_INT, 'End date'),
        ]);
    }

    /**
     * Parameters for get_applications.
     */
    public static function get_applications_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Enrol instance ID'),
            'tab' => new external_value(PARAM_ALPHA, 'Tab/Status'),
        ]);
    }

    /**
     * Get applications for an instance.
     * @param int $instanceid Enrol instance ID
     * @param string $tab Tab/Status
     * @throws \moodle_exception
     * @return array Array of application arrays
     */
    public static function get_applications($instanceid, $tab) {
        global $DB, $OUTPUT, $CFG;

        $params = self::validate_parameters(self::get_applications_parameters(), [
            'instanceid' => $instanceid,
            'tab' => $tab,
        ]);

        $instance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);
        self::validate_context($context);
        require_capability('enrol/gapply:manage', $context);

        $results = [];
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $showuseridentity = ["firstname", 'lastname'];
        $settingidentity = explode(',', get_config('enrol_gapply', 'showuseridentity'));
        if ($settingidentity) {
            $showuseridentity = array_merge($showuseridentity, $settingidentity);
        }
        $showuseridentity = array_diff($showuseridentity, ['picture']);

        $corefields = ["id"];
        $customfields = [];
        foreach ($showuseridentity as $field) {
            if (strpos($field, 'profile_field_') !== false) {
                $customfields[] = str_replace('profile_field_', '', $field);
            } else {
                $corefields[] = $field;
            }
        }

        $picfields = \core_user\fields::get_picture_fields();
        $corefields = array_unique(array_merge($corefields, $picfields));
        $userfieldaliases = [];
        $userselects = [];
        foreach ($corefields as $field) {
            if ($field === 'id') {
                continue;
            }
            $alias = 'user_' . $field;
            $userfieldaliases[$field] = $alias;
            $userselects[] = "u.$field AS $alias";
        }
        $userselect = $userselects ? ', ' . implode(', ', $userselects) : '';

        $sql = "SELECT g.*{$userselect}
                  FROM {enrol_gapply} g
                  JOIN {user} u ON u.id = g.userid
                 WHERE g.instance = ? AND g.status = ?";
        $records = $DB->get_records_sql($sql, [$instance->id, $params['tab']]);

        if ($records) {
            $fs = get_file_storage();
            $filesbyapplication = [];
            $files = $fs->get_area_files(
                $context->id,
                'enrol_gapply',
                'applyfile',
                array_keys($records),
                'itemid, filename',
                false
            );
            foreach ($files as $file) {
                $filesbyapplication[$file->get_itemid()][] = $file;
            }

            foreach ($records as $record) {
                $attachments = [];
                $files = $filesbyapplication[$record->id] ?? [];
                if ($files) {
                    foreach ($files as $file) {
                        $attachment = new stdClass();
                        $attachment->filename = $file->get_filename();
                        $attachment->url = moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            $file->get_itemid(),
                            $file->get_filepath(),
                            $file->get_filename()
                        )->out();
                        $attachment->mimetype = $file->get_mimetype();
                        $attachments[] = $attachment;
                    }
                }

                $user = new stdClass();
                $user->id = $record->userid;
                foreach ($userfieldaliases as $field => $alias) {
                    $user->$field = $record->$alias ?? '';
                }
                if (!empty($customfields)) {
                    profile_load_custom_fields($user);
                }

                $userpicture = $OUTPUT->user_picture($user, ['size' => 30, 'class' => 'mr-2', 'link' => false]);
                $fullname = fullname($user);

                $attachmentshtml = '';
                foreach ($attachments as $attachment) {
                    $attachmentshtml .= html_writer::link('javascript:void(0)', '<i class="fa fa-fw fa-paperclip mr-1"></i>'
                        . $attachment->filename, [
                        'class' => 'small attachmentlink',
                        'data-type' => $attachment->mimetype,
                        'data-url' => $attachment->url,
                        'data-userid' => $record->userid,
                        'data-id' => $record->id,
                    ]) . '<br>';
                }

                $applicationdetails = '<div style="min-width: 500px; max-width: 100%">';
                $formattedapplytext = '';
                $plainapplytext = '';
                if (!empty($record->applytext)) {
                    $formattedapplytext = format_text(
                        $record->applytext,
                        $record->format,
                        ['context' => $context]
                    );
                    $plainapplytext = content_to_text($record->applytext, $record->format);
                    $applicationdetails .= '<div class="applicationtext overflow-auto mb-2" style="max-height: 200px" data-id="'
                        . $record->id . '">' . $formattedapplytext . '</div>';
                }
                $applicationdetails .= '<div class="text-truncate">' . $attachmentshtml . '</div></div>';

                $type = 'primary';
                $icon = 'fa-circle-o';
                if ($record->status == 'rejected') {
                    $type = 'danger';
                    $icon = 'fa-times';
                } else if ($record->status == 'waitlisted') {
                    $type = 'info';
                    $icon = 'fa-clock-o';
                } else if ($record->status == 'approved') {
                    $type = 'success';
                    $icon = 'fa-check';
                }
                $status = '<span class="px-2 py-1 rounded-sm badge-' . $type . '">'
                    . '<i class="fa fa-fw ' . $icon . ' me-2 mr-2"></i>'
                    . get_string($record->status, 'enrol_gapply') . '</span>';
                $date = userdate($record->timecreated, '%d/%m/%Y, %H:%M');

                // Action menu.
                $actionhtml = html_writer::start_tag('div', ['class' => 'dropdown']);
                $actionhtml .= html_writer::start_tag('button', [
                    'class' => 'btn btn-icon d-flex align-items-center justify-content-center icon-no-margin ml-auto',
                    'type' => 'button',
                    'data-toggle' => 'dropdown',
                    'data-bs-toggle' => 'dropdown',
                    'data-bs-boundary' => 'window',
                    'data-boundary' => 'window',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                ]);
                $actionhtml .= '<i class="icon fa fa-ellipsis-v fa-fw" title="Edit" role="img" aria-label="Edit"></i>';
                $actionhtml .= html_writer::end_tag('button');
                $actionhtml .= html_writer::start_tag(
                    'ul',
                    ['class' => 'dropdown-menu menu dropdown-menu-right dropdown-menu-end']
                );

                $menuactions = [];
                if ($record->status !== 'approved') {
                    $menuactions[] = ['approve', 'check'];
                    if ($record->status !== 'waitlisted') {
                        $menuactions[] = ['waitlist', 'clock-o'];
                    }
                    if ($record->status !== 'rejected') {
                        $menuactions[] = ['reject', 'times'];
                    }
                }
                $menuactions[] = ['delete', 'trash'];
                foreach ($menuactions as $ma) {
                    $actionhtml .= html_writer::link(
                        'javascript:void(0)',
                        '<i class="icon fa fa-' . $ma[1] . ' fa-fw" aria-hidden="true"></i>' . get_string($ma[0], 'enrol_gapply'),
                        ['class' => 'dropdown-item menu-action action-button', 'data-action' => $ma[0], 'data-id' => $record->id]
                    );
                }
                $actionhtml .= html_writer::end_tag('ul');
                $actionhtml .= html_writer::end_tag('div');

                $data = ['', $record->id, $userpicture
                    . '<a href="javascript:void(0)" class="showuserdetail font-weight-bold" data-status="'
                    . $record->status . '" data-statusformatted=\'' . $status . '\' data-id="' . $record->id
                    . '" data-userid="' . $record->userid . '">' . $fullname . '</a>'];

                foreach ($showuseridentity as $field) {
                    if (strpos($field, 'profile_field_') !== false) {
                        $f = str_replace('profile_field_', '', $field);
                        $val = isset($user->profile[$f]) ? $user->profile[$f] : '';
                        $data[] = format_string($val, true);
                    } else {
                        $val = isset($user->{$field}) ? $user->{$field} : '';
                        $data[] = format_string($val, true);
                    }
                }

                $data = array_merge($data, [
                    $applicationdetails,
                    $plainapplytext,
                    $attachmentshtml,
                    $status,
                    $date,
                    (int)$record->timecreated,
                    $actionhtml,
                    (int)$record->userid, // Added for easy access in JS.
                    $record->status, // Added for easy access in JS.
                ]);
                $results[] = $data;
            }
        }

        return $results;
    }

    /**
     * Returns get_applications result.
     */
    public static function get_applications_returns() {
        return new external_multiple_structure(new external_multiple_structure(new external_value(PARAM_RAW, 'Field value')));
    }

    /**
     * Parameters for get_application_info.
     */
    public static function get_application_info_parameters() {
        return new external_function_parameters([
            'applicationid' => new external_value(PARAM_INT, 'Application ID'),
            'instanceid' => new external_value(PARAM_INT, 'Enrol instance ID'),
        ]);
    }

    /**
     * Get basic info for a specific application.
     * @param int $applicationid Application ID
     * @param int $instanceid Enrol instance ID
     * @throws \moodle_exception
     * @return array Array of application info
     */
    public static function get_application_info($applicationid, $instanceid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::get_application_info_parameters(), [
            'applicationid' => $applicationid,
            'instanceid' => $instanceid,
        ]);

        $instance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);
        self::validate_context($context);
        require_capability('enrol/gapply:manage', $context);

        $record = $DB->get_record('enrol_gapply', ['id' => $params['applicationid'], 'instance' => $instance->id]);

        if (!$record) {
            return ['found' => false];
        }

        $fs = get_file_storage();
        $attachments = [];
        $files = $fs->get_area_files($context->id, 'enrol_gapply', 'applyfile', $record->id, 'filename', false);
        if ($files) {
            foreach ($files as $file) {
                $attachments[] = [
                    'filename' => $file->get_filename(),
                    'link' => moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out(),
                    'type' => $file->get_mimetype(),
                ];
            }
        }

        $type = 'primary';
        $icon = 'fa-circle-o';
        if ($record->status == 'rejected') {
            $type = 'danger';
            $icon = 'fa-times';
        } else if ($record->status == 'waitlisted') {
            $type = 'info';
            $icon = 'fa-clock-o';
        } else if ($record->status == 'approved') {
            $type = 'success';
            $icon = 'fa-check';
        }
        $statushtml = '<span class="px-2 py-1 rounded-sm badge-' . $type . '">'
            . '<i class="fa fa-fw ' . $icon . ' me-2 mr-2"></i>'
            . get_string($record->status, 'enrol_gapply') . '</span>';

        return [
            'found' => true,
            'userid' => $record->userid,
            'status' => $statushtml,
            'statusraw' => $record->status,
            'applytext' => format_text($record->applytext ?? '', $record->format ?? FORMAT_HTML, ['context' => $context]),
            'attachments' => $attachments,
        ];
    }

    /**
     * Returns get_application_info result.
     */
    public static function get_application_info_returns() {
        return new external_single_structure([
            'found' => new external_value(PARAM_BOOL, 'Found status'),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_RAW, 'Application status HTML', VALUE_OPTIONAL),
            'statusraw' => new external_value(PARAM_ALPHA, 'Application status raw', VALUE_OPTIONAL),
            'applytext' => new external_value(PARAM_RAW, 'Application text', VALUE_OPTIONAL),
            'attachments' => new external_multiple_structure(
                new external_single_structure([
                    'filename' => new external_value(PARAM_TEXT, 'File name'),
                    'link' => new external_value(PARAM_URL, 'File URL'),
                    'type' => new external_value(PARAM_TEXT, 'Mime type'),
                ]),
                'Attachments',
                VALUE_OPTIONAL
            ),
        ]);
    }
    /**
     * Parameters for get_status_counts.
     */
    public static function get_status_counts_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Enrol instance ID'),
        ]);
    }

    /**
     * Get application counts grouped by status.
     *
     * @param int $instanceid Enrol instance ID
     * @return array
     */
    public static function get_status_counts($instanceid) {
        global $DB;

        $params = self::validate_parameters(self::get_status_counts_parameters(), [
            'instanceid' => $instanceid,
        ]);

        $instance = $DB->get_record('enrol', ['id' => $params['instanceid'], 'enrol' => 'gapply'], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);
        self::validate_context($context);
        require_capability('enrol/gapply:manage', $context);

        $counts = [
            'new' => 0,
            'approved' => 0,
            'waitlisted' => 0,
            'rejected' => 0,
        ];

        $sql = 'SELECT status, COUNT(1) AS total
                  FROM {enrol_gapply}
                 WHERE instance = ?
              GROUP BY status';
        $records = $DB->get_records_sql($sql, [$instance->id]);
        foreach ($records as $record) {
            if (array_key_exists($record->status, $counts)) {
                $counts[$record->status] = (int) $record->total;
            }
        }

        return $counts;
    }

    /**
     * Returns get_status_counts result.
     */
    public static function get_status_counts_returns() {
        return new external_single_structure([
            'new' => new external_value(PARAM_INT, 'New applications count'),
            'approved' => new external_value(PARAM_INT, 'Approved applications count'),
            'waitlisted' => new external_value(PARAM_INT, 'Waitlisted applications count'),
            'rejected' => new external_value(PARAM_INT, 'Rejected applications count'),
        ]);
    }

    /**
     * Replace tags in the message.
     *
     * @param string $message
     * @param stdClass $user
     * @param stdClass $course
     * @param stdClass $instance
     * @return string
     */
    public static function replace_tags($message, $user, $course, $instance) {
        $tags = [
            '{{firstname}}' => $user->firstname,
            '{{lastname}}' => $user->lastname,
            '{{fullname}}' => fullname($user),
            '{{coursename}}' => format_string($course->fullname),
            '{{courseid}}' => $course->id,
        ];

        return str_replace(array_keys($tags), array_values($tags), $message);
    }
}
