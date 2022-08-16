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
use ZipArchive;

class reflectionexportermanager {

    const STARTED = 'S';
    const FINISHED = 'F';
    const PDF_COMPLETED = 'C'; // Completed. Cant edit anymore

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
                FROM {assignsubmission_onlinetext} AS onlinetxt
                JOIN {assign_submission} AS asub 
                ON onlinetxt.submission = asub.id               
                WHERE asub.status = ? 
                AND asub.assignment IN ($assessids)
                AND asub.userid = ?;";

        $params = ['status' => 'submitted', 'userid' => $userid];
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
    public static function get_selected_students($studentids) {
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
            $data->id = $DB->insert_record('report_reflec_exporter_pdf', $data, true);
            unset($data->pdf); // Dont send it back
            $dataobjects[] = $data;
        }

        return $dataobjects;
    }

    // Update the PDF with the supervisor comment.
    public static function update_pdfbase64($pdfdata) {
        global $DB;

        $dataobject = new stdClass();
        $dataobject->id = $pdfdata->id;
        $dataobject->pdf = $pdfdata->pdf;
        $dataobject->status = reflectionexportermanager::PDF_COMPLETED;

        $DB->update_record('report_reflec_exporter_pdf', $dataobject);

        $data = new stdClass();
        $data->id = $pdfdata->refexid;
        $data->status = reflectionexportermanager::STARTED;

        $DB->update_record('report_reflectionexporter', $data);
        // We need to update the status of the process too.
    }

    public static function get_pdfbase64($rid) {
        global $DB;

        $sql  = "SELECT pdf FROM {report_reflec_exporter_pdf} WHERE id = ?";
        $params = ['id' => $rid];

        $r = $DB->get_record_sql($sql, $params);

        return $r->pdf;
    }

    public static function generate_zip($data) {
        global $DB, $CFG;
        $data = json_decode($data);
        error_log(print_r($data, true));
        // Increase the server timeout to handle the creation and sending of large zip files.
        \core_php_time_limit::raise();

        $userids     = implode(',', array_column($data, 'userid'));
        $exportids   = implode(',', array_column($data, 'id'));        // Id from mdl_report_reflec_exporter_pdf.
        $refexpids   = array_column($data, 'refexid');  // Id from mdl_report_reflectionexporter.
        $refexpids   = $refexpids[count($refexpids) - 1];
        $courseid   = array_column($data, 'refexid');  // Id from mdl_report_reflectionexporter.
        $courseid   = $courseid[count($courseid) - 1];


        $sql = "SELECT  exp.*, u.lastname, u.firstname
                FROM mdl_user as u 
                INNER JOIN mdl_report_reflec_exporter_pdf exp  
                ON u.id = exp.userid
                WHERE u.id in ($userids) AND exp.id in ($exportids ) AND exp.refexid = $refexpids";

        $results = $DB->get_records_sql($sql);

        // Prepare Tmp File for Zip archive
        $file = tempnam($CFG->tempdir, '/reflections');
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        foreach ($results as $result) {
            // COllect the pdfs
            $filename = strtoupper($result->lastname) . '_' . $result->firstname . '_EE_RPPF_.pdf';
            $zip->addFromString($filename, base64_decode($result->pdf));
        }

        // Close and send to users
        $zip->close();
        $foldername = date('Y') . ' EE RPPF.zip';
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename=" ' . $foldername . '"');
        header('Pragma: public');
        readfile($file);
        unlink($file);

        // Update status 

        reflectionexportermanager::update_download_status($refexpids);

        die();
    }

    public static function update_download_status($id) {
        global $DB;
        // Update status 
        $dataobject = new stdClass();
        $dataobject->id = $id;
        $dataobject->status = reflectionexportermanager::FINISHED;

        $DB->update_record('report_reflectionexporter', $dataobject);
    }

    public static function delete_process($rid) {
        global $DB;

        $r = $DB->delete_records('report_reflec_exporter_pdf', ['refexid' => $rid]);
        $r2 = $DB->delete_records('report_reflectionexporter', ['id' => $rid]);

        return $r == $r2;
    }

    // To display the table with the processes started but not finished
    public static function get_process() {
        global $DB, $COURSE;

        $sql = "SELECT * FROM mdl_report_reflectionexporter WHERE courseid = ?";
        $params = ['courseid' => $COURSE->id];

        $results = array_values($DB->get_records_sql($sql, $params));
        return $results;
    }

    // To fill the pdfjson property in the template
    public static function get_existing_proc($rid) {
        global $DB;
        $sql = "SELECT  id, userid, courseid, refexid, status
                FROM {report_reflec_exporter_pdf} where refexid = ?";
        $params = ['refexid' => $rid];

        $results = array_values($DB->get_records_sql($sql, $params));

        return $results;
    }

    // Returns student records based on the reflection export created.
    public static function get_students_from_export($rid) {
        global $DB;
        $record = json_decode((reflectionexportermanager::get_reflections_json($rid))->{'reflections_json'});
        $uids = array_column($record, 'uid');
        $uids = implode(',', $uids);

        $sql = "SELECT * FROM {user} WHERE id in ($uids)";
        $students = $DB->get_records_sql($sql);

        return $students;
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
            $us->uid = $user->id;
            $us->si = $data->supervisorinitials;
            $us->reflections = reflectionexportermanager::get_user_reflections($data->cid, $assessids, $user->id);

            $reflections[] = $us;
        }

        // Save the reflection data in the DB.
        $reflections = json_encode($reflections);
        $dataobject = new stdClass();
        $dataobject->reflections_json = $reflections;
        $dataobject->courseid = $data->courseid;
        $dataobject->timecreated = time();
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
