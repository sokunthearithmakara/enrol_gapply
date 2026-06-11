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

    $records = $DB->get_recordset_sql(
        "SELECT f.id AS fileid,
                f.contextid,
                f.component,
                f.filearea,
                f.itemid,
                f.filepath,
                f.filename,
                g.id AS applicationid,
                g.instance,
                g.userid
           FROM {files} f
           JOIN {context} ctx ON ctx.id = f.contextid
           JOIN {enrol_gapply} g ON g.courseid = ctx.instanceid
          WHERE ctx.contextlevel = :contextlevel
                AND f.component = :component
                AND f.filearea = :filearea
                AND NOT EXISTS (
                    SELECT 1
                      FROM {enrol_gapply} g2
                     WHERE g2.courseid = g.courseid
                           AND g2.id <> g.id
                           AND CAST(CONCAT(g2.instance, g2.userid) AS DECIMAL(20,0)) = f.itemid
                )",
        [
            'contextlevel' => CONTEXT_COURSE,
            'component' => 'enrol_gapply',
            'filearea' => 'applyfile',
        ]
    );

    foreach ($records as $record) {
        $legacyitemid = (string) $record->instance . (string) $record->userid;
        if ((string) $record->itemid !== $legacyitemid || (int) $record->itemid === (int) $record->applicationid) {
            continue;
        }

        $pathnamehash = \file_storage::get_pathname_hash(
            $record->contextid,
            $record->component,
            $record->filearea,
            $record->applicationid,
            $record->filepath,
            $record->filename
        );

        if ($DB->record_exists('files', ['pathnamehash' => $pathnamehash])) {
            continue;
        }

        $DB->update_record('files', (object) [
            'id' => $record->fileid,
            'itemid' => $record->applicationid,
            'pathnamehash' => $pathnamehash,
        ]);
    }

    $records->close();
}
