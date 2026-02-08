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

/**
 * Version metadata for the public-test-link
 * @package public-test-link
 * @author azi-team
 * @copyright 2026, Aretex <your@email.address>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_publictestlink';
$plugin->version   = 2026020601;
$plugin->requires  = 2022041900; 
$plugin->supported = [500, 511];   // Available as of Moodle 3.9.0 or later.
// $plugin->incompatible = [400, 404];   // Available as of Moodle 3.9.0 or later.
$plugin->component = 'local_publictestlink';
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.1.1';

$plugin->dependencies = [
    'mod_forum' => 2022042100,
    'mod_data' => 2022042100
];