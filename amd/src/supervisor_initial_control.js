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
 * Provides the report_reflectionexporter/create_control module
 *
 * @package   report_reflectionexporter
 * @category  output
 * @copyright 2022 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module report_reflectionexporter/controls
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * Initializes the controls.
     */
    function init(data) {

        const control = new SupervisorInitialControl(data);
        control.main();

    }

    // Constructor.
    function SupervisorInitialControl(data) {

        var self = this;
        self.data = data;

    }

    SupervisorInitialControl.prototype.main = function () {
        var self = this;
        self.initListeners();
    };

    // Add event listener to the select 
    SupervisorInitialControl.prototype.initListeners = function () {
        const self = this;

        document.getElementById('id_coursegroups').addEventListener('change', (function (e) {
            // Add selected teachers to the JSON.
            $(e.target).find('option:selected').each(function (index, option) {
                const groupid = option.value;

                // look for the teacher in the group.
                const teacher = document.querySelectorAll(`[id^= id_teacher_${groupid}]`)[0];
                // Get the div that has the id fitem_id_teacher_groupid_techerid.
                let teacherid = `fitem_${teacher.getAttribute('id')}`;
                const dEl = document.getElementById(teacherid);
                teacherid = teacherid.split('_');
                teacherid = teacherid[teacherid.length - 1];

                dEl.classList.remove('teacher-initial-field-hide');
                const details = {
                    groupid: groupid,
                    teacherid: teacherid,
                    delete: 0
                };
                self.updateTeacherSelectionJSON(details);
                //self.updateStudentSelectionJSON(details);
            });

            // Control that the unselect elements are not part of the JSON. (Example: Selected all first and then unselect some)
            $('#id_coursegroups option:not(:selected)').each(function (index, option) {
                const groupid = option.value;
                // look for the teacher in the group.
                const teacher = document.querySelectorAll(`[id^= id_teacher_${groupid}]`)[0];
                let teacherid = `fitem_${teacher.getAttribute('id')}`;
                const dEl = document.getElementById(teacherid);
                teacherid = teacherid.split('_');
                teacherid = teacherid[teacherid.length - 1];
                dEl.classList.add('teacher-initial-field-hide');
                const details = {
                    groupid: groupid,
                    teacherid: teacherid,
                    delete: 1
                };
                self.updateTeacherSelectionJSON(details);


            })

        }).bind(self));

        // Add listener to the teachers initial inputs to pick up any changes done.
        const supervisorintials = document.querySelectorAll("[id^= id_teacher_]");
        $(supervisorintials).each(function (index, sinput) {
            sinput.addEventListener('change', function (e) {
                let teacherid = e.target.getAttribute('id');
                teacherid = teacherid.split('_');
                teacherid = teacherid[teacherid.length - 1];
                console.log(e.target.value)
                const data = {
                    id: teacherid,
                    si: e.target.value
                };
                self.updateTeacherInitial(data);
            });
        }).bind(self);
    }

    SupervisorInitialControl.prototype.updateTeacherSelectionJSON = function (details) {
        let jsoninput = JSON.parse(document.getElementById('id_groupselectionjsonaux').value);
        let jsoninputcurrent = JSON.parse(document.getElementById('id_groupselectionjson').value);

        if (details.delete == 1) {
            // We already put some data inthe currentjson to get to this point
            jsoninputcurrent = jsoninputcurrent.filter(function (input) {
                if (input.groupid != details.groupid) return input;
            }, details);
            document.getElementById('id_groupselectionjson').value = JSON.stringify(jsoninputcurrent);
        } else {

            // check if its not already  there
            if (jsoninputcurrent.find(input => input.groupid == details.groupid) == undefined) {

                jsoninput = jsoninput.filter(function (input) {
                    if (input.groupid == details.groupid) return input;
                }, details);

                jsoninputcurrent.push(jsoninput[0]);
            }
            document.getElementById('id_groupselectionjson').value = JSON.stringify(jsoninputcurrent);
        }

    }

    SupervisorInitialControl.prototype.updateTeacherInitial = function (data) {
        console.log(data);
        let jsoninput = JSON.parse(document.getElementById('id_groupselectionjsonaux').value);
        let jsoninputcurrent = JSON.parse(document.getElementById('id_groupselectionjson').value);

        // We need to update both jsons
        jsoninputcurrent.forEach(function (teacher) {
            if (teacher.id == data.id) {
                teacher.si = data.si;
            }
        }, data);

        jsoninput.forEach(function (teacher) {
            if (teacher.id == data.id) {
                teacher.si = data.si;
                
            }
        }, data);

        document.getElementById('id_groupselectionjson').value = JSON.stringify(jsoninputcurrent);
        document.getElementById('id_groupselectionjsonaux').value = JSON.stringify(jsoninput);

    }


    return {
        init: init
    };
});