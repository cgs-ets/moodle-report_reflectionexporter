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
 * Exports an Excel spreadsheet  for TOK form
 *
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_reflectionexporter\wordcount;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/excellib.class.php');
require_once($CFG->dirroot . '/report/reflectionexporter/classes/spreadsheetmanager.php');


use MoodleExcelWorkbook;
use MoodleExcelWorksheet;
use SpreadsheetWorkbook;

const HEADINGSROW = 4;
const HEADINGTITLES = [
    'size' => 12,
    'bold' => 1,
    'text_wrap' => true,
    'align' => 'centre'
];

const HEADINGSUBTITLES = [
    'bold' => 1,
    'text_wrap' => true,
    'align' => 'fill'
];

function report_reflectionexporter_setup_wordcount_workbook($context, $studentsdata, $tempdir, $course) {
    global $DB, $COURSE;

    require_capability('mod/assign:grade', $context);


    $filename       = $course->shortname . ' -TheoryOfKnowledgeWordCount'  . '.xls';
    $workbook       = new SpreadsheetWorkbook("-");

    $workbook->send($filename);

    $sheet          = $workbook->add_worksheet($course->fullname);
    $pos            = report_reflectionexporter_add_header($workbook,
                                                           $sheet,
                                                           $course->fullname,
                                                           get_string('tokessay', 'report_reflectionexporter')
                                                        );
    $pos            = report_reflectionexporter_add_info_header($workbook, $sheet, $pos);

    if (count($studentsdata) > 0 ) {
        report_reflectionexporter_set_students_rows($sheet, $studentsdata, $pos);
        $workbook->savetotempdir($tempdir);
    }

}

function report_reflectionexporter_add_header(MoodleExcelWorkbook $workbook, MoodleExcelWorksheet $sheet, $coursename, $modname) {

    $format = $workbook->add_format(array('size' => 18, 'bold' => 1));
    $sheet->write_string(0, 0, $coursename, $format);
    $sheet->set_row(0, 24, $format);
    $format = $workbook->add_format(array('size' => 16, 'bold' => 1));
    $sheet->write_string(1, 0, $modname, $format);
    $sheet->set_row(1, 21, $format);

    // Column headers - two rows for grouping.
    $format = $workbook->add_format(HEADINGTITLES);
    $format2 = $workbook->add_format(HEADINGSUBTITLES);

    $sheet->write_string(HEADINGSROW, 0, get_string('student', 'report_reflectionexporter'), $format);
    $sheet->merge_cells(HEADINGSROW, 0, HEADINGSROW, 1, $format); // Student section.
    $col = 0;
    $sheet->write_string(5, $col++, get_string('firstname', 'report_reflectionexporter'), $format2);
    $sheet->write_string(5, $col++, get_string('lastname', 'report_reflectionexporter'), $format2);
    $sheet->set_column(0, $col, 10); // Set column widths to 10.

    return $col;
}

function report_reflectionexporter_add_info_header(MoodleExcelWorkbook $workbook,
                                                            MoodleExcelWorksheet $sheet,
                                                            $pos) {
    $format     = $workbook->add_format(HEADINGTITLES);
    $format2    = $workbook->add_format(HEADINGSUBTITLES);

    $sheet->set_row(4, 30, $format);
    $sheet->write_string(5, $pos++, get_string('prescribedtitle', 'report_reflectionexporter'), $format2);
    $sheet->set_column($pos - 1, $pos, 30); // Set column widths to 20.
    $sheet->write_string(5, $pos++, get_string('wordcount', 'report_reflectionexporter'), $format2);
    $sheet->set_column($pos - 1, $pos, 20); // Set column widths to 20.

    return $pos;
}

/**
 * Fill the rows with the student info
 */
function report_reflectionexporter_set_students_rows (MoodleExcelWorksheet $sheet, $studentsdata) {
    $row = 5;
    $format = ['text_wrap' => true, 'align' => 'top'];

    foreach ($studentsdata as $studentdata) {
        $col = 0;
        $row++;
        $sheet->write_string($row, $col++, $studentdata->firstname, $format);
        $sheet->write_string($row, $col++, $studentdata->lastname, $format);
        $sheet->write_string($row, $col++, $studentdata->prescribedtitle, $format);
        $wordcount = $studentdata->wordcount; //count_words($studentdata->wordcount);
        $sheet->write_string($row, $col++, $wordcount, $format);
    }

}


