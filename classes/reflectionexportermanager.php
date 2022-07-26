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
 *
 * @package    report_reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_reflectionexporter;

use context_course;
use moodle_url;
use stdClass;

class reflectionexportermanager {


    // Get the course's assessments that have submissions and the submission type is onlinetext.
    public static function get_submitted_assessments($courseid) {
        global $DB;

        $sql = "SELECT distinct assign.id, assign.name AS 'assignmentname' FROM {assign} as assign 
                JOIN {assign_submission} as asub 
                ON assign.id = asub.assignment
                JOIN {assignsubmission_onlinetext} as onlinetxt ON assign.id = onlinetxt.assignment
                WHERE assign.course = ? AND asub.status = ?";

        $params = ['course' => $courseid, 'status' => 'submitted'];

        $results = $DB->get_records_sql($sql, $params);

        return $results;
    }

    // Get the reflections the student submitted.
    public static function get_user_reflections($courseid, $assessids, $userid) {
        global $DB;

        $sql = "SELECT onlinetxt.*, asub.timemodified AS 'month', asub.userid
                FROM {assign} AS assign 
                JOIN {assign_submission} AS asub 
                ON assign.id = asub.assignment
                JOIN {assignsubmission_onlinetext} AS onlinetxt 
                ON assign.id = onlinetxt.assignment
                WHERE assign.course = ? 
                AND asub.status = ? 
                AND asub.assignment IN ( $assessids)
                AND asub.userid = ?;";

        $params = ['course' => $courseid, 'status' => 'submitted', 'userid' => $userid];
        $results = $DB->get_records_sql($sql, $params);
        $context = $context = context_course::instance($courseid);
        // Format the text to keep new lines. If the student added images, process the URL to avoid warning. 
        // The image wont be seen in the PDF.
        foreach ($results as $r) {

            $onlinetext = file_rewrite_pluginfile_urls($r->onlinetext, 'pluginfile.php', $context->id, 'assignsubmission_onlinetext', 'submissions_onlinetext', $r->id);
            $r->onlinetext =  json_encode(strip_tags(format_text($onlinetext, FORMAT_MARKDOWN)), JSON_HEX_QUOT | JSON_HEX_TAG);
            $r->month = date('F', $r->month);
        }

        $results = array_values($results);

        return $results;
    }

    // Get the reflections_json column.
    public static function get_reflections_json($rid) {
        global $DB;
        $sql = "SELECT reflections_json FROM {report_reflectionexporter} WHERE id = ?";
        $params = ['id' => $rid];

        $result = $DB->get_record_sql($sql, $params);

        return $result;
    }

    // Get the details of students selected in the form.
    private static function get_selected_students($studentids) {
        global $DB;

        $ids = implode(',', $studentids);

        $sql = "SELECT * FROM {user} WHERE id in ($ids)";

        $results = $DB->get_records_sql($sql);

        return $results;
    }
    // Save the PDF filled with the students reflections.
    public static function save_pdfbase64($pdfs) {
        global $DB;
        $pdfs = json_decode($pdfs);
        $dataobjects = [];
      
        foreach ($pdfs as $pdf) {
            $data = new stdClass();
            $data->userid = $pdf->uid;
            $data->courseid = $pdf->courseid;
            $data->refexid = $pdf->rid; // id o mdl_report_reflectionexporter
            $data->pdf = $pdf->pdf;
            $dataobjects[] = $data;
        }

        $DB->insert_records('report_reflec_exporter_pdf', $dataobjects);
    }


  
    // Collect the reflections the student selected in the form did.
    // Save data in report_reflectionexporter table.
    public static function collect_and_save_reflections($data) {
        global $DB;

        $users = reflectionexportermanager::get_selected_students($data->userid);
        $reflections = [];

        foreach ($users as $user) {
            $assessids = implode(',', $data->assessments);
            profile_load_custom_fields($user);

            $us = new stdClass();
            $us->id = $user->profile['IBCode']; // Personal code.
            $us->dp = $user->profile['Year'] == '11' ? 1 : '2';
            $us->firstname = $user->firstname;
            $us->lastname = $user->lastname;
            $us->si = $data->supervisorinitials;
            $us->reflections = reflectionexportermanager::get_user_reflections($data->cid, $assessids, $user->id);

            $reflections[] = $us;
        }

        // Save the reflection data in the DB.
        $reflections = json_encode($reflections);
        $dataobject = new stdClass();
        $dataobject->reflections_json = $reflections;
        $rid = $DB->insert_record('report_reflectionexporter', $dataobject);

        return $rid;
    }

    // Get the pdf the user submitted in the form.
    public static function get_file_url($context, $rid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'report_reflectionexporter', 'attachment', $rid);
        foreach ($files as $f) {
            $filename = $f->get_filename();
        }
        $url = moodle_url::make_pluginfile_url($context->id, 'report_reflectionexporter', 'attachment', $rid, '/', $filename, false);

        return $url->__toString();
    }

    // Get the active users in the course.
    public static function get_active_users($courseid) {
        $context = context_course::instance($courseid);
        return  get_enrolled_users(
            $context,
            "mod/assign:submit",
            null,
            'u.*',
            'firstname',
            null,
            null,
            true
        );
    }
}
