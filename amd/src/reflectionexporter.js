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
 * Gets the Extended Essay (EE) ib form in PDF format and imports the
 * reflections into it.
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
    "report_reflectionexporter/fontkit.umd",
    "core/templates",
    "report_reflectionexporter/reflectionexporterHelper",

], function ($, Ajax, Log, PDFLib, Fontkit, Templates, ReHelper) {
    "use strict";

    function init(data) {
        var control = new Controls(data);
        control.main();
    }

    function Controls(data) {
        this.data = data;
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
                ReHelper.get_error_template(self.data);
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
                formname: this.data.ibform,
                comment: users[i].teachercomments,
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

        pdfDoc.registerFontkit(Fontkit);
        const fontBytes = await fetch('./font/arial.ttf').then(res => res.arrayBuffer());

        // Embed the font in the PDF document
        const customFont = await pdfDoc.embedFont(fontBytes);

        fields.forEach((field) => {
            this.setFormFields(user, form, field, customFont);
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
     *  WORD LIMITS:
     *  Candidate commetns:  364
     *  Teachers comments: 542
     * @param {*} user
     * @param {*} form
     * @param {*} field
     */
    Controls.prototype.setFormFields = async function (user, form, field, customFont) {
        var self = this;
        const fieldName = field.getName();
        const fontSize = 9;

        switch (fieldName) {

            case ReHelper.get_ee_form_inputs().CANDIDATE_PERSONAL_CODE: //Candaite personal code "Text1"
                form.getTextField(fieldName).setText(String(user.id));
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().FIRST_REFLECTION_SESSION: // First reflection session (1st page) "Text3"
                user.reflections[0].onlinetext = JSON.parse(user.reflections[0].onlinetext).replace(/(\r\n|\n|\r)/gm, "");
                form.getTextField(fieldName).setText(user.reflections[0].onlinetext);
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().MONTH1: // Month MONTH1
                form.getDropdown(fieldName).select(user.reflections[0].month);
                break;
            case ReHelper.get_ee_form_inputs().DP1: // DP DP1
                form.getDropdown(fieldName).select(String(user.dp)); // just to make sure that we are sending a string
                break;
            case ReHelper.get_ee_form_inputs().FIRST_REFLECTION_SESSION_SUPERVISOR_INITIALS: // Supervisor initials "Text5"
                form.getTextField(fieldName).setText(String(user.si));
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().INTERIM_REFLECTION: // Interim reflection (2nd page) "Text6"
                user.reflections[1].onlinetext = JSON.parse(user.reflections[1].onlinetext).replace(/(\r\n|\n|\r)/gm, "");
                form.getTextField(fieldName).setText(user.reflections[1].onlinetext);
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().MONTH2: // Month MONTH2
                form.getDropdown(fieldName).select(user.reflections[1].month);
                break;
            case ReHelper.get_ee_form_inputs().DP2: // DP 2
                form.getDropdown(fieldName).select(String(user.dp));
                break;
            case ReHelper.get_ee_form_inputs().INTERIM_REFLECTION_SUPERVISOR_INITIALS: // Supervisor initials "Text8"
                form.getTextField(fieldName).setText(String(user.si));
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().FINAL_REFLECTION: // Final reflection (3rd page) "Text9"
                user.reflections[2].onlinetext = JSON.parse(user.reflections[2].onlinetext).replace(/(\r\n|\n|\r)/gm, "");
                form.getTextField(fieldName).setText(user.reflections[2].onlinetext);
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().MONTH3: // Month
                form.getDropdown(fieldName).select(user.reflections[2].month);
                break;
            case ReHelper.get_ee_form_inputs().DP3: //DP //dp
                form.getDropdown(fieldName).select(String(user.dp));
                break;
            case ReHelper.get_ee_form_inputs().FINAL_REFLECTION_SUPERVISOR_INITIALS: // Supervisor initials "Text11"
                form.getTextField(fieldName).setText(String(user.si));
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
            case ReHelper.get_ee_form_inputs().SUPERVISOR_COMMENT: // Supervisor COMMENT "Text12"
                form.getTextField(fieldName).setText(String(user.teachercomments));
                form.getTextField(fieldName).setFontSize(fontSize);
                form.getTextField(fieldName).updateAppearances(customFont);
                break;
        }
    };

    // Call WS to save pdf data in DB.

    Controls.prototype.savePDFInDB = function (pdfs) {
        var self = this;
        const batchSize = 10;
        const batches = [];
        let allResponses = [];

        for (let i = 0; i < pdfs.length; i += batchSize) {
            batches.push(pdfs.slice(i, i + batchSize));
        }

        // Do this in batches because when the numbers gets too big it crashes.
        function processBatch(batchIndex) {
            if (batchIndex >= batches.length) {
                // All batches processed, now render the template
                const context = {
                    pdfjson: JSON.stringify(allResponses),
                    courseid: self.data.cid,
                    coursename: self.data.coursename,
                    showuseridentity: true,
                    reflecid: self.data.rid,
                    formname: self.data.ibform,
                    firstuserid: 0,
                    withcomment: self.data.withcomment
                };
                console.log("YA TERMNINO LOS BATCHES> AHORA A RENDERERAR")
                console.log(context);
                Templates.render('report_reflectionexporter/viewer', context)
                    .done(function (html, js) {
                        $(document.querySelector('.importing-animation')).fadeOut("fast", function () {
                            Templates.replaceNodeContents($(document.querySelector('.importing-animation')), html, js);
                            $(document.querySelector('.importing-animation')).fadeIn("fast");
                        }.bind(this));
                    }).fail(function (ex) {
                        console.log(ex);
                    });

                return;
            }

            const pdfjson = JSON.stringify(batches[batchIndex]);

            Ajax.call([{
                methodname: "report_reflectionexporter_save_pdfbase64",
                args: {
                    pdfs: pdfjson,
                },
                done: function (response) {
                    console.log(response.savedrecords)
                    var savedrecords = JSON.parse(response.savedrecords);
                    if ((savedrecords[0]).teachercomments != null) {
                        (savedrecords[0]).teachercomments = (savedrecords[0]).teachercomments.replace(/(\r\n|\n|\r)/gm, "")
                        console.log(savedrecords)
                    }
                    allResponses = allResponses.concat(savedrecords) // Concatenate responses
                    processBatch(batchIndex + 1); // Process next batch
                },
                fail: function (reason) {
                    Log.error(reason);
                    ReHelper.get_error_template(self.data);
                },
            }]);
        }

        processBatch(0); // Start processing batches
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