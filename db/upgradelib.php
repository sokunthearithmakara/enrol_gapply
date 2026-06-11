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
 * Plugin upgrade helper functions are defined here.
 *
 * @package     enrol_gapply
 * @category    upgrade
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Migrate application attachments from the legacy instance/user itemid to the application id.
 */
function enrol_gapply_migrate_legacy_applyfile_itemids() {
    global $CFG, $DB;

    require_once($CFG->libdir . '/filestorage/file_storage.php');

    $legacyitemids = [];
    $applications = $DB->get_recordset('enrol_gapply', null, '', 'id, courseid, instance, userid');
    foreach ($applications as $application) {
        $courseid = (int) $application->courseid;
        $legacyitemid = (string) $application->instance . (string) $application->userid;

        if (!isset($legacyitemids[$courseid])) {
            $legacyitemids[$courseid] = [];
        }

        if (array_key_exists($legacyitemid, $legacyitemids[$courseid])) {
            $legacyitemids[$courseid][$legacyitemid] = false;
        } else {
            $legacyitemids[$courseid][$legacyitemid] = (int) $application->id;
        }
    }
    $applications->close();

    $files = $DB->get_recordset_sql(
        "SELECT f.id AS fileid,
                f.contextid,
                f.component,
                f.filearea,
                f.itemid,
                f.filepath,
                f.filename,
                ctx.instanceid AS courseid
           FROM {files} f
           JOIN {context} ctx ON ctx.id = f.contextid
          WHERE ctx.contextlevel = :contextlevel
                AND f.component = :component
                AND f.filearea = :filearea",
        [
            'contextlevel' => CONTEXT_COURSE,
            'component' => 'enrol_gapply',
            'filearea' => 'applyfile',
        ]
    );

    foreach ($files as $file) {
        $courseid = (int) $file->courseid;
        $legacyitemid = (string) $file->itemid;
        if (empty($legacyitemids[$courseid]) || !array_key_exists($legacyitemid, $legacyitemids[$courseid])) {
            continue;
        }

        $applicationid = $legacyitemids[$courseid][$legacyitemid];
        if ($applicationid === false || (int) $file->itemid === $applicationid) {
            continue;
        }

        $pathnamehash = \file_storage::get_pathname_hash(
            $file->contextid,
            $file->component,
            $file->filearea,
            $applicationid,
            $file->filepath,
            $file->filename
        );

        if ($DB->record_exists('files', ['pathnamehash' => $pathnamehash])) {
            continue;
        }

        $DB->update_record('files', (object) [
            'id' => $file->fileid,
            'itemid' => $applicationid,
            'pathnamehash' => $pathnamehash,
        ]);
    }

    $files->close();
}
