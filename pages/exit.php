<?php
require_once('../../../config.php');
require_once('../classes/session.php');

use core\url as moodle_url;

// Logout the user
$session = publictestlink_session::logout();

// Go home, you're drunk
redirect(new moodle_url('/'));