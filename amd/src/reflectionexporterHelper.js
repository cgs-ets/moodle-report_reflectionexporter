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
 * Each ibform has different ids for the inputs.
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(["core/templates"], function (Templates) {
    "use strict";

    return {
        //
        get_ee_form_inputs: function () {
            return {
                CANDIDATE_PERSONAL_CODE: 'Text1',
                FIRST_REFLECTION_SESSION: 'Text3',
                FIRST_REFLECTION_SESSION_SUPERVISOR_INITIALS: 'Text5',
                INTERIM_REFLECTION: 'Text6',
                INTERIM_REFLECTION_SUPERVISOR_INITIALS: 'Text8',
                FINAL_REFLECTION: 'Text9',
                FINAL_REFLECTION_SUPERVISOR_INITIALS: 'Text11',
                SUPERVISOR_COMMENT: 'Text12',

            };

        },

        get_tok_form_inputs: function () {
            return {
                CANDIDATE_PERSONAL_CODE: 'Text1',
                SESSION: 'Text2',
                PRESCRIBED_TITLE: 'Text3',
                FIRST_INTERACTION_CANDIDATE_COMMENTS: 'Text4',
                FIRST_INTERACTION_CANDIDATE_DATE: 'Text5',
                SECOND_INTERACTION_CANDIDATE_COMMENTS: 'Text6',
                SECOND_INTERACTION_CANDIDATE_DATE: 'Text7',
                THIRD_INTERACTION_CANDIDATE_COMMENTS: 'Text8',
                THIRD_INTERACTION_CANDIDATE_DATE: 'Text9',
                TEACHER_COMMENTS: 'Text10',
                COMPLETED_CANDIDATE_NAME: 'Text11',
                COMPLETED_CANDIDATE_SESSION_NUMBER: 'Text12',
                COMPLETED_DECLARATION_DATE1: 'Text13',
                COMPLETED_DECLARATION_TEACHER_NAME: 'Text14',
                COMPLETED_DECLARATION_DATE2: 'Text15',
                COMPLETED_DECLARATION_SCHOOL_NAME: 'Text16',
                COMPLETED_DECLARATION_SCHOOL_NUMBER: 'Text17',

            }
        },

        get_ibform_name: function () {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('ibform');
        },

        get_error_template: function (data) {
            const context = {
                courseid: data.cid,
                coursename: data.coursename
            };

            Templates.renderForPromise('report_reflectionexporter/error_message', context)
                .then(({ html, js }) => {
                    Templates.replaceNodeContents($(document.querySelector('.importing-animation')), html, js);
                })
                .catch((error) => displayException(error));
        }


    };
});