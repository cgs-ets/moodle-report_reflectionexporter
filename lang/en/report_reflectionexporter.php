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
 * Strings for component 'report_reflectionexporter'
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname']                       = 'Reflection exporter';
$string['heading']                          = 'Reflection exporter';
$string['returntocourse']                   = 'Return to course';
$string['attachment_filemanager']           = 'EE/RPPF file';
$string['attachment_filemanager_help']      = 'File where the reflections will be exported to.';
$string['assessments']                      = '1<sup>st</sup> reflection';
$string['assessments_help']                 = 'Only assessments with submission type <strong>online text</strong> are displayed.';
$string['assessments2']                     = '2<sup>nd</sup> reflection';
$string['assessments2_help']                = 'Only assessments with submission type <strong>online text</strong> are displayed.';
$string['assessments3']                     = '3<sup>rd</sup> reflection';
$string['assessments3_help']                = 'Only assessments with submission type <strong>online text</strong> are displayed.';
$string['assessmenterror']                  = 'Please select the assessment corresponding to the 1<sup>st</sup> reflection';
$string['assessment2error']                 = 'Please select the assessment corresponding to the  2<sup>nd</sup> reflection';
$string['assessment3error']                 = 'Please select the assessment corresponding to the 3<sup>rd</sup> reflection';
$string['useridterror']                     = 'Please select One or more students';
$string['onbehalf']                         = 'Are you filling the supervisor comments on behalf of another colleague(s)?';
$string['onbehalf_help']                    = 'If this option is disabled it is because there are no groups available';
$string['coursegroups']                     = 'Select group(s)';
$string['nogroups']                         = 'No groups available';
$string['coursegroups_help']                = 'Group the colleague you are completing this work for belongs to. <br> See the initials that will be printed in the PDF';
$string['supervisorinitials']               = 'Supervisor initials';
$string['supervisorinitialserror']          = 'Please insert your initials';
$string['activeusers']                      = 'Allocated students';
$string['action-text-continue']             = 'Continue export';
$string['action-text-new']                  = 'New export';
$string['reflection-exporter-heading']      = 'Reflection exporter';
$string['savingchanges']                    = 'Saving changes';
$string['nousersselected']                  = 'No user selected';
$string['cantdisplayerror']                 = 'Reflection exporter is only functional in courses.';
$string['noprocesserror']                   = 'The selected students did not complete their reflections';
$string['wrongfileformat']                  = 'The PDF provided cannot be processed';
$string['teacheringroup']                   = 'Supervisor <strong> {$a->firstname} {$a->lastname} </strong> initials';