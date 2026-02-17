<?php

use core\output\html_writer;
use core\url as moodle_url;

require_once(__DIR__ . '/session.php');

/**
 * The header writer for the quiz flow.
 */
class user_header_writer {
    /**
     * Writes HTML of the currently logged in user on the current position.
     * @param publictestlink_session $session The current session.
     */
    public static function write(publictestlink_session $session) {
        $shadowuser = $session->get_user();

        $html = html_writer::start_div('d-flex mb-4 w-100 flex-row gap-4 justify-content-center align-items-center');
            $html .= html_writer::tag('h6',
                "Logged in as {$shadowuser->get_firstname()} {$shadowuser->get_lastname()} ({$shadowuser->get_email()})."
            );
            $html .= html_writer::link(
                new moodle_url(PLUGIN_URL . '/exit.php'),
                'Logout',
                ['class' => 'btn btn-danger']
            );
        $html .= html_writer::end_div();

        echo $html;
    }
}