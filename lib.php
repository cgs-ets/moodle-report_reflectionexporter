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
 * Defines the APIs used by reflectionexporter report
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2021 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

function report_reflectionexporter_extend_navigation_course($navigation, $course, $context) {

    if (has_capability('moodle/site:viewuseridentity', $context)) {
        $url = new moodle_url('/report/reflectionexporter/index.php', array('cid' => $course->id, 'cmid' => $context->id));
        $navigation->add(get_string('pluginname', 'report_reflectionexporter'), $url, navigation_node::COURSE_INDEX_PAGE, null, null, new pix_icon('i/report', ''));
    }
}


/**
 * Serve the files from the MYPLUGIN file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function report_reflectionexporter_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }
   
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'attachment') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true);

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; 
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'report_reflectionexporter', 'attachment', $itemid, $filepath, $filename);
   
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function report_reflectionexporter_filemanager_prep($context) {
    global $CFG, $USER;
    $maxbytes = $CFG->maxbytes;
    if (empty($entry->id)) {
        $entry = new stdClass;
        $entry->id = 0;
    }
    $options = array(
        'subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => array('.pdf')
    );

    $entry = file_prepare_standard_filemanager(
        $entry,
        'attachment',
        $options,
        $context,
        'report_reflectionexporter',
        'attachment',
        $USER->id
    );

    return $entry;
}

function report_reflectionexporter_filemanager_postupdate($entry) {
    global $CFG, $USER;
    $maxbytes = $CFG->maxbytes;
    $options = array(
        'subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => array('.pdf')
    );
    $context = context_course::instance($entry->cid);
    $entry = file_postupdate_standard_filemanager(
        $entry,
        'attachment',
        $options,
        $context,
        'report_reflectionexporter',
        'attachment',
        $entry->rid
    );
}
