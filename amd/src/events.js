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
 * Provides a list of events that can be triggered in the ORCALTI management
 * page.
 *
 * @module     mod_orcalti/events
 * @class      events
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define([], function() {
    return /** @alias module:mod_orcalti/events */ {
        NEW_TOOL_TYPE: 'orcalti.tool.type.new',
        START_EXTERNAL_REGISTRATION: 'orcalti.registration.external.start',
        STOP_EXTERNAL_REGISTRATION: 'orcalti.registration.external.stop',
        START_CARTRIDGE_REGISTRATION: 'orcalti.registration.cartridge.start',
        STOP_CARTRIDGE_REGISTRATION: 'orcalti.registration.cartridge.stop',
        REGISTRATION_FEEDBACK: 'orcalti.registration.feedback',
        CAPABILITIES_AGREE: 'orcalti.tool.type.capabilities.agree',
        CAPABILITIES_DECLINE: 'orcalti.tool.type.capabilities.decline',
    };
});
