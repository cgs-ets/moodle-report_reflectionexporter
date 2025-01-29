/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    report_reflectionexporter
 * @copyright  2025 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log'], function (Log) {
  // 'use strict';
  function init() {
    let control = new SingleDownloadControl();
    control.main();
  }

  function SingleDownloadControl() {
    Log.debug('report_reflectionexporter: initializing controls');
  }

  SingleDownloadControl.prototype.main = function () {
    let self = this;
    self.setupEvents();
  };

  SingleDownloadControl.prototype.setupEvents = function () {
    let self = this;
    document
      .querySelectorAll('.ref-exporter-single-download')
      .forEach(function (element) {
        element.addEventListener('click', self.btnclickHandler);
      });
  };

  SingleDownloadControl.prototype.btnclickHandler = function (e) {
    e.target.click();
  };

  return {
    init: init,
  };
});
