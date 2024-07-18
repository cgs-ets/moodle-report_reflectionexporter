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
 * Gets the Theory of Knowledge (TOK) ib form in PDF format and imports the
 * interactions and teacher comments into it.
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define([
    "jquery",
    "core/ajax",
    "core/log",
    "report_reflectionexporter/pdf-lib",
    "core/templates",
    "report_reflectionexporter/reflectionexporterHelper",

], function ($, Ajax, Log, PDFLib, Templates, ReHelper) {
    "use strict";

    function init(data) {
        var control = new Controls(data);
        control.main();
    }

    /**
     * The TextX name are the names the ib form in PDF format ha given to the fields.
     * @param {*} data
     */
    function Controls(data) {

        this.data = data;
        this.tokFormInputs = ReHelper.get_tok_form_inputs();
    }

    /**
     * Run the controller.
     *
     */
    Controls.prototype.main = function () {
        // Get the interactions data.

        if (this.data.new == 1) {
            this.getInteractionsjson()
        } else {
            this.getInteractionspdf();
        }

    };

    Controls.prototype.getInteractionspdf = function () {

        this.displayTemplate();
    }

    Controls.prototype.getInteractionsjson = async function () {
        var self = this;

        Ajax.call([{
            methodname: "report_reflectionexporter_get_reflections",
            args: {
                rid: this.data.rid,
            },
            done: async function (response) {
                const users = JSON.parse(response.reflecjson);
                const pdfs = await self.processInteractions(users);
                self.savePDFInDB(pdfs);

            },
            fail: function (reason) {
                Log.error(reason);
            },
        },]);
    };

    /**
     *  For each student, fill the form with the
     *  data needed.
     * @param {*} users
     * @returns
     */
    Controls.prototype.processInteractions = async function (users) {
        const studentpdfs = [];
        for (var i = 0; i < users.length; i++) {
            const pdf = await this.fillformAndSave(users[i]);
            const student = {
                uid: users[i].interactions[0].userid,
                courseid: this.data.cid,
                rid: this.data.rid,
                pdf: pdf,
                formname: this.data.ibform
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
   * Fields to complete in the form.
   * @param {*} user
   * @param {*} form
   * @param {*} field
   */
    Controls.prototype.setFormFields = async function (user, form, field) {
        var self = this;
        const fieldName = field.getName();

        switch (fieldName) {
            case self.tokFormInputs.CANDIDATE_PERSONAL_CODE:
                form.getTextField(fieldName).setText(String(user.id));
                break;
            case self.tokFormInputs.SESSION:
                form.getTextField(fieldName).setText(String(user.session));
                break;
            case self.tokFormInputs.PRESCRIBED_TITLE:
                form.getTextField(fieldName).setText(String(user.prescribedtitle));
                break;
            case self.tokFormInputs.FIRST_INTERACTION_CANDIDATE_COMMENTS:

                form.getTextField(fieldName).setText((user.interactions[0].plaintext).replaceAll('"', ''));
                break;
            case self.tokFormInputs.FIRST_INTERACTION_CANDIDATE_DATE:
                form.getTextField(fieldName).setText(user.interactions[0].month);
                break;
            case self.tokFormInputs.SECOND_INTERACTION_CANDIDATE_COMMENTS:
                form.getTextField(fieldName).setText((user.interactions[1].plaintext).replaceAll('"', ''));
                break;
            case self.tokFormInputs.SECOND_INTERACTION_CANDIDATE_DATE:
                form.getTextField(fieldName).setText(user.interactions[1].month);
                break;
            case self.tokFormInputs.THIRD_INTERACTION_CANDIDATE_COMMENTS:
                form.getTextField(fieldName).setText((user.interactions[2].plaintext.replaceAll('"', '')));
                break;
            case self.tokFormInputs.THIRD_INTERACTION_CANDIDATE_DATE:
                form.getTextField(fieldName).setText(user.interactions[2].month);
                break;
            case self.tokFormInputs.TEACHER_COMMENTS:
                form.getTextField(fieldName).setText(user.comments);
                break;
            case self.tokFormInputs.COMPLETED_CANDIDATE_NAME:
                form.getTextField(fieldName).setText(`${user.firstname} ${user.lastname}`);
                break;
            case self.tokFormInputs.COMPLETED_CANDIDATE_SESSION_NUMBER:
                form.getTextField(fieldName).setText(user.session);
                break;
            case self.tokFormInputs.COMPLETED_DECLARATION_DATE1:
                form.getTextField(fieldName).setText(user.month);
                break;
            case self.tokFormInputs.COMPLETED_DECLARATION_TEACHER_NAME:
                form.getTextField(fieldName).setText(user.teachersname);
                break;
            case self.tokFormInputs.COMPLETED_DECLARATION_DATE2:
                form.getTextField(fieldName).setText(user.month);
                break;
            case self.tokFormInputs.COMPLETED_DECLARATION_SCHOOL_NAME:
                form.getTextField(fieldName).setText(user.schoolname);
                break;
            case self.tokFormInputs.COMPLETED_DECLARATION_SCHOOL_NUMBER:
                form.getTextField(fieldName).setText(user.schoolnumber);
                break;

        }
    };

    // Call WS to save pdf data in DB (table mdl_report_reflec_exporter_pdf).
    Controls.prototype.savePDFInDB = function (pdfs) {
        var self = this;

        const pdfjson = JSON.stringify(pdfs);

        Ajax.call([{
            methodname: "report_reflectionexporter_save_pdfbase64",
            args: {
                pdfs: pdfjson,
            },
            done: function (response) {
                const downloadurl = new URL(window.location.href);
                downloadurl.searchParams.append('d', 1);
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
            // tkform: self.data.ibform === 'TK_PPF',
        }

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
    }

    return {
        init: init,
    };




})
