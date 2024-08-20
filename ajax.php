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
 * Handle AJAX requests.
 *
 * @package     enrol_gapply
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/enrol/gapply/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_login();
require_sesskey();

$action = required_param('action', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$instance = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);
$courseid = $instance->courseid;

$context = context_course::instance($courseid);

$PAGE->set_context($context);

if (!has_capability('enrol/gapply:manage', $context)) {
    die;
}

$enrol = enrol_get_plugin('gapply');

if ($action == "approve") {
    $ids = required_param('ids', PARAM_TEXT);
    $ids = explode(',', $ids);
    $ids = array_map('intval', $ids); // Sanitize the input values
    // Get records from enrol_gapply where id in ids.
    $records = $DB->get_records_list('enrol_gapply', 'id', $ids);

    $message = new stdClass();
    $course = $DB->get_record('course', ['id' => $courseid]);
    $message->subject = get_string('applicationapproved', 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
    $message->text = get_string('applicationapproved', 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
    $message->contexturl = new moodle_url('/course/view.php', ['id' => $courseid]);
    $message->contexturlname = get_string('viewcourse', 'enrol_gapply');
    $currentlang = current_language();
    foreach ($records as $record) {
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $enrol->enrol_user($instance, $record->userid, 5, $instance->enrolstartdate, $instance->enrolenddate);
        // Add user to selected groups.
        $groups = optional_param('groups', '', PARAM_TEXT);
        if ($groups != '') {
            $groups = explode(',', $groups);
            foreach ($groups as $groupid) {
                groups_add_member($groupid, $record->userid);
            }
        }
        $user = $DB->get_record('user', ['id' => $record->userid]);
        if (get_config('enrol_gapply', 'sendnotificationinrecipientlang')) {
            $SESSION->lang = $user->lang;
            $message->subject = get_string('applicationapproved', 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
            $message->text = get_string('applicationapproved', 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
            $message->contexturlname = get_string('viewcourse', 'enrol_gapply');
        }
        $enrol->send_notification($user, $USER, $message);
    }
    $SESSION->lang = $currentlang;
    // Update records from enrol_gapply where id in ids to status approved.
    [$insql, $inparams] = $DB->get_in_or_equal($ids);
    $DB->set_field_select('enrol_gapply', 'status', 'approved', "id $insql", $inparams);
    die;
} else if ($action == "waitlist" || $action == "reject") {
    $ids = required_param('ids', PARAM_TEXT);
    $ids = explode(',', $ids);
    $ids = array_map('intval', $ids); // Sanitize the input values
    // Update records from enrol_gapply where id in ids to status waitlisted.
    [$insql, $inparams] = $DB->get_in_or_equal($ids);
    $DB->set_field_select('enrol_gapply', 'status', $action . 'ed', "id $insql", $inparams);
    $message = new stdClass();
    $course = $DB->get_record('course', ['id' => $courseid]);
    $message->subject = get_string('application' . $action, 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
    $message->text = get_string('application' . $action, 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
    $message->contexturl = new moodle_url('/course/view.php', ['id' => $courseid]);
    $message->contexturlname = get_string('viewcourse', 'enrol_gapply');
    $records = $DB->get_records_list('enrol_gapply', 'id', $ids);
    $currentlang = current_language();
    foreach ($records as $record) {
        $user = $DB->get_record('user', ['id' => $record->userid]);
        if (get_config('enrol_gapply', 'sendnotificationinrecipientlang')) {
            $SESSION->lang = $user->lang;
            $message->subject = get_string('application' . $action, 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
            $message->text = get_string('application' . $action, 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
            $message->contexturlname = get_string('viewcourse', 'enrol_gapply');
        }
        $enrol->send_notification($user, $USER, $message);
    }
    $SESSION->lang = $currentlang;
    die;
} else if ($action == "delete") {
    $ids = required_param('ids', PARAM_TEXT);
    $ids = explode(',', $ids);
    // Get userid  from enrol_gapply where id in ids.
    $ids = array_map('intval', $ids); // Sanitize the input values
    [$insql, $inparams] = $DB->get_in_or_equal($ids);
    $userid = $DB->get_fieldset_select('enrol_gapply', 'userid', "id $insql", $inparams);
    // Delete records from enrol_gapply where id in ids.
    $DB->delete_records_list('enrol_gapply', 'id', $ids);
    // Delete files from files.
    $fs = get_file_storage();
    foreach ($userid as $uid) {
        $fs->delete_area_files($context->id, 'enrol_gapply', 'applyfile', $id . $uid);
    }
    die;
} else if ($action == "getuserbyid") {
    $userid = required_param('userid', PARAM_INT);
    require_once($CFG->dirroot . '/user/profile/lib.php');
    $showuseridentity = ["firstname", 'lastname'];
    if ($instance->customtext3) {
        $showuseridentity = array_merge($showuseridentity, explode(',', $instance->customtext3));
    }
    // Remove picture from array.
    $showuseridentity = array_diff($showuseridentity, ['picture']);
    $corefields = ['id', 'firstaccess', 'lastaccess'];
    $customfields = [];

    foreach ($showuseridentity as $field) {
        if (strpos($field, 'profile_field_') !== false) {
            $customfields[] = $field;
        } else {
            $corefields[] = $field;
        }
    }

    $corefield = implode(', ', $corefields);
    $user = $DB->get_record('user', ['id' => $userid], $corefield);
    if (!empty($customfields)) {
        profile_load_custom_fields($user);
    }

    $user->picture = $OUTPUT->user_picture($user, ['size' => 64, 'class' => 'mr-2', 'link' => false]);
    $user->fullname = fullname($user);
    $user->membersince = userdate($user->firstaccess, get_string('strftimedate'));
    $user->lastaccess = userdate($user->lastaccess, get_string('strftimedate'));
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
    echo $OUTPUT->render_from_template(
        'enrol_gapply/profilesummary',
        ['user' => $user, 'identity' => $identity, 'hasidentity' => true]
    );
    die;
} else if ($action == "getgroups") {
    $groups = groups_get_all_groups($courseid, 0, 0, 'g.id, g.name');
    $groupsdata = [];
    foreach ($groups as $group) {
        $group->name = format_text($group->name, FORMAT_PLAIN);
        $groupsdata[] = ['id' => $group->id, 'name' => $group->name];
    }
    echo json_encode($groupsdata);
    die;
} else if ($action == "getapplications") {
    require_sesskey();
    $tab = required_param('tab', PARAM_TEXT);
    // Get records from enrol_gapply table where 'instance' = $id and 'status' is not 'approved'.
    $sql = "SELECT * FROM {enrol_gapply} WHERE instance = ? AND status = ?";
    $records = $DB->get_records_sql($sql, [$id, $tab]);

    if ($records) {
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $showuseridentity = ["firstname", 'lastname'];
        if ($instance->customtext3) {
            $showuseridentity = array_merge($showuseridentity, explode(',', $instance->customtext3));
        }

        // Remove picture from array.
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

        $corefield = implode(', ', $corefields);

        $fs = get_file_storage();

        foreach ($records as $record) {
            // Create an array for attachments.
            $attachments = [];

            if ($files = $fs->get_area_files($context->id, 'enrol_gapply', 'applyfile', $id . $record->userid, 'filename', false)) {

                // Look through each file being managed.
                foreach ($files as $file) {
                    $attachment = new stdClass;
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
                    array_push($attachments, $attachment);
                }
            }
            $record->attachments = $attachments;
            $record->user = $DB->get_record('user', ['id' => $record->userid], $corefield);
            // Load profile fields data to user object if there is any.
            if (!empty($customfields)) {
                profile_load_custom_fields($record->user);
            }
        }

        $table = new stdClass();
        $table->data = [];

        foreach ($records as $record) {
            $userpicture = $OUTPUT->user_picture($record->user, ['size' => 30, 'class' => 'mr-2', 'link' => false]);
            $fullname = fullname($record->user);
            $applicationtext = $record->applytext;
            $attachments = '';
            foreach ($record->attachments as $attachment) {
                $attachments .= html_writer::link('javascript:void(0)', '<i class="fa fa-fw fa-paperclip mr-1"></i>'
                    . $attachment->filename, [
                    'class' => 'small attachmentlink',
                    'data-type' => $attachment->mimetype,
                    'data-url' => $attachment->url,
                    'data-userid' => $record->userid,
                    'data-id' => $record->id
                ])
                    . '<br>';
            }
            $applicationdetails = '<div style="min-width: 500px; max-width: 100%">';
            if (!empty($record->applytext)) {
                $applicationdetails .= '<div class="applicationtext overflow-auto mb-2" style="max-height: 200px" data-id="'
                    . $record->id . '">'
                    . $record->applytext . '</div>';
            }
            $applicationdetails .= '<div class="text-truncate">' . $attachments . '</div></div>';
            $type = "primary";
            if ($record->status == 'waitlisted') {
                $type = "info";
            } else if ($record->status == 'rejected') {
                $type = "warning";
            }
            $status = '<span class="badge-pill badge-' . $type . '">' . get_string($record->status, 'enrol_gapply') . '</span>';
            $date = userdate($record->timecreated, '%d/%m/%Y, %H:%M');
            $action = '';
            // Render action menu.
            $action .= html_writer::start_tag('div', ['class' => 'dropdown']);
            $action .= html_writer::start_tag(
                'button',
                [
                    'class' => 'btn btn-icon d-flex align-items-center justify-content-center icon-no-margin ml-auto',
                    'type' => 'button',
                    'data-toggle' => 'dropdown',
                    'data-boundary' => 'window',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false'
                ]
            );
            $action .= '<i class="icon fa fa-ellipsis-v fa-fw" title="Edit" role="img" aria-label="Edit"></i>';
            $action .= html_writer::end_tag('button');
            $action .= html_writer::start_tag('ul', ['class' => 'dropdown-menu menu dropdown-menu-right']);

            $action .= html_writer::link(
                'javascript:void(0)',
                '<i class="icon fa fa-check fa-fw" aria-hidden="true"></i>'
                    . get_string('approve', 'enrol_gapply'),
                [
                    'class' => 'dropdown-item menu-action action-button',
                    'data-action' => 'approve',
                    'data-id' => $record->id
                ]
            );

            $action .= html_writer::link(
                'javascript:void(0)',
                '<i class="icon fa fa-clock-o fa-fw" aria-hidden="true"></i>'
                    . get_string('waitlist', 'enrol_gapply'),
                [
                    'class' => 'dropdown-item menu-action action-button',
                    'data-action' => 'waitlist',
                    'data-id' => $record->id
                ]
            );

            $action .= html_writer::link(
                'javascript:void(0)',
                '<i class="icon fa fa-times fa-fw" aria-hidden="true"></i>'
                    . get_string('reject', 'enrol_gapply'),
                [
                    'class' => 'dropdown-item menu-action action-button',
                    'data-action' => 'reject',
                    'data-id' => $record->id
                ]
            );

            $action .= html_writer::link(
                'javascript:void(0)',
                '<i class="icon fa fa-trash fa-fw" aria-hidden="true"></i>'
                    . get_string('delete', 'enrol_gapply'),
                [
                    'class' => 'dropdown-item menu-action action-button',
                    'data-action' => 'delete',
                    'data-id' => $record->id
                ]
            );

            $action .= html_writer::end_tag('ul');
            $action .= html_writer::end_tag('div');

            $data = [
                '',
                $record->id,
                $userpicture . '<a href="javascript:void(0)" class="showuserdetail font-weight-bold" data-status="'
                    . $record->status
                    . '" data-statusformatted=\''
                    . $status . '\' data-id="' . $record->id
                    . '" data-userid="' . $record->userid . '">'
                    . $fullname . '</a>',
            ];

            foreach ($showuseridentity as $field) {
                if (strpos($field, 'profile_field_') !== false) {
                    $field = str_replace('profile_field_', '', $field);
                    $data[] = format_string($record->user->profile[$field], true);
                } else {
                    $data[] = format_string($record->user->{$field}, true);
                }
            }
            $data = array_merge($data, [
                $applicationdetails,
                $applicationtext,
                $attachments,
                $status,
                $date,
                $record->timecreated,
                $action,
            ]);
            $table->data[] = $data;
        }
        echo json_encode($table->data);
    }
    die;
}
