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

$string['acceptedfiletypes'] = 'Accepted file types';
$string['add'] = 'Add';
$string['addtogroup'] = 'Add to group...';
$string['allowoverenrol'] = 'Allow applications after the seats are full';
$string['allowwithdrawal'] = 'Allow applicants to withdraw their unprocessed application.';
$string['alreadyapplied'] = '<b>Your application was successfully sent on {$a}.</b><br/>You will be notified when your enrollment has been reviewed.';
$string['anerroroccurred'] = 'An error occurred. Please try again.';
$string['application'] = 'Application';
$string['applicationapproved'] = 'Your application for {$a} has been approved.';
$string['applicationattachment'] = 'Application attachment';
$string['applicationdetails'] = 'Application details';
$string['applicationenddate'] = 'Application end date';
$string['applicationenddate_help'] = 'The date when applications will no longer be accepted.';
$string['applicationends'] = 'Application deadline: <b>{$a}</b>';
$string['applicationid'] = 'Application ID';
$string['applicationnotavailable'] = 'Application is not available yet.';
$string['applicationreject'] = 'Your application for {$a} has been rejected.';
$string['applications'] = 'Applications';
$string['applicationstartdate'] = 'Application start date';
$string['applicationstartdate_help'] = 'The date when applications will be accepted.';
$string['applicationtext'] = 'Application text';
$string['applicationwaitlist'] = 'Your application for {$a} has been waitlisted.';
$string['applicationwithdrawn'] = 'Application withdrawn';
$string['applicationwithdrawnsuccess'] = 'Your application has been successfully withdrawn.';
$string['applicationwithdrawntext'] = 'The application for the course "{$a->coursefullname}" has been withdrawn by {$a->username}. <br><br> Reason: {$a->reason}';
$string['appliedon'] = 'Applied on';
$string['apply'] = 'Apply';
$string['approve'] = 'Approve';
$string['approveapplication'] = 'Approve application';
$string['approveapplications'] = 'Approve applications';
$string['approved'] = 'Approved';
$string['approvesuccess'] = 'The application has been successfully approved.';
$string['approvesuccess_bulk'] = '{$a} applications have been successfully approved.';
$string['areyousureyouwantto'] = 'Are you sure you want to {$a} the selected applications?';
$string['areyousureyouwanttoapprove'] = 'Are you sure you want to approve this application?';
$string['areyousureyouwanttoapprove_bulk'] = 'Are you sure you want to approve the {$a} selected applications?';
$string['areyousureyouwanttodelete'] = 'Are you sure you want to delete this application?';
$string['areyousureyouwanttodelete_bulk'] = 'Are you sure you want to delete the {$a} selected applications?';
$string['areyousureyouwanttoreject'] = 'Are you sure you want to reject this application?';
$string['areyousureyouwanttoreject_bulk'] = 'Are you sure you want to reject the {$a} selected applications?';
$string['areyousureyouwanttowaitlist'] = 'Are you sure you want to waitlist this application?';
$string['areyousureyouwanttowaitlist_bulk'] = 'Are you sure you want to waitlist the {$a} selected applications?';
$string['asc'] = 'Ascending';
$string['assigngroups'] = 'Assign groups';
$string['assignrole'] = 'Assign role';
$string['availability'] = 'Availability';
$string['availableseats'] = 'Available seats';
$string['availableseats_help'] = 'The number of available seats for the course (regardless of enrollment methods). If the number of available seats is set to 0, the limit will be ignored.';
$string['availabletags'] = 'Available tags: {$a}';
$string['by'] = 'by';
$string['cancel'] = 'Cancel';
$string['cannotenrol'] = 'You cannot enroll in this course.';
$string['cannotopenfile'] = 'Unable to display file. Download instead.';
$string['cannotopenpdffile'] = 'Unable to display the PDF file on this device. Download instead.';
$string['close'] = 'Close';
$string['confirmcreation'] = 'Confirm creation';
$string['create'] = 'Create';
$string['createnewgroup'] = 'Create new group';
$string['creating'] = 'Creating...';
$string['customtext'] = 'Custom text';
$string['datatableinfo'] = 'Showing _START_ to _END_ of _TOTAL_ entries';
$string['datatableinfoempty'] = 'Showing 0 to 0 of 0 entries';
$string['datatableinfofiltered'] = '(filtered from _MAX_ total entries)';
$string['defaultrole'] = 'Default role';
$string['delete'] = 'Delete';
$string['deleteapplication'] = 'Delete application';
$string['deleteapplications'] = 'Delete applications';
$string['deleted'] = 'Deleted';
$string['deletesuccess'] = 'The application has been successfully deleted.';
$string['deletesuccess_bulk'] = '{$a} applications have been successfully deleted.';
$string['desc'] = 'Descending';
$string['description'] = 'Enrollment instructions';
$string['download'] = 'Download';
$string['edit'] = 'Change settings';
$string['editmyprofile'] = 'Edit my profile';
$string['enddate'] = 'Ending on';
$string['enrolenddate'] = 'Enrollment end date';
$string['enrolenddate_help'] = 'The date when users will no longer be able to access the course.';
$string['enrollmentapproved'] = 'Your enrollment application has been approved. You will be able to access the course when it begins on {$a}.';
$string['enrolment'] = 'Enrollment';
$string['enrolmentapplications'] = 'Enrollment applications';
$string['enrolmentexpired'] = 'Your enrollment in this course expired on {$a}.';
$string['enrolmentsuspended'] = 'Your enrollment in this course has been suspended.';
$string['enrolstartdate'] = 'Enrollment start date';
$string['enrolstartdate_help'] = 'The date when users will be able to access the course.';
$string['entergroupname'] = 'Enter group name';
$string['entergroupnameerror'] = 'Please enter a group name.';
$string['error'] = 'Error';
$string['first'] = 'First';
$string['fullseats'] = 'The available seats ({$a}) for this course have been filled. You can no longer apply for this course.';
$string['gapply:config'] = 'Config enroll instances';
$string['gapply:manage'] = 'Manage enrollment applications';
$string['gapply:unenrol'] = 'Unenrol users from the course';
$string['gapply:unenrolself'] = 'Unenrol self from the course';
$string['groupingoptional'] = 'Grouping (Optional)';
$string['groupname'] = 'Group name';
$string['groupselectedusers'] = 'Group selected users';
$string['groupsettings'] = 'Group settings';
$string['hidden'] = 'Hidden';
$string['id'] = 'ID';
$string['item'] = 'item';
$string['items'] = 'items';
$string['last'] = 'Last';
$string['lastaccess'] = 'Last access ';
$string['manage'] = 'Manage';
$string['manageapplicationfor'] = 'Manage applications for {$a}';
$string['manageapplications'] = 'Manage applications';
$string['maxattachmentnum'] = 'Maximum number of files allowed';
$string['maxattachmentsize'] = 'Maximum attachment size';
$string['messageprovider:enrolled'] = 'Enrolled';
$string['messageprovider:gapply'] = 'Notifications for enrollment applications';
$string['modifiedon'] = 'Modified on';
$string['more'] = 'More';
$string['name'] = 'Name';
$string['name_help'] = 'The name of the enrollment instance. If not specified, the name of the plugin will be used.';
$string['new'] = 'New';
$string['newapplicationfor'] = 'A new application for {$a}';
$string['newapplicationtext'] = 'A new application has been submitted for {$a->coursefullname} by {$a->username}.';
$string['next'] = 'Next';
$string['noapplications'] = 'No applications to process';
$string['noapplicationtext'] = 'No application text provided.';
$string['noattachments'] = 'No attachments';
$string['nofound'] = 'Not found';
$string['nogrouping'] = 'No grouping';
$string['nomorerecords'] = 'No more records in this direction.';
$string['notavailableanymore'] = 'Application deadline was {$a}. We are no longer accepting applications.';
$string['notavailableyet'] = 'Application start date is <b>{$a}</b>. Check back later.';
$string['notifications'] = 'Notifications';
$string['notifyusers'] = 'Notify users';
$string['notifyusers_help'] = 'Select the users that will be notified when an application is submitted. If not selected, notifications will be sent to the course contact roles.';
$string['notprovided'] = 'Not provided';
$string['outcomemessage'] = 'Outcome message';
$string['outcomemessage_help'] = 'This message will be sent to the applicant.';
$string['participants'] = 'Participants';
$string['pluginname'] = 'Enrollment application';
$string['previous'] = 'Previous';
$string['proceed'] = 'Proceed';
$string['profilefieldrequired'] = 'Complete your profile to apply for this course.';
$string['profilefields'] = 'Profile fields';
$string['profilefields_help'] = 'Select the user profile fields that will be required for the application. Users must complete these fields before they can apply for the course. These fields will be displayed on the application form to the reviewers.';
$string['reject'] = 'Reject';
$string['rejectapplication'] = 'Reject application';
$string['rejectapplications'] = 'Reject applications';
$string['rejected'] = 'Rejected';
$string['rejectsuccess'] = 'The application has been successfully rejected.';
$string['rejectsuccess_bulk'] = '{$a} applications have been successfully rejected.';
$string['remove'] = 'Remove';
$string['removefromgroup'] = 'Remove from group...';
$string['requireapplicationfile'] = 'Require application attachment';
$string['requireapplicationfile_help'] = 'If enabled, users will be required to upload a file with their application.';
$string['requireapplicationtext'] = 'Require application text';
$string['requireapplicationtext_help'] = 'If enabled, users will be required to provide a text description of their application.';
$string['requiredfields'] = 'Required fields';
$string['rowsselected'] = '%d rows selected';
$string['search'] = 'Search';
$string['seatsinfo'] = '{$a->enrolled} / {$a->seats}';
$string['selectfiletopreview'] = 'Select a file to preview';
$string['selectgroup'] = 'Please select at least one group.';
$string['selectgroups'] = 'Select groups';
$string['selectparticipant'] = 'Please select at least one participant.';
$string['sendnotificationinrecipientlang'] = 'Send notification in recipient language';
$string['sendnotificationinrecipientlang_desc'] = 'If enabled, the notification will be sent in the recipient\'s preferred language.';
$string['showapplicationfile'] = 'Show application attachment';
$string['showapplicationfile_help'] = 'If enabled, the application file upload box will be displayed to users, but it will not be required.';
$string['showapplicationtext'] = 'Show application text';
$string['showapplicationtext_help'] = 'If enabled, the application text box will be displayed to users, but it will not be required.';
$string['showuseridentity'] = 'Show user identity';
$string['showuseridentity_desc'] = 'By enabling this option, teachers will be able to specify which user fields are required for the application.';
$string['startdate'] = 'Starting from';
$string['success'] = 'Applications have been successfully updated.';
$string['timecreated'] = 'Time created';
$string['unlimitedseats'] = 'Unlimited';
$string['userdetails'] = 'Applicant';
$string['usernotfound'] = 'User not found';
$string['viewcourse'] = 'View course';
$string['waitlist'] = 'Waitlist';
$string['waitlistapplication'] = 'Waitlist application';
$string['waitlistapplications'] = 'Waitlist applications';
$string['waitlisted'] = 'Waitlisted';
$string['waitlistsuccess'] = 'The application has been successfully waitlisted.';
$string['waitlistsuccess_bulk'] = '{$a} applications have been successfully waitlisted.';
$string['withdraw'] = 'Withdraw';
$string['withdrawalreason'] = 'Reason for withdrawal';
$string['withdrawalsuccess'] = 'Application successfully withdrawn.';
$string['withdrawapplication'] = 'Withdraw application';
$string['withdrawapplicationconfirm'] = 'Note: All associated files and details will be removed. You still can re-submit a new application if the application is still open.';
$string['withdrawmyapplication'] = 'Withdraw my application';
$string['youhavebeenrejected'] = '<b>Your application has been rejected.</b><br>You will not be added to the course.';
$string['youhavebeenwaitlisted'] = '<b>You have been waitlisted.</b><br>You will be added to the course when a place becomes available, in which case you will be notified.';
