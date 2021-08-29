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
 * Javascript controller for checkboxed table.
 *
 * @package tool_cleanupusers
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
import Notification from "core/notification";

function redirectPost(url, data) {
    const form = document.createElement('form');
    document.body.appendChild(form);
    form.method = 'post';
    form.action = url;
    for (const pair of data) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = pair.k;
        input.value = pair.v;
        form.appendChild(input);
    }
    form.submit();
}

export function init() {
    const checkall = document.querySelector('input[name="tool_cleanupusers-checkall"]');
    if (!checkall) {
        return;
    }
    const checkboxes = document.querySelectorAll('input[name="tool_cleanupusers-check"]');
    checkall.onclick = () => {
        checkboxes.forEach((c) => {
            c.checked = checkall.checked;
        });
    };
    const action = document.querySelectorAll('*[data-cleanupusers-action]');
    action.forEach((a) => {
        a.onclick = (e) => {
            e.preventDefault();
            let data = [
                {k: 'a', v: a.getAttribute('data-cleanupusers-action')},
                {k: 'sesskey', v: M.cfg.sesskey}
            ];
            if (a.getAttribute('data-cleanupusers-forall') === '1') {
                data.push({k: 'all', v: '1'});

                getStrings([
                    {'key' : 'warning'},
                    {'key' : 'forallwarning', component : 'tool_cleanupusers'},
                    {'key' : 'yes'},
                    {'key' : 'no'},
                ]).done(function(s) {
                        Notification.confirm(s[0], s[1], s[2], s[3], function() {
                            redirectPost(window.location, data);
                        });
                    }
                ).fail(Notification.exception);
            } else  {
                checkboxes.forEach((c) => {
                    if (c.checked) {
                        data.push({k: 'ids[]', v: c.value});
                    }
                });
                redirectPost(window.location, data);
            }
        };
    });
}