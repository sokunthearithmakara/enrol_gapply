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
 * Plugin strings are defined here.
 *
 * @package     enrol_gapply
 * @category    string
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['messageprovider:enrolled'] = 'Enrolled';
$string['pluginname'] = 'Enrollment application';
$string['customtext'] = 'Custom text';
$string['applicationtext'] = 'Application text';
$string['applicationattachment'] = 'Application attachment';
$string['name'] = 'Name';
$string['description'] = 'Enrolment instructions';
$string['requireapplicationtext'] = 'Require application text';
$string['requireapplicationfile'] = 'Require application attachment';
$string['alreadyapplied'] = '<b>Enrolment application was successfully sent on {$a}.</b><br/>You will be notified when your enrolment has been reviewed.';
$string['apply'] = 'Apply';
$string['applications'] = 'Applications';
$string['manage'] = 'Manage';
$string['participants'] = 'Participants';
$string['application'] = 'Application';
$string['userdetails'] = 'Applicant';
$string['applicationdetails'] = 'Application details';
$string['noapplications'] = 'No applications to process';
$string['youhavebeenwaitlisted'] = '<b>You have been waitlisted.</b><br>You will be added to the course when a place becomes available, in which case you will be notified.';
$string['youhavebeenrejected'] = '<b>Your application has been rejected.</b><br>You will not be added to the course.';
$string['enrolmentapplications'] = 'Enrolment applications';
$string['id'] = 'ID';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['waitlist'] = 'Waitlist';
$string['delete'] = 'Delete';
$string['approved'] = 'Approved';
$string['rejected'] = 'Rejected';
$string['waitlisted'] = 'Waitlisted';
$string['deleted'] = 'Deleted';
$string['success'] = 'Applications have been successfully updated.';
$string['new'] = 'New';
$string['viewcourse'] = 'View course';
$string['applicationapproved'] = 'Your application for {$a} has been approved.';
$string['applicationwaitlist'] = 'Your application for {$a} has been waitlisted.';
$string['applicationreject'] = 'Your application for {$a} has been rejected.';
$string['manageapplications'] = 'Manage applications';
$string['messageprovider:gapply'] = 'Notifications for enrolment applications';
$string['areyousureyouwantto'] = 'Are you sure you want to {$a} the selected applications?';
$string['cancel'] = 'Cancel';
$string['proceed'] = 'Proceed';
$string['newapplicationfor'] = 'A new application for {$a}';
$string['newapplicationtext'] = 'A new application has been submitted for {$a->coursefullname} by {$a->username}.';
$string['gapply:config'] = 'Config enrol instances';
$string['gapply:manage'] = 'Manage enrollment applications';
$string['gapply:unenrol'] = 'Unenrol users from the course';
$string['gapply:unenrolself'] = 'Unenrol self from the course';
$string['lastaccess'] = 'Last access ';
$string['hidden'] = 'Hidden';
$string['close'] = 'Close';
$string['assigngroups'] = 'Assign groups';
$string['cannotopenfile'] = 'Unable to display file. Download instead.';
$string['cannotopenpdffile'] = 'Unable to display the PDF file on this device. Download instead.';
$string['datatableinfo'] = 'Showing _START_ to _END_ of _TOTAL_ entries';
$string['datatableinfoempty'] = 'Showing 0 to 0 of 0 entries';
$string['datatableinfofiltered'] = '(filtered from _MAX_ total entries)';
$string['nofound'] = 'Not found';
$string['previous'] = 'Previous';
$string['next'] = 'Next';
$string['first'] = 'First';
$string['last'] = 'Last';
$string['search'] = 'Search';
$string['download'] = 'Download';
$string['rowsselected'] = '%d rows selected';
$string['showapplicationtext'] = 'Show application text';
$string['showapplicationfile'] = 'Show application attachment';
$string['enrolment'] = 'Enrollment';
$string['applicationstartdate'] = 'Application start date';
$string['applicationenddate'] = 'Application end date';
$string['availability'] = 'Availability';
$string['notavailableanymore'] = 'Application deadline was {$a}. We are no longer accepting applications.';
$string['applicationnotavailable'] = 'Application is not available yet.';
$string['edit'] = 'Change settings';
$string['requiredfields'] = 'Required fields';
$string['editmyprofile'] = 'Edit my profile';
$string['profilefieldrequired'] = 'Complete your profile to apply for this course.';
$string['notavailableyet'] = 'Application start date is <b>{$a}</b>. Check back later.';
$string['applicationends'] = 'Application deadline: <b>{$a}</b>';
$string['maxattachmentnum'] = 'Maximum number of files allowed';
$string['maxattachmentsize'] = 'Maximum attachment size';
$string['acceptedfiletypes'] = 'Accepted file types';
$string['approveapplications'] = 'Approve applications';
$string['rejectapplications'] = 'Reject applications';
$string['waitlistapplications'] = 'Waitlist applications';
$string['deleteapplications'] = 'Delete applications';
$string['areyousureyouwanttodelete'] = 'Are you sure you want to delete the selected application(s)?';
$string['areyousureyouwanttoreject'] = 'Are you sure you want to reject the selected application(s)?';
$string['areyousureyouwanttowaitlist'] = 'Are you sure you want to waitlist the selected application(s)?';
$string['areyousureyouwanttoapprove'] = 'Are you sure you want to approve the selected application(s)?';
$string['enrollmentapproved'] = 'Your enrollment application has been approved. You will be able to access the course when it begins on {$a}.';
$string['cannotenrol'] = 'You cannot enrol in this course.';
$string['enrolmentexpired'] = 'Your enrolment in this course expired on {$a}.';
$string['enrolmentsuspended'] = 'Your enrolment in this course has been suspended.';
$string['profilefields'] = 'Profile fields';
$string['manageapplicationfor'] = 'Manage applications for {$a}';
$string['desc'] = 'Descending';
$string['asc'] = 'Ascending';
$string['timecreated'] = 'Time created';
$string['showuseridentity'] = 'Show user identity';
$string['showuseridentity_desc'] = 'By enabling this option, teachers will be able to specify which user fields are required for the application.';
$string['name_help'] = 'The name of the enrollment instance. If not specified, the name of the plugin will be used.';
$string['requireapplicationtext_help'] = 'If enabled, users will be required to provide a text description of their application.';
$string['requireapplicationfile_help'] = 'If enabled, users will be required to upload a file with their application.';
$string['showapplicationtext_help'] = 'If enabled, the application text box will be displayed to users, but it will not be required.';
$string['showapplicationfile_help'] = 'If enabled, the application file upload box will be displayed to users, but it will not be required.';
$string['applicationstartdate_help'] = 'The date when applications will be accepted.';
$string['applicationenddate_help'] = 'The date when applications will no longer be accepted.';
$string['profilefields_help'] = 'Select the user profile fields that will be required for the application. Users must complete these fields before they can apply for the course. These fields will be displayed on the application form to the reviewers.';
$string['enrolstartdate_help'] = 'The date when users will be able to access the course.';
$string['enrolenddate_help'] = 'The date when users will no longer be able to access the course.';
$string['enrolenddate'] = 'Enrollment end date';
$string['enrolstartdate'] = 'Enrollment start date';
$string['availableseats'] = 'Available seats';
$string['availableseats_help'] = 'The number of available seats for the course (regardless of enrollment methods). If the number of available seats is set to 0, the limit will be ignored.';
$string['fullseats'] = 'The available seats ({$a}) for this course have been filled. You can no longer apply for this course.';
$string['allowoverenrol'] = 'Allow applications after the seats are full';
$string['unlimitedseats'] = 'Unlimited';
$string['seatsinfo'] = 'Seats taken: {$a->enrolled} / {$a->seats}';