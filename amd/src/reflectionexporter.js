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
    "core/ajax",
    "core/log",
    "report_reflectionexporter/pdf-lib",
], function (Ajax, Log, PDFLib) {
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
        this.getreflectionsjson();

    };

    Controls.prototype.getreflectionsjson = function () {
        var self = this;
        Ajax.call([{
            methodname: "report_reflectionexporter_get_reflections",
            args: {
                rid: this.data.rid,
            },
            done: async function (response) {
                const pdfs = await self.processReflections(JSON.parse(response.reflecjson));
                self.savePDFInDB(pdfs);
            },
            fail: function (reason) {
                Log.error(reason);
            },
        }, ]);
    };

    // Returns an array with the users PDF enconded in base64
    Controls.prototype.processReflections = async function (users) {
        const studentpdfs = [];
        for (var i = 0; i < users.length; i++) {
            const pdf = await this.fillformAndSave(users[i]);

            const student = {
                uid: users[i].reflections[0].userid,
                courseid: this.data.cid,
                rid:  this.data.rid,
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

        // const pdfDataUri = await pdfDoc.saveAsBase64({
        //     dataUri: true
        // });

        // //console.log(pdfDataUri);
        // document.getElementById("pdf").src = pdfDataUri;

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

        const fieldName = field.getName();
        switch (fieldName) {
            case "Text1": //Candaite personal code
                Y.log(user);
                form.getTextField(fieldName).setText(String(user.id));
                break;
            case "Text3": // First reflection session (1st page)
                form.getTextField(fieldName).setText(JSON.parse(user.reflections[0].onlinetext));
                break;
            case "Dropdown1": // Month
                form.getDropdown(fieldName).select(user.reflections[0].month);
                break;
            case "Dropdown2": // DP
                form.getDropdown(fieldName).select(String(user.dp)); // just to make sure that we are sending a string
                break;
            case "Text5": // Supervisor initials
                form.getTextField(fieldName).setText(String(user.si));
                break;
            case "Text6": // Interim reflection (2nd page)
                form.getTextField(fieldName).setText(JSON.parse(user.reflections[1].onlinetext));
                break;
            case "Dropdown3": // Month
                form.getDropdown(fieldName).select(user.reflections[1].month);
                break;
            case "Dropdown4": // DP
                form.getDropdown(fieldName).select(String(user.dp));
                break;
            case "Text8": // Supervisor initials
                form.getTextField(fieldName).setText(String(user.si));
                break;
            case "Text9": // Final reflection (3rd page)
                form.getTextField(fieldName).setText(JSON.parse(user.reflections[2].onlinetext));
                break;
            case "Dropdown5": // Month
                form.getDropdown(fieldName).select(user.reflections[2].month);
                break;
            case "Dropdown6": //DP //dp
                form.getDropdown(fieldName).select(String(user.dp));
                break;
            case "Text11": // Supervisor initials
                form.getTextField(fieldName).setText(String(user.si));
                break;
        }
    };

    // Call WS to save pdf data in DB.
    Controls.prototype.savePDFInDB = function (pdfs) {
        Y.log("savePDFInDB");
        Y.log(pdfs);
        Ajax.call([{
            methodname: "report_reflectionexporter_save_pdfbase64",
            args: {
                pdfs: JSON.stringify(pdfs),
            },
            done: function (response) {
                console.log(response);
            },
            fail: function (reason) {
                Log.error(reason);
            },
        }, ]);
    }

    return {
        init: init,
    };
});