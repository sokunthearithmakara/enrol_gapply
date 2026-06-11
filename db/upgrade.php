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
 * Plugin upgrade steps are defined here.
 *
 * @package     enrol_gapply
 * @category    upgrade
 * @copyright   2022 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Execute enrol_gapply upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_enrol_gapply_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    if ($oldversion < 2026050700) {
        // Define field outcomemessage to be added to enrol_gapply.
        $table = new xmldb_table('enrol_gapply');
        $field = new xmldb_field('outcomemessage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');

        // Conditionally launch add field outcomemessage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gapply savepoint reached.
        upgrade_plugin_savepoint(true, 2026050700, 'enrol', 'gapply');
    }

    if ($oldversion < 2026052900) {
        upgrade_plugin_savepoint(true, 2026052900, 'enrol', 'gapply');
    }

    if ($oldversion < 2026061100) {
        enrol_gapply_migrate_legacy_applyfile_itemids();

        upgrade_plugin_savepoint(true, 2026061100, 'enrol', 'gapply');
    }

    return true;
}
