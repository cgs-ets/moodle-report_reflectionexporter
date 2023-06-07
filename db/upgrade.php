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
 * Upgrade plugin
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

function xmldb_report_reflectionexporter_upgrade($oldversion=0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023060500) {

        // Define field formname to be added to report_reflec_exporter_pdf.
        $table = new xmldb_table('report_reflec_exporter_pdf');
        $field = new xmldb_field('formname', XMLDB_TYPE_CHAR, '10', null, null, null, 'EE_RPPF', 'pdf');
        // Conditionally launch add field formname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reflectionexporter savepoint reached.
        upgrade_plugin_savepoint(true, 2023060500, 'report', 'reflectionexporter');
    }

    if ($oldversion < 2023060601) {

        // Define field formname to be added to report_reflectionexporter.
        $table = new xmldb_table('report_reflectionexporter');
        $field = new xmldb_field('formname', XMLDB_TYPE_CHAR, '10', null, null, null, 'EE_RPPF', 'status');

        // Conditionally launch add field formname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reflection savepoint reached.
        upgrade_plugin_savepoint(true, 2023060601, 'report', 'reflectionexporter');
    }

}
