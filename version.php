<?php
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
//
// This file is part of BasicORCALTI4Moodle made for ORCA.nrw project, it is based
// on BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

// BasicORCALTI4Moodle is copyright 2022-2023, made as a collaboration of ORCA.nrw project with
// Ruhr-Universität Bochum, metromorph softworks GmbH, Ampada GmbH and Moodle Community

/**
 * This file defines the version of orcalti
 *
 * @package mod_orcalti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2023031500;    // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2022041900;    // Requires this Moodle version.
$plugin->component = 'mod_orcalti';     // Full name of the plugin (used for diagnostics).
$plugin->cron      = 0;
