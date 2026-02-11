<?php
require_once('../../../config.php');
require_once('../classes/session.php');

use core\url as moodle_url;

$session = publictestlink_session::logout();

redirect(
    new moodle_url('/')
);