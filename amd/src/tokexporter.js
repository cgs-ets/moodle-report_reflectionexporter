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
  'jquery',
  'core/ajax',
  'core/log',
  'report_reflectionexporter/pdf-lib',
  'report_reflectionexporter/fontkit.umd',
  'core/templates',
  'report_reflectionexporter/reflectionexporterHelper',
], function ($, Ajax, Log, PDFLib, Fontkit, Templates, ReHelper) {
  'use strict';

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
      this.getInteractionsjson();
    } else {
      this.getInteractionspdf();
    }
  };

  Controls.prototype.getInteractionspdf = function () {
    this.displayTemplate();
  };

  Controls.prototype.getInteractionsjson = async function () {
    var self = this;

    Ajax.call([
      {
        methodname: 'report_reflectionexporter_get_reflections',
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
          ReHelper.get_error_template(self.data);
        },
      },
    ]);
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
        formname: this.data.ibform,
        comment: users[i].teachercomments,
        pdf: pdf,
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
    const fontBytes = await fetch('./font/arial.ttf').then((res) =>
      res.arrayBuffer(),
    );

    // Embed the font in the PDF document
    const customFont = await pdfDoc.embedFont(fontBytes);

    fields.forEach((field) => {
      this.setFormFields(user, form, field, customFont);
    });

    // Return the base64 PDF
    return await pdfDoc.saveAsBase64();
  };

  /**
   * Fields to complete in the form.
   * WORKD LIMITS:
   *    Interactions: 96
   *    Teachers comment: 200 words
   * @param {*} user
   * @param {*} form
   * @param {*} field
   */
  Controls.prototype.setFormFields = async function (
    user,
    form,
    field,
    customFont,
  ) {
    var self = this;
    const fieldName = field.getName();
    const fontSize = 9;

    switch (fieldName) {
      case self.tokFormInputs.CANDIDATE_PERSONAL_CODE:
        form.getTextField(fieldName).setText(String(user.id));
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.SESSION:
        form.getTextField(fieldName).setText(String(user.session));
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.PRESCRIBED_TITLE:
        form.getTextField(fieldName).setText(String(user.prescribedtitle));
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.FIRST_INTERACTION_CANDIDATE_COMMENTS:
        user.interactions[0].onlinetext = JSON.parse(
          user.interactions[0].onlinetext,
        ).replace(/(\r\n|\n|\r)/gm, '');
        form.getTextField(fieldName).setText(user.interactions[0].onlinetext);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.FIRST_INTERACTION_CANDIDATE_DATE:
        form.getTextField(fieldName).setText(user.interactions[0].month);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.SECOND_INTERACTION_CANDIDATE_COMMENTS:
        user.interactions[1].onlinetext = JSON.parse(
          user.interactions[1].onlinetext,
        ).replace(/(\r\n|\n|\r)/gm, '');
        form.getTextField(fieldName).setText(user.interactions[1].onlinetext);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.SECOND_INTERACTION_CANDIDATE_DATE:
        form.getTextField(fieldName).setText(user.interactions[1].month);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.THIRD_INTERACTION_CANDIDATE_COMMENTS:
        user.interactions[2].onlinetext = JSON.parse(
          user.interactions[2].onlinetext,
        ).replace(/(\r\n|\n|\r)/gm, '');
        form.getTextField(fieldName).setText(user.interactions[2].onlinetext);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.THIRD_INTERACTION_CANDIDATE_DATE:
        form.getTextField(fieldName).setText(user.interactions[2].month);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.TEACHER_COMMENTS:
        form.getTextField(fieldName).setText(user.teachercomments);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_CANDIDATE_NAME:
        form
          .getTextField(fieldName)
          .setText(`${user.firstname} ${user.lastname}`);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_CANDIDATE_SESSION_NUMBER:
        form.getTextField(fieldName).setText(user.studiescode);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_DECLARATION_DATE1:
        if (ReHelper.is_commented()) {
          user.month = ReHelper.get_today_formatted();
        }

        form.getTextField(fieldName).setText(user.month);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_DECLARATION_TEACHER_NAME:
        form.getTextField(fieldName).setText(user.teachersname);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_DECLARATION_DATE2:
        if (ReHelper.is_commented()) {
          user.month = ReHelper.get_today_formatted();
        }
        form.getTextField(fieldName).setText(user.month);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_DECLARATION_SCHOOL_NAME:
        form.getTextField(fieldName).setText(user.schoolname);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
      case self.tokFormInputs.COMPLETED_DECLARATION_SCHOOL_NUMBER:
        form.getTextField(fieldName).setText(user.schoolnumber);
        form.getTextField(fieldName).setFontSize(fontSize);
        form.getTextField(fieldName).updateAppearances(customFont);
        break;
    }
  };

  // Call WS to save pdf data in DB (table mdl_report_reflec_exporter_pdf).
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
        const downloadurl = new URL(window.location.href);
        downloadurl.searchParams.append('d', 1);

        const context = {
          pdfjson: JSON.stringify(allResponses),
          courseid: self.data.cid,
          coursename: self.data.coursename,
          showuseridentity: true,
          reflecid: self.data.rid,
          firstuserid: 0,
          withcomment: self.data.withcomment,
        };

        Templates.render('report_reflectionexporter/viewer', context)
          .done(function (html, js) {
            $(document.querySelector('.importing-animation')).fadeOut(
              'fast',
              function () {
                Templates.replaceNodeContents(
                  $(document.querySelector('.importing-animation')),
                  html,
                  js,
                );
                $(document.querySelector('.importing-animation')).fadeIn(
                  'fast',
                );
              }.bind(this),
            );
          })
          .fail(function (ex) {
            console.log(ex);
          });

        return;
      }

      const pdfjson = JSON.stringify(batches[batchIndex]);

      Ajax.call([
        {
          methodname: 'report_reflectionexporter_save_pdfbase64',
          args: {
            pdfs: pdfjson,
          },
          done: function (response) {
            var savedrecords = JSON.parse(response.savedrecords);

            if (savedrecords[0].teachercomments != null) {
              savedrecords[0].teachercomments =
                savedrecords[0].teachercomments.replace(/(\r\n|\n|\r)/gm, '');
            }
            allResponses = allResponses.concat(savedrecords); // Concatenate responses

            processBatch(batchIndex + 1); // Process next batch
          },
          fail: function (reason) {
            Log.error(reason);
            ReHelper.get_error_template(self.data);
          },
        },
      ]);
    }

    processBatch(0); // Start processing batches
  };

  Controls.prototype.displayTemplate = function () {
    var self = this;
    const context = {
      pdfjson: self.data.pdfjson,
      courseid: self.data.cid,
      coursename: self.data.coursename,
      showuseridentity: true,
      reflecid: self.data.rid,
      firstuserid: 0,
    };

    Templates.render('report_reflectionexporter/viewer', context)
      .done(function (html, js) {
        $(document.querySelector('.importing-animation')).fadeOut(
          'fast',
          function () {
            Templates.replaceNodeContents(
              $(document.querySelector('.importing-animation')),
              html,
              js,
            );
            $(document.querySelector('.importing-animation')).fadeIn('fast');
          }.bind(this),
        );
      })
      .fail(function (ex) {
        console.log(ex);
      });
  };

  return {
    init: init,
  };
});
