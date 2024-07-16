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
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    "jquery",
    "core/ajax",
    "core/log",
    "report_reflectionexporter/pdf-lib",
    "core/templates",

], function ($, Ajax, Log, PDFLib, Templates) {
    "use strict";

    function init(data) {
        var control = new Controls(data);
        control.main();
    }

    function Controls(data) {
        this.data = data;
        this.CANDIDATE_PERSONAL_CODE = 'Text1';
        this.FIRST_REFLECTION_SESSION = 'Text3';
        this.FIRST_REFLECTION_SESSION_SUPERVISOR_INITIALS = 'Text5';
        this.INTERIM_REFLECTION = 'Text6';
        this.INTERIM_REFLECTION_SUPERVISOR_INITIALS = 'Text8';
        this.FINAL_REFLECTION = 'Text9';
        this.FINAL_REFLECTION_SUPERVISOR_INITIALS = 'Text11';
        this.SUPERVISOR_COMMENT = 'Text12';
    }

    /**
     * Run the controller.
     *
     */
    Controls.prototype.main = function () {
        // Get the reflections data.

        if (this.data.new == 1) {
            this.getreflectionsjson()

        } else {
            this.getreflectionspdf();
        }

    };

    Controls.prototype.getreflectionspdf = function () {

        this.displayTemplate();
    }

    Controls.prototype.getreflectionsjson = async function () {
        var self = this;

        Ajax.call([{
            methodname: "report_reflectionexporter_get_reflections",
            args: {
                rid: this.data.rid,
            },
            done: async function (response) {
                const users = JSON.parse(response.reflecjson);
                const pdfs = await self.processReflections(users);

                self.savePDFInDB(pdfs);

            },
            fail: function (reason) {
                Log.error(reason);
            },
        },]);
    };

    // Returns an array with the users PDF enconded in base64
    Controls.prototype.processReflections = async function (users) {
        const studentpdfs = [];
        for (var i = 0; i < users.length; i++) {
            const pdf = await this.fillformAndSave(users[i]);

            const student = {
                uid: users[i].reflections[0].userid,
                courseid: this.data.cid,
                rid: this.data.rid,
                pdf: pdf
            };

            studentpdfs.push(student);

        }

        return studentpdfs;

    };
    // Fill PDF with data and return the generted file in base64
    Controls.prototype.fillformAndSave = async function (user) {
        const formUrl = this.data.fileurl;
        const formPdfBytes = await fetch(formUrl).then((res) => res.arrayBuffer());
        const pdfDoc = await PDFLib.PDFDocument.load(formPdfBytes);
        const form = pdfDoc.getForm();
        const fields = form.getFields();

        fields.forEach((field) => {
            this.setFormFields(user, form, field);
        });

        // Return the base64 PDF
        return await pdfDoc.saveAsBase64();

    };
    /**
     * Fields to complete for the student
     *  Candidate personal code: Text1
     * First reflection session: Text3
     *  Month first page: Dropdown1
     *  DP: Dropdown2
     *  supervisor id: Text5
     * Second reflection: Text6
     *  Month second page: Dropdown3
     *  DP: Dropdown4
     *  supervisor id: Text8
     *  third reflection: Text9
     *  Month third page: Dropdown5
     *  DP: Dropdown6
     *  supervisor id: Text11
     * 
     * @param {*} user 
     * @param {*} form 
     * @param {*} field 
     */
    Controls.prototype.setFormFields = async function (user, form, field) {
        var self = this;
        const fieldName = field.getName();
        console.log(user);
        switch (fieldName) {
            case self.CANDIDATE_PERSONAL_CODE: //Candaite personal code "Text1"
                Y.log(user);
                form.getTextField(fieldName).setText(String(user.id));
                break;
            case self.FIRST_REFLECTION_SESSION: // First reflection session (1st page) "Text3"
                form.getTextField(fieldName).setText(user.reflections[0].plaintext);
                break;
            case "Dropdown1": // Month
                form.getDropdown(fieldName).select((new Date(user.reflections[0].month * 1000)).toLocaleString('default', { month: 'long' }));
                break;
            case "Dropdown2": // DP
                form.getDropdown(fieldName).select(String(user.dp)); // just to make sure that we are sending a string
                break;
            case self.FIRST_REFLECTION_SESSION_SUPERVISOR_INITIALS: // Supervisor initials "Text5"
                form.getTextField(fieldName).setText(String(user.si));
                break;
            case self.INTERIM_REFLECTION: // Interim reflection (2nd page) "Text6"
                form.getTextField(fieldName).setText(user.reflections[1].plaintext);
                break;
            case "Dropdown3": // Month
                form.getDropdown(fieldName).select((new Date(user.reflections[1].month * 1000)).toLocaleString('default', { month: 'long' }));
                break;
            case "Dropdown4": // DP
                form.getDropdown(fieldName).select(String(user.dp));
                break;
            case self.INTERIM_REFLECTION_SUPERVISOR_INITIALS: // Supervisor initials "Text8"
                form.getTextField(fieldName).setText(String(user.si));
                break;
            case self.FINAL_REFLECTION: // Final reflection (3rd page) "Text9"
                form.getTextField(fieldName).setText(user.reflections[2].plaintext);
                break;
            case "Dropdown5": // Month
                form.getDropdown(fieldName).select((new Date(user.reflections[2].month * 1000)).toLocaleString('default', { month: 'long' }));
                break;
            case "Dropdown6": //DP //dp
                form.getDropdown(fieldName).select(String(user.dp));
                break;
            case self.FINAL_REFLECTION_SUPERVISOR_INITIALS: // Supervisor initials "Text11"
                form.getTextField(fieldName).setText(String(user.si));
                break;
        }
    };

    // Call WS to save pdf data in DB.
    Controls.prototype.savePDFInDB = function (pdfs) {
        var self = this;

        const pdfjson = JSON.stringify(pdfs);
        Ajax.call([{
            methodname: "report_reflectionexporter_save_pdfbase64",
            args: {
                pdfs: pdfjson,
            },
            done: function (response) {

                const context = {
                    pdfjson: response.savedrecords,
                    courseid: self.data.cid,
                    coursename: self.data.coursename,
                    showuseridentity: true,
                    reflecid: self.data.rid,
                    firstuserid: 0,
                }

                Templates.render('report_reflectionexporter/viewer', context)
                    .done(function (html, js) {
                        $(document.querySelector('.importing-animation')).fadeOut("fast", function () {
                            Templates.replaceNodeContents($(document.querySelector('.importing-animation')), html, js);
                            $(document.querySelector('.importing-animation')).fadeIn("fast");
                        }.bind(this));
                    }).fail(function (ex) {
                        console.log(ex);
                    });

            },
            fail: function (reason) {
                Log.error(reason);
            },
        },]);

    }

    Controls.prototype.displayTemplate = function () {
        var self = this;
        const context = {
            pdfjson: self.data.pdfjson,
            courseid: self.data.cid,
            coursename: self.data.coursename,
            showuseridentity: true,
            reflecid: self.data.rid,
            firstuserid: 0,
        }

        Templates.render('report_reflectionexporter/viewer', context)
            .done(function (html, js) {
                $(document.querySelector('.importing-animation')).fadeOut("fast", function () {
                    Templates.replaceNodeContents($(document.querySelector('.importing-animation')), html, js);
                    $(document.querySelector('.importing-animation')).fadeIn("fast");
                }.bind(this));
            }).fail(function (ex) {
                console.log(ex);
            });
    }

    return {
        init: init,
    };
});