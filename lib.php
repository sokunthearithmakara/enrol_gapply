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
 * The enrol plugin gapply is defined here.
 *
 * @package     enrol_gapply
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The base class 'enrol_plugin' can be found at lib/enrollib.php. Override
// methods as necessary.

defined('MOODLE_INTERNAL') || die();
/**
 * Class enrol_gapply_plugin.
 */
class enrol_gapply_plugin extends enrol_plugin {
class enrol_gapply_plugin extends enrol_plugin {

    /**
     * Return an array of action icons for the instance.
     *
     * @param stdClass $instance Course enrol instance.
     * @return array.
     */
    public function get_action_icons($instance) {
    public function get_action_icons($instance) {
        global $OUTPUT;
        $context = context_course::instance($instance->courseid);
        $icons = [];
        if (has_capability('enrol/gapply:config', $context)) {
            $enrolurl = new moodle_url(
                '/enrol/editinstance.php',
                [
                    'id' => $instance->id,
                    'courseid' => $instance->courseid,
                    'type' => 'gapply',
                ]
            );
            $icons[] =
                $OUTPUT->action_icon($enrolurl, new pix_icon(
                    'i/settings',
                    get_string('edit', 'moodle'),
                    'core',
                    ['class' => 'iconsmall']
                ));
        }
        if (has_capability('enrol/gapply:manage', $context)) {
            $managelink = new moodle_url(
                '/enrol/gapply/manage.php',
                [
                    'id' => $instance->id,
                    'courseid' => $instance->courseid,
                ]
            );
            $icons[] =
                $OUTPUT->action_icon($managelink, new pix_icon(
                    'i/users',
                    get_string('applications', 'enrol_gapply'),
                    'core',
                    ['class' => 'iconsmall']
                ));
        }
        return $icons;
    }

    /**
     * Does this plugin allow manual enrolments?
     *
     * All plugins allowing this must implement 'enrol/gapply:enrol' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means user with 'enrol/gapply:enrol' may enrol others freely,
     * false means nobody may add more enrolments manually.
     */
    public function allow_enrol($instance) {
    public function allow_enrol($instance) {
        return true;
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     *
     * All plugins allowing this must implement 'enrol/gapply:unenrol' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means user with 'enrol/gapply:unenrol' may unenrol others freely,
     * false means nobody may touch user_enrolments.
     */
    public function allow_unenrol($instance) {
    public function allow_unenrol($instance) {
        return true;
    }

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * All plugins allowing this must implement 'enrol/gapply:manage' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means it is possible to change enrol period and status in user_enrolments table.
     */
    public function allow_manage($instance) {
    public function allow_manage($instance) {
        return true;
    }

    /**
     * Does this plugin allow manual unenrolment of a specific user?
     *
     * All plugins allowing this must implement 'enrol/gapply:unenrol' capability.
     *
     * This is useful especially for synchronisation plugins that
     * do suspend instead of full unenrolment.
     *
     * @param stdClass $instance Course enrol instance.
     * @param stdClass $ue Record from user_enrolments table, specifies user.
     * @return bool True means user with 'enrol/gapply:unenrol' may unenrol this user,
     * false means nobody may touch this user enrolment.
     */
    public function allow_unenrol_user($instance, $ue) {
    public function allow_unenrol_user($instance, $ue) {
        return true;
    }

    /**
     * Use the standard interface for adding/editing the form.
     *
     * @since Moodle 3.1.
     * @return bool.
     */
    public function use_standard_editing_ui() {
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Adds form elements to add/edit instance form.
     *
     * @since Moodle 3.1.
     * @param stdClass $instance Enrol instance or null if does not exist yet.
     * @param MoodleQuickForm $mform Quick form.
     * @param context $context current context.
     * @return void
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $PAGE, $CFG;
        $PAGE->add_body_class('limitedwidth');

        // Do nothing by default.
        $mform->addElement('text', 'name', get_string('name', 'enrol_gapply'), ['size' => '100']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'name', 'enrol_gapply');

        // Instruction textbox.
        $mform->addElement('editor', 'customtext1', get_string('description', 'enrol_gapply'), ['rows' => 10]);
        $mform->setType('customtext1', PARAM_RAW);

        // Set default value.
        if ($instance != null && isset($instance->customtext1)) {
            $mform->setDefault(
                'customtext1',
                [
                    'text' => $instance->customtext1 ? $instance->customtext1 : '',
                    'format' => FORMAT_HTML,
                ]
            );
        }

        $mform->addElement('advcheckbox', 'customint1', get_string('requireapplicationtext', 'enrol_gapply'), null, [0, 1]);
        $mform->setDefault('customint1', 1);
        $mform->addHelpButton('customint1', 'requireapplicationtext', 'enrol_gapply');

        $mform->addElement('advcheckbox', 'customint3', get_string('showapplicationtext', 'enrol_gapply'), null, [0, 1]);
        $mform->hideIf('customint3', 'customint1', 'checked');
        $mform->addHelpButton('customint3', 'showapplicationtext', 'enrol_gapply');

        $mform->addElement('advcheckbox', 'customint2', get_string('requireapplicationfile', 'enrol_gapply'), null, [0, 1]);
        $mform->setDefault('customint2', 1);
        $mform->addHelpButton('customint2', 'requireapplicationfile', 'enrol_gapply');

        $mform->addElement('advcheckbox', 'customint4', get_string('showapplicationfile', 'enrol_gapply'), null, [0, 1]);
        $mform->hideIf('customint4', 'customint2', 'checked');
        $mform->addHelpButton('customint4', 'showapplicationfile', 'enrol_gapply');

        // Availibity header.
        $mform->addElement('header', 'availability', get_string('availability', 'enrol_gapply'));
        $mform->setExpanded('availability', true);

        $mform->addElement(
            'date_time_selector',
            'customint7',
            get_string('applicationstartdate', 'enrol_gapply'),
            ['optional' => true]
        );
        $mform->addHelpButton('customint7', 'applicationstartdate', 'enrol_gapply');

        $mform->addElement(
            'date_time_selector',
            'customint8',
            get_string('applicationenddate', 'enrol_gapply'),
            ['optional' => true]
        );
        $mform->addHelpButton('customint8', 'applicationenddate', 'enrol_gapply');

        $profilefields = get_config('enrol_gapply', 'showuseridentity');
        $profilefields = explode(',', $profilefields);
        // Remove the empty value.
        // Remove the empty value.
        $profilefields = array_filter($profilefields);
        $profilefields[] = 'picture';
        $profilefieldsobj = new stdClass();
        // Get custom profile field.
        foreach ($profilefields as $profield) {
            if (strpos($profield, 'profile_field_') !== false) {
                $profilefieldsobj->$profield = \core_user\fields::get_display_name($profield) . ' *';
            } else {
                $profilefieldsobj->$profield = get_string($profield, 'moodle');
            }
        }

        $profilefields = (array) $profilefieldsobj;

        asort($profilefields);

        $mform->addElement('select', 'customtext3', get_string('profilefields', 'enrol_gapply'), $profilefields, [
            'multiple' => 'multiple',
            'style' => 'width: 100%;',
        ]);
        $mform->addHelpButton('customtext3', 'profilefields', 'enrol_gapply');

        if ($instance != null && isset($instance->customtext3)) {
            if (is_string($instance->customtext3)) {
                $instance->customtext3 = explode(',', $instance->customtext3);
            }
            $mform->setDefault('customtext3', $instance->customtext3);
        }

        // 1.0.4 Update limit seats.
        $mform->addElement('text', 'customchar1', get_string('availableseats', 'enrol_gapply'), ['size' => '10']);
        $mform->setType('customchar1', PARAM_INT);
        $mform->addHelpButton('customchar1', 'availableseats', 'enrol_gapply');
        $mform->addRule('customchar1', null, 'numeric', null, 'client');
        $mform->setDefault('customchar1', 0);

        // Allow user to apply even seats are full.
        $mform->addElement('advcheckbox', 'customchar2', get_string('allowoverenrol', 'enrol_gapply'), null, [0, 1]);
        $mform->setDefault('customchar2', 0);

        // Submission header.
        $mform->addElement('header', 'submission', get_string('applicationattachment', 'enrol_gapply'));
        $mform->setExpanded('submission', true);

        $options = [];
        for ($i = 1; $i <= 20; $i++) { // Max 20 files.
            $options[$i] = $i;
        }

        $name = get_string('maxattachmentnum', 'enrol_gapply');
        $mform->addElement('select', 'customint5', $name, $options); // Max files.

        // Size limit.
        $name = get_string('maxattachmentsize', 'enrol_gapply');
        $choices = get_max_upload_sizes(
            $CFG->maxbytes,
            $PAGE->course->maxbytes
        );

        $mform->addElement(
            'select',
            'customint6',
            $name,
            $choices
        ); // Max bytes.

        $name = get_string('acceptedfiletypes', 'enrol_gapply');
        $mform->addElement('filetypes', 'customtext2', $name); // File types.

        $defaultfiletypes = '.doc .docx .pdf web_image';
        $mform->setDefault('customtext2', $defaultfiletypes);

        $mform->addElement('header', 'enrolment', get_string('enrolment', 'enrol_gapply'));
        $mform->setExpanded('enrolment', true);

        $mform->addElement(
            'date_time_selector',
            'enrolstartdate',
            get_string('enrolstartdate', 'enrol_self'),
            ['optional' => true]
        );
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_gapply');

        $mform->addElement(
            'date_time_selector',
            'enrolenddate',
            get_string('enrolenddate', 'enrol_self'),
            ['optional' => true]
        );
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_gapply');
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @since Moodle 3.1.
     * @param array $data Array of ("fieldname"=>value) of submitted data.
     * @param array $files Array of uploaded files "element_name"=>tmp_file_path.
     * @param object $instance The instance data loaded from the DB.
     * @param context $context The context of the instance we are editing.
     * @return array Array of "element_name"=>"error_description" if there are errors, empty otherwise.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
    public function edit_instance_validation($data, $files, $instance, $context) {
        // No errors by default.
        return [];
    }

    /**
     * Return whether or not, given the current state, it is possible to add a new instance
     * of this enrolment plugin to the course.
     *
     * @param int $courseid Course ID.
     * @return bool.
     */
    public function can_add_instance($courseid) {
    public function can_add_instance($courseid) {
        return true;
    }

    /**
     * Return whether or not, given the current state, it is possible to hide/show an instance
     * of this enrolment plugin to the course.
     *
     * @param int $courseid Course ID.
     * @return bool.
     */
    public function can_hide_show_instance($courseid) {
    public function can_hide_show_instance($courseid) {
        return true;
    }

    /**
     * Return whether or not, given the current state, it is possible to delete an instance
     * of this enrolment plugin to the course.
     *
     * @param int $courseid Course ID.
     * @return bool.
     */
    public function can_delete_instance($courseid) {
    public function can_delete_instance($courseid) {
        return true;
    }

    /**
     * Return whether or not, given the current state, it is possible to edit an instance
     * of this enrolment plugin to the course.
     *
     * @param int $courseid Course ID.
     * @return bool.
     */
    public function can_edit_instance($courseid) {
    public function can_edit_instance($courseid) {
        return true;
    }

    /**
     * Return whether or not, given the current state, it is possible to show enrolme link
     * of this enrolment plugin to the course.
     *
     * @param stdClass $instance Enrollment instance.
     * @return bool.
     */
    public function show_enrolme_link(stdClass $instance) {
    public function show_enrolme_link(stdClass $instance) {
        return true;
    }

    /**
     * Return UI for enrollment form.
     * @param stdClass $instance Enrollment instance.
     * @return string.
     */
    public function enrol_page_hook(stdClass $instance) {
    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $OUTPUT, $DB, $USER, $PAGE;

        if (isguestuser()) {
            // Can not enrol guest!
            return null;
        }

        // Get record from enrol_gapply table.
        $record = $DB->get_record('enrol_gapply', ['instance' => $instance->id, 'userid' => $USER->id]);
        if ($record) {
            $recordcontext = [];
            $timeapplied = userdate($record->timecreated);
            $recordcontext['time'] = $timeapplied;

            if ($record->status == 'new') {
                $recordcontext['new'] = true;
            } else if ($record->status == 'waitlisted') {
                $recordcontext['waitlisted'] = true;
            } else if ($record->status == 'rejected') {
                $recordcontext['rejected'] = true;
            } else if ($record->status == 'approved') {

                $enrolment = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $USER->id]);

                if ($enrolment) {
                    if ($enrolment->status == ENROL_USER_ACTIVE && $enrolment->timestart >= time() && $enrolment->timestart != 0) {
                        $recordcontext['approved'] = true;
                        $recordcontext['enrolmentstart'] = userdate($enrolment->timestart);
                    } else if (
                        $enrolment->status == ENROL_USER_ACTIVE
                        && $enrolment->timeend < time()
                        && $enrolment->timeend != 0
                    ) {
                        $recordcontext['expired'] = true;
                        $recordcontext['enrolmentend'] = userdate($enrolment->timeend);
                    } else if ($enrolment->status == ENROL_USER_SUSPENDED) {
                        $recordcontext['suspended'] = true;
                    }
                } else {
                    $recordcontext['noenrolment'] = true;
                }
            }

            $fs = get_file_storage();
            $attachments = [];

            $coursecontext = context_course::instance($instance->courseid);

            if ($files = $fs->get_area_files(
                $coursecontext->id,
                'enrol_gapply',
                'applyfile',
                $instance->id . $record->userid,
                'filename',
                false
            )) {
                // Look through each file being managed.
                foreach ($files as $file) {
                    $downloadurl = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out();
                    $attachment = new stdClass;
                    $attachment->filename = $file->get_filename();
                    $attachment->url = $downloadurl;
                    $attachment->mimetype = $file->get_mimetype();
                    array_push($attachments, $attachment);
                }
            }

            if (count($attachments) > 0) {
                $recordcontext['hasattachments'] = true;
                $recordcontext['attachments'] = $attachments;
            }

            $recordcontext['applytext'] = $record->applytext;
            $record->attachments = $attachments;

            if (count($attachments) > 0 || !empty($record->applytext)) {
                $recordcontext['hasapplication'] = true;
            }

            $output = $OUTPUT->render_from_template('enrol_gapply/applicationstatus', $recordcontext);

            // Add empty form to $output so that it looks like a form.
            $form = new enrol_gapply_emptyform(
                null,
                [
                    'instance' => $instance,
                    'output' => $output,
                ],
                'post',
                '',
                ['class' => 'enrolgapplyform']
            );

            // Pass strings to js.
            $PAGE->requires->strings_for_js([
                'cannotopenfile',
                'cannotopenpdffile',
                'download',
                'close',
            ], 'enrol_gapply');

            $return = html_writer::start_tag('div', ['class' => 'box py-3 generalbox']);
            $return .= $form->render();
            $return .= html_writer::end_tag('div');

            return $return;
        }

        // Check seats availability.
        $enrolledusers = count_enrolled_users(context_course::instance($instance->courseid), 'mod/assign:submit');
        $availableseats = $instance->customchar1;
        $allowoverenrol = $instance->customchar2;
        if ($availableseats > 0 && $enrolledusers >= $availableseats && !$allowoverenrol) {
            return $OUTPUT->render_from_template(
                'enrol_gapply/status',
                [
                    'full' => true,
                    'availableseats' => $availableseats,
                ]
            );
        }

        if ($instance->customint7 > 0 && $instance->customint7 > time()) {
            return $OUTPUT->render_from_template(
                'enrol_gapply/status',
                [
                    'notavailableyet' => true,
                    'time' => get_string('notavailableyet', 'enrol_gapply', userdate($instance->customint7)),
                ]
            );
        }

        if ($instance->customint8 > 0 && $instance->customint8 < time()) {
            return $OUTPUT->render_from_template(
                'enrol_gapply/status',
                [
                    'notavailableanymore' => true,
                    'time' => get_string(
                        'notavailableanymore',
                        'enrol_gapply',
                        userdate($instance->customint8)
                    ),
                ]
            );
        }

        // Check if the user meets profile field requirements.
        // Get the profilefields and loop through each one (if any) to see if the current user's profile field isn't empty.

        if (!empty($instance->customtext3)) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $profilefields = explode(",", $instance->customtext3);

            $corefields = [];
            $customfields = [];
            $missingfields = [];

            $profiledata = profile_user_record($USER->id);
            $fields = profile_get_custom_fields();

            // Filter fields that don't have "profile_field_" in them.
            foreach ($profilefields as $profilefield) {
                if (strpos($profilefield, "profile_field_") !== false) { // Handle custom fields.
                    $customfields[] = $profilefield;
                    $profilefield = str_replace("profile_field_", "", $profilefield);
                    // Get data type of custom field. If it's a checkbox, then we need to get the value differently.
                    $profilefielda = array_filter($fields, function ($var) use ($profilefield) {
                        return $var->shortname == $profilefield;
                    });
                    if (!empty($profilefielda)) {
                        $profilefielda = array_values($profilefielda)[0];
                        if ($profilefielda->datatype == "checkbox") {
                            $profilefieldvalue = $profiledata->$profilefield == 1 ?? '';
                        } else {
                            $profilefieldvalue = $profiledata->$profilefield;
                        }
                        if (empty($profilefieldvalue)) {
                            $missingfields[] = format_text($profilefielda->name, FORMAT_HTML);
                        }
                    }
                } else {
                    $corefields[] = $profilefield;
                    if ($profilefield == 'picture' && $USER->$profilefield == 0) {
                        $missingfields[] = get_string($profilefield);
                    } else if (empty($USER->$profilefield)) {
                        $missingfields[] = get_string($profilefield);
                    }
                }
            }

            if (!empty($missingfields)) {
                $profilerequirement = false;
            } else {
                $profilerequirement = true;
            }

            // Create a list of required fields.
            if (!$profilerequirement) {
                $contextdata = [];

                if (count($missingfields) >= 1) {
                    $contextdata['editprofile'] = new moodle_url(
                        "/user/edit.php",
                        ["id" => $USER->id, 'returnto' => $PAGE->url]
                    );
                }

                $contextdata['hasfields'] = true;
                $fields = [];
                foreach ($missingfields as $missingfield) {
                    $field = [];
                    $field['name'] = $missingfield;
                    $fields[] = $field;
                }
                $contextdata['fields'] = $fields;
                $contextdata['notready'] = true;

                return $OUTPUT->render_from_template('enrol_gapply/status', $contextdata);
            }
        }

        require_once($CFG->dirroot . '/enrol/gapply/enrol_form.php');
        $mform = new enrol_gapply_form(null, ['instance' => $instance], 'post', '', ['class' => 'enrolgapplyform']);

        if ($mform->is_cancelled()) {
            return false;
        } else if ($data = $mform->get_data()) {
            $data->applytext = isset($data->applytext) ? $data->applytext['text'] : '';
            $data->applytext = isset($data->applytext) ? $data->applytext['text'] : '';
            $data->format = 1;
            $data->status = 'new';
            $data->usermodified = $data->userid;
            $data->timemodified = time();
            $data->timecreated = time();

            $DB->insert_record('enrol_gapply', $data);

            $filecontext = context_course::instance($instance->courseid);

            // Save files.
            if (!empty($data->applyfile)) {
                file_save_draft_area_files(
                    $data->applyfile,
                    $filecontext->id,
                    'enrol_gapply',
                    'applyfile',
                    $instance->id . $data->userid,
                    [
                        'subdirs' => 0,
                        'maxbytes' => $instance->customint6,
                        'maxfiles' => $instance->customint5,
                    ]
                );
            }

            // Notify course contact (teachers) that a new application has been submitted.
            $coursecontacts = get_users_by_capability(
                $filecontext,
                'enrol/gapply:manage',
                'enrol/gapply:manage',
                '',
                'u.lastname ASC, u.firstname ASC',
                '',
                '',
                '',
                '',
                false,
                false
            );
            if ($coursecontacts) {
                $message = new stdClass();
                $course = $DB->get_record('course', ['id' => $instance->courseid]);
                $message->subject = get_string('newapplicationfor', 'enrol_gapply', format_text($course->fullname, FORMAT_HTML));
                $message->text = get_string('newapplicationtext', 'enrol_gapply', [
                    'coursefullname' => format_text($course->fullname, FORMAT_HTML),
                    'username' => fullname($USER),
                ]);
                $message->contexturl = new moodle_url('/enrol/gapply/manage.php', ['id' => $instance->id]);
                $message->contexturlname = get_string('manageapplications', 'enrol_gapply');
                foreach ($coursecontacts as $coursecontact) {
                    $this->send_notification($coursecontact, $USER, $message);
                }
            }

            redirect(new moodle_url('/enrol/index.php', ['id' => $instance->courseid]));
        } else {
            $output = $mform->render();

            return $OUTPUT->box($output);
        }
    }

    /**
     * Send notification to user
     * @param stdClass $user User to send notification to.
     * @param stdClass $userfrom User sending notification.
     * @param stdClass $msg Message to send.
     * @return bool
     */
    public function send_notification(stdClass $user, $userfrom, $msg = null) {
    public function send_notification(stdClass $user, $userfrom, $msg = null) {
        $message = new \core\message\message();
        $message->component = 'enrol_gapply';
        $message->name = 'gapply';
        $message->userfrom = $userfrom;
        $message->userto = $user;
        $message->subject = $msg->subject;
        $message->fullmessage = $msg->text . "\n" . $msg->contexturlname . ': ' . $msg->contexturl;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $msg->text . '<br><a href="' . $msg->contexturl . '">' . $msg->contexturlname . '</a>';
        $message->smallmessage = $msg->text;
        $message->notification = 1;
        $message->contexturl = $msg->contexturl;
        $message->contexturlname = $msg->contexturlname;
        $messageid = message_send($message);

        return true;
    }

    /**
     * The self enrollment plugin has several bulk operations that can be performed.
     * @param course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
    public function get_bulk_operations(course_enrolment_manager $manager) {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/self/locallib.php');
        $context = $manager->get_context();
        $bulkoperations = [];
        if (has_capability("enrol/gapply:manage", $context)) {
            $bulkoperations['editselectedusers'] = new enrol_gapply_editselectedusers_operation($manager, $this);
        }
        if (has_capability("enrol/gapply:unenrol", $context)) {
            $bulkoperations['deleteselectedusers'] = new enrol_gapply_deleteselectedusers_operation($manager, $this);
        }
        return $bulkoperations;
    }

    /**
     * Add a new instance of this enrolment plugin to the course.
     *
     * @param stdClass $course Course.
     * @param array $fields Form data.
     * @return int The id of the new instance.
     */
    public function add_instance($course, array $fields = null) {
    public function add_instance($course, array $fields = null) {
        // In the form we are representing 2 db columns with one field.
        if (!empty($fields) && !empty($fields['expirynotify'])) {
            if ($fields['expirynotify'] == 2) {
                $fields['expirynotify'] = 1;
                $fields['notifyall'] = 1;
            } else {
                $fields['notifyall'] = 0;
            }
        }

        $fields['customtext1'] = $fields['customtext1'] ? $fields['customtext1']['text'] : '';
        $fields['customtext1'] = $fields['customtext1'] ? $fields['customtext1']['text'] : '';
        $fields['customtext3'] = implode(',', $fields['customtext3']);
        return parent::add_instance($course, $fields);
    }

    /**
     * Update an instance of this enrolment plugin.
     *
     * @param stdClass $instance Enrollment instance.
     * @param stdClass $data Form data.
     * @return bool.
     */
    public function update_instance($instance, $data) {
    public function update_instance($instance, $data) {
        $data->customtext1 = $data->customtext1['text'];
        $data->customtext3 = implode(',', $data->customtext3);
        return parent::update_instance($instance, $data);
    }

    /**
     * Unenrol user from course,
     * the last unenrolment removes all remaining roles.
     *
     * @param stdClass $instance
     * @param int $userid
     * @return void
     */
    public function unenrol_user(stdClass $instance, $userid) {
    public function unenrol_user(stdClass $instance, $userid) {
        global $CFG, $USER, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $name = $this->get_name();
        $courseid = $instance->courseid;

        if ($instance->enrol !== $name) {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!$ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid])) {
            // Weird, user not enrolled.
            return;
        }

        // Remove all users groups linked to this enrolment instance.
        if ($gms = $DB->get_records(
            'groups_members',
            [
                'userid' => $userid,
                'component' => 'enrol_' . $name,
                'itemid' => $instance->id,
            ]
        )) {
            foreach ($gms as $gm) {
                groups_remove_member($gm->groupid, $gm->userid);
            }
        }

        role_unassign_all([
            'userid' => $userid,
            'contextid' => $context->id,
            'component' => 'enrol_' . $name,
            'itemid' => $instance->id,
        ]);
        $DB->delete_records('user_enrolments', ['id' => $ue->id]);

        // Add extra info and trigger event.
        $ue->courseid  = $courseid;
        $ue->enrol     = $name;

        $sql = "SELECT 'x'
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid)
                 WHERE ue.userid = :userid AND e.courseid = :courseid";
        if ($DB->record_exists_sql($sql, ['userid' => $userid, 'courseid' => $courseid])) {
            $ue->lastenrol = false;
        } else {
            // The big cleanup IS necessary!
            require_once("$CFG->libdir/gradelib.php");

            // Remove all remaining roles.
            role_unassign_all(['userid' => $userid, 'contextid' => $context->id], true, false);

            // Clean up ALL invisible user data from course if this is the last enrolment - groups, grades, etc.
            groups_delete_group_members($courseid, $userid);

            grade_user_unenrol($courseid, $userid);

            $DB->delete_records('user_lastaccess', ['userid' => $userid, 'courseid' => $courseid]);

            $ue->lastenrol = true; // Means user not enrolled any more.
        }
        // Trigger event.
        $event = \core\event\user_enrolment_deleted::create(
            [
                'courseid' => $courseid,
                'context' => $context,
                'relateduserid' => $ue->userid,
                'objectid' => $ue->id,
                'other' => [
                    'userenrolment' => (array)$ue,
                    'enrol' => $name,
                ],
            ]
        );
        $event->trigger();

        // User enrolments have changed, so mark user as dirty.
        mark_user_dirty($userid);

        // Check if courrse contacts cache needs to be cleared.
        core_course_category::user_enrolment_changed($courseid, $ue->userid, ENROL_USER_SUSPENDED);

        // Reset current user enrolment caching.
        if ($userid == $USER->id) {
            if (isset($USER->enrol['enrolled'][$courseid])) {
                unset($USER->enrol['enrolled'][$courseid]);
            }
            if (isset($USER->enrol['tempguest'][$courseid])) {
                unset($USER->enrol['tempguest'][$courseid]);
                remove_temp_course_roles($context);
            }
        }

        // Additional tasks for gapply.
        // Delete the user from the gapply table.
        $DB->delete_records('enrol_gapply', ['userid' => $userid, 'courseid' => $courseid]);
        // Delete any attached files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'enrol_gapply', 'applyfile', $instance->id . $userid);
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param stdClass $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();
        return $this->add_instance($course, $fields);
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $fields = [];
        $fields['name'] = get_string('pluginname', 'enrol_gapply');
        $fields['customtext1'] = [
            'text' => '',
        ];

        $fields['customtext3'] = [];
        $fields['customint1'] = 0;
        $fields['customint2'] = 0;

        return $fields;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param stdClass $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();
        return $this->add_instance($course, $fields);
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $fields = [];
        $fields['name'] = get_string('pluginname', 'enrol_gapply');
        $fields['customtext1'] = [
            'text' => '',
        ];

        $fields['customtext3'] = [];
        $fields['customint1'] = 0;
        $fields['customint2'] = 0;

        return $fields;
    }
}

/**
 * Handling plugin file
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args file arguments
 * @param bool $forcedownload whether force download
 * @param array $options other options
 * @return bool
 *
 **/
function enrol_gapply_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel == CONTEXT_COURSE && ($filearea === 'applyfile')) {
        $itemid = array_shift($args);
        $filename = array_pop($args);
        if (!$args) {
            $filepath = '/';
        } else {
            $filepath = '/' . implode('/', $args) . '/';
        }
        // Retrieve the file from the Files API.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'enrol_gapply', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false; // The file does not exist.
        }

        // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
        send_stored_file($file, null, 0, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Add 'Course ratings' to the course administration menu
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function enrol_gapply_extend_navigation_course(\navigation_node $navigation, \stdClass $course, \context $context) {
function enrol_gapply_extend_navigation_course(\navigation_node $navigation, \stdClass $course, \context $context) {
    // Get enrolment instance.
    if (!has_capability('enrol/gapply:manage', $context)) {
        return;
    }
    global $DB;
    $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'gapply', 'status' => 0], '*', IGNORE_MISSING);
    if (!$instance) {
        return;
    }
    $url = new moodle_url('/enrol/gapply/manage.php', ['id' => $instance->id, 'courseid' => $course->id]);
    $navigation->add(
        get_string('enrolmentapplications', 'enrol_gapply'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/report', '')
    );
}

require_once($CFG->libdir . '/formslib.php');
/**
 * Form for adding a new instance of the gapply enrolment plugin.
 */
class enrol_gapply_emptyform extends moodleform {
class enrol_gapply_emptyform extends moodleform {
    /**
     * Add elements to form.
     * @return void
     */
    public function definition() {
    public function definition() {
        $mform = $this->_form;
        $output = $this->_customdata['output'];
        $instance = $this->_customdata['instance'];
        $plugin = enrol_get_plugin('gapply');
        $heading = $plugin->get_instance_name($instance);
        if ($instance->name != null) {
            $heading = $instance->name;
        }

        $mform->addElement('header', 'heading', format_text($heading, FORMAT_HTML));

        $mform->addElement('html', $output);
    }
}

/**
 * Add loading div.
 */
function enrol_gapply_before_footer() {
function enrol_gapply_before_footer() {
    global $PAGE;
    // Check page id; if equal to page-enrol-gapply-manage then add loading.
    // Check page id; if equal to page-enrol-gapply-manage then add loading.
    if ($PAGE->bodyid == 'page-enrol-gapply-manage') {
        $loading = '<div id="enrol-gapply-loading" class="d-none align-items-center justify-content-center position-fixed w-100 h-100"
    style="top: 0;bottom: 0; left: 0; right: 0; z-index: 9999; background: rgba(0,0,0,0.5);">
    <div class="spinner-grow text-light" style="width: 3rem; height: 3rem;" role="status">
    <span class="sr-only">Loading...</span></div></div>';
        echo $loading;
    }
}
