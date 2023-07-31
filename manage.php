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
 * Manage applications for a course.
 *
 * @package     enrol_gapply
 * @author      2023 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @copyright   2023 Sokunthearith Makara
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$id = required_param('id', PARAM_INT);

$tab = optional_param('tab', 'new', PARAM_ALPHA);

$instance = $DB->get_record('enrol', array('id' => $id, 'enrol' => 'gapply'), '*', MUST_EXIST);
if (!$instance) {
    redirect(new moodle_url('/course/view.php', array('id' => $instance->courseid)));
}

require_course_login($instance->courseid);
$course = get_course($instance->courseid);

$pageheading = $course->fullname;
$coursecontext = context_course::instance($course->id);
require_capability('enrol/gapply:manage', $coursecontext);
$PAGE->set_url(new moodle_url('/enrol/gapply/manage.php', array('id' => $id, 'tab' => $tab)));
$PAGE->set_context($coursecontext);
$PAGE->set_title(get_string('manageapplicationfor', 'enrol_gapply', $pageheading));
$PAGE->set_heading($pageheading);

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('participants', 'enrol_gapply'), new moodle_url('/user/index.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('applications', 'enrol_gapply'), new moodle_url('/enrol/gapply/manage.php', array('id' => $id)));
$PAGE->navbar->add(get_string('manage', 'enrol_gapply'));

$stringman = get_string_manager();
$strings = $stringman->load_component_strings('enrol_gapply', current_language());
$PAGE->requires->strings_for_js(array_keys($strings), 'enrol_gapply');

$PAGE->set_pagelayout('incourse');

// Create tabs.
$tabs = array();
// New Applications.
$tabs[] = new tabobject('new',
new moodle_url('/enrol/gapply/manage.php',
array('id' => $id, 'tab' => 'new')), get_string('new', 'enrol_gapply'));
// Approved Applications.
$tabs[] = new tabobject('approved',
new moodle_url('/enrol/gapply/manage.php',
array('id' => $id, 'tab' => 'approved')), get_string('approved', 'enrol_gapply'));
// Waitlisted Applications.
$tabs[] = new tabobject('waitlisted',
new moodle_url('/enrol/gapply/manage.php',
array('id' => $id, 'tab' => 'waitlisted')), get_string('waitlisted', 'enrol_gapply'));
// Rejected Applications.
$tabs[] = new tabobject('rejected',
new moodle_url('/enrol/gapply/manage.php',
array('id' => $id, 'tab' => 'rejected')), get_string('rejected', 'enrol_gapply'));
// Edit instance.
$tabs[] = new tabobject('edit',
new moodle_url('/enrol/editinstance.php',
array('id' => $id, 'courseid' => $instance->courseid, "type" => 'gapply')),
'<i class="fa fa-cog mr-2"></i>' . get_string('edit', 'enrol_gapply'));
$content = '';

// Get records from enrol_gapply table where 'instance' = $id and 'status' is not 'approved'.
$sql = "SELECT * FROM {enrol_gapply} WHERE instance = ? AND status = ?";
$records = $DB->get_records_sql($sql, array($id, $tab));
$fs = get_file_storage();
$content = html_writer::tag('p', get_string('noapplications', 'enrol_gapply'));
if ($records) {
    $table = new html_table();

    $table->id = 'gapplytable';
    $table->attributes['data-instance'] = $id;

    $table->attributes['class'] = 'table table-striped table-hover d-none generaltable w-100 ' . $tab;

    $table->head = array(
        '<input type="checkbox" id="selectall" class="selectall" />',
        get_string('id', 'enrol_gapply'),
        get_string('userdetails', 'enrol_gapply'),
    );

    $table->colclasses = array(
        'checkbox',
        'id inv',
        'userdetails text-truncate noorder position-sticky',
    );

    $table->align = array(
        'center',
        'left',
        'left',
    );

    // Table head must follow the setting enrol_gapply/showuseridentity.
    $showuseridentity = explode(',', ('firstname,lastname,' . $instance->customtext3));
    // Remove "picture" from the list of fields to show.
    $showuseridentity = array_diff($showuseridentity, array('picture'));
    foreach ($showuseridentity as $field) {
        if (strpos($field, 'profile_field_') !== false) {
            $table->head[] = \core_user\fields::get_display_name($field);
        } else {
            $table->head[] = get_string($field);
        }
        $table->colclasses[] = $field . ' exportable colvis inv profilefield';
        $table->align[] = 'left';
    }

    $table->head = array_merge($table->head, array(
        get_string('applicationdetails', 'enrol_gapply'),
        get_string('applicationtext', 'enrol_gapply'),
        get_string('applicationattachment', 'enrol_gapply'),
        get_string('status'),
        get_string('date'),
        get_string('timecreated'),
        get_string('action')
    ));

    $table->colclasses = array_merge($table->colclasses, array(
        'applicationdetails  noorder',
        'applicationtext inv exportable',
        'applicationattachment inv exportable',
        'status inv exportable',
        'date exportable noorder',
        'timecreated inv',
        'action noorder' . ($tab == 'approved' ? ' inv' : '')
    ));

    $table->align = array_merge($table->align, array(
        'left',
        'left',
        'left',
        'left',
        'left',
        'left',
        'right'
    ));

    $table->data = array();
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/enrol/gapply/libraries/DataTables/datatables.min.css'));
    $content = html_writer::table($table);
    $PAGE->requires->js_call_amd('enrol_gapply/custom', 'init', [$tab, $id]);
}

ob_start();
print_tabs(array($tabs), $tab);
$tabmenu = ob_get_contents();
ob_end_clean();

echo $OUTPUT->header();

echo $tabmenu;
echo $content;

echo $OUTPUT->footer();
