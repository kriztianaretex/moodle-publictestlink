<?php

/**
 * @package local_publictestlink
 * @author azi-team
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_publictestlink_before_footer() {
    \core\notification::add('Hello World!', \core\output\notification::NOTIFY_SUCCESS);
    \core\notification::add('You have successfully pulled my commit.', \core\output\notification::NOTIFY_INFO);
}