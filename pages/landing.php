<?php

require_once('../../config.php');

require_once('./forms/non_user_login.php');
require_once($GLOBALS['CFG']->libdir . '/moodlelib.php');


// function normalize_email(string $email): string {
//     return core_text::strtolower(trim($email));
// }

// function login_shadow_user(string $email, string $firstname, string $lastname): bool {
//     $email = normalize_email($email);
//     $firstname = trim($firstname);
//     $lastname  = trim($lastname);

//     if (empty($email)) {
//         throw new \core\exception\moodle_exception('invalidemail');
//     }

// }

$PAGE->requires->css('/local/publictestlink/styles.css');

$PAGE->set_url('/local/publictestlink/landing.php');
$PAGE->set_context(context_system::instance());

$PAGE->set_title('Login');
$PAGE->set_heading('Login as non-user');
$PAGE->set_pagelayout('standard');

$PAGE->add_body_class('landing-body');

$form = new local_publictestlink_non_user_login();

if ($data = $form->get_data()) {
    var_dump($data->firstname);
    var_dump($data->lastname);
    var_dump($data->email);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();