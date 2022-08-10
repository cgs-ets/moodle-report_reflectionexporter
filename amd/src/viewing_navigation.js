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
 * Javascript to handle changing users via the user selector in the header.
 *
 * @module     report_reflectionexporter/grading_navigation
 * @copyright  2016 Damyon Wiese <damyon@moodle.com>
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/str', 'core/ajax'],
    function ($, notification, str, ajax) {

        /**
         * viewingNavigation class.
         *
         * @class report_reflectionexporter/viewing_navigation
         * @param {String} selector The selector for the page region containing the user navigation.
         */
        var viewingNavigation = function (selector) {
            this._regionSelector = selector;
            this._region = $(selector);
            this._filters = [];
            this._users = [];
            this._firstLoadUsers = true;

            // Get the current user list from a webservice.
            this._loadAllUsers();

            // We do not allow navigation while ajax requests are pending.
            // Attach listeners to the select and arrow buttons.

            this._region.find('[data-action="previous-user"]').on('click', this._handlePreviousUser.bind(this));
            this._region.find('[data-action="next-user"]').on('click', this._handleNextUser.bind(this));
            this._region.find('[data-action="change-user"]').on('change', this._handleChangeUser.bind(this));

            $(document).on('user-changed', this._refreshSelector.bind(this));

            // var userid = $('[data-region="viewer-navigation-panel"]').data('first-userid');

            // if (userid) {
            //     this._selectUserById(userid);
            // }

            $(document).bind("start-loading-user", function () {
                this._isLoading = true;
            }.bind(this));

            $(document).bind("finish-loading-user", function () {
                this._isLoading = false;
            }.bind(this));
        };

        /** @property {Boolean} Boolean tracking active ajax requests. */
        viewingNavigation.prototype._isLoading = false;

        /** @property {String} Selector for the page region containing the user navigation. */
        viewingNavigation.prototype._regionSelector = null;

        /** @property {Array} The list of active filter keys */
        viewingNavigation.prototype._filters = null;

        /** @property {Array} The list of users */
        viewingNavigation.prototype._users = null;

        /** @property {JQuery} JQuery node for the page region containing the user navigation. */
        viewingNavigation.prototype._region = null;

        /** @property {String} Last active filters */
        viewingNavigation.prototype._lastFilters = '';

        /**
         * Load the list of all users for this assignment.
         *
         * @private
         * @method _loadAllUsers
         * @return {Boolean} True if the user list was fetched.
         */
        viewingNavigation.prototype._loadAllUsers = function () {
            var select = this._region.find('[data-action=change-user]');
            var reflecid = select.attr('data-rid');

            ajax.call([{
                methodname: 'report_reflectionexporter_list_participants',
                args: {
                    rid: reflecid,
                },
                done: this._usersLoaded.bind(this),
                fail: notification.exception
            }]);
            return true;
        };

        /**
         * Call back to rebuild the user selector.
         *
         * @private
         * @method _usersLoaded
         * @param {Array} users
         */
        viewingNavigation.prototype._usersLoaded = function (users) {
            this._firstLoadUsers = false;
            this._users = users;

            var select = this._region.find('[data-action=change-user]')[0];

            for (var i = 0; i < this._users.length; i++) {
                var option = document.createElement('option');
                option.value = this._users[i].id;
                option.text = this._users[i].fullname;
                select.add(option);
            }
            // Select the first user to display the summary
            var firstUser = this._users[0].id;
            $('[data-region="viewer-navigation-panel"]').attr('data-first-userid', firstUser);
            $('[data-region="viewer-navigation-panel"]').attr('first-userid', firstUser);

            this._selectUserById(firstUser);
        };

        /**
         * Select the specified user by id.
         *
         * @private
         * @method _selectUserById
         * @param {Number} userid
         */
        viewingNavigation.prototype._selectUserById = function (userid) {
            var select = this._region.find('[data-action=change-user]');
            var useridnumber = parseInt(userid, 10);
            select.attr('data-selected', userid);

            if (!isNaN(useridnumber) && useridnumber > 0) {
                $(document).trigger('user-changed', userid);
            }
        };

        /**
         * Change to the previous user in the viewing list.
         *
         * @private
         * @method _handlePreviousUser
         * @param {Event} e
         */
        viewingNavigation.prototype._handlePreviousUser = function (e) {
            e.preventDefault();
            var select = this._region.find('[data-action=change-user]');
            var currentUserId = select.attr('data-selected');
            var i = 0;
            var currentIndex = 0;

            for (i = 0; i < this._users.length; i++) {
                if (this._users[i].id == currentUserId) {
                    currentIndex = i;
                    break;
                }
            }

            var count = this._users.length;
            var newIndex = (currentIndex - 1);
            if (newIndex < 0) {
                newIndex = count - 1;
            }

            if (count) {
                this._selectUserById(this._users[newIndex].id, newIndex);
            }

        };


        /**
         * Change to the next user in the viewing list.
         *
         * @param {Event} e
         * @param {Boolean} saved Has the form already been saved? Skips checking for changes if true.
         */
        viewingNavigation.prototype._handleNextUser = function (e, saved) {
            e.preventDefault();
            var select = this._region.find('[data-action=change-user]');
            var currentUserId = select.attr('data-selected');
            var i = 0;
            var currentIndex = 0;

            for (i = 0; i < this._users.length; i++) {
                if (this._users[i].id == currentUserId) {
                    currentIndex = i;
                    break;
                }
            }
            var count = this._users.length;
            var newIndex = (currentIndex + 1) % count;
            if (count) {
                this._selectUserById(this._users[newIndex].id);
            }

        };


        /**
         * Respond to a user-changed event by updating the selector.
         *
         * @private
         * @method _refreshSelector
         * @param {Event} event
         * @param {String} userid
         */
        viewingNavigation.prototype._refreshSelector = function (event, userid) {
            var select = this._region.find('[data-action=change-user]');
            userid = parseInt(userid, 10);

            var select = this._region.find('[data-action=change-user]');
            userid = parseInt(userid, 10);

            if (!isNaN(userid) && userid > 0) {
                select.attr('data-selected', userid);
            }

            var i = 0;
            var options = document.querySelector('[data-action=change-user]').children;
            for (i; i < options.length; i++) {
                if (options[i].getAttribute('value') == String(userid)) {
                    options[i].setAttribute('selected', true);
                } else if (options[i].getAttribute('selected')) {
                    options[i].removeAttribute('selected')
                }
            }

        };


        /**
         * Change to a different user in the viewing list.
         *
         * @private
         * @method _handleChangeUser
         */
        viewingNavigation.prototype._handleChangeUser = function () {
            Y.log('_handleChangeUser...');
            var select = this._region.find('[data-action=change-user]');
            var userid = parseInt(select.val(), 10);

            if (this._isLoading) {
                return;
            }

            if (!isNaN(userid) && userid > 0) {
                select.attr('data-selected', userid);
                $(document).trigger('user-changed', userid);

            }
        };

        return viewingNavigation;
    });