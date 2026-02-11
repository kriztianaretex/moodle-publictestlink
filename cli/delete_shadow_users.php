<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/moodlelib.php');

global $DB;

// --------------------
// CLI options
// --------------------
list($options, $unrecognized) = cli_get_params(
    [
        'confirm' => false,
        'dry-run' => false,
        'days'    => 7,
        'help'    => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($options['help']) {
    echo <<<EOF
Delete shadow users created via auth=nologin.

Options:
--confirm=yes     Actually delete users (required)
--dry-run         Show what would be deleted
--days=7          Minimum age in days (default: 7)
-h, --help        Show this help

Example:
php local/yourplugin/cli/cleanup_nologin_users.php --dry-run
php local/yourplugin/cli/cleanup_nologin_users.php --confirm=yes --days=14

EOF;
    exit(0);
}

if ($options['confirm'] !== 'yes') {
    cli_error("Refusing to delete users. Use --confirm=yes");
}

$cutoff = time() - ((int)$options['days'] * DAYSECS);

// --------------------
// Fetch shadow users
// --------------------
$users = $DB->get_records_sql("
    SELECT u.*
    FROM {user} u
    WHERE u.auth = :auth
      AND u.deleted = 0
      AND u.lastaccess = 0
      AND u.timecreated < :cutoff
      AND NOT EXISTS (
          SELECT 1
          FROM {user_enrolments} ue
          WHERE ue.userid = u.id
      )
", [
    'auth'   => 'nologin',
    'cutoff' => $cutoff,
]);

mtrace("Found " . count($users) . " nologin shadow users");

foreach ($users as $user) {
    if (is_siteadmin($user)) {
        mtrace("Skipping site admin {$user->id}");
        continue;
    }

    if ($options['dry-run']) {
        mtrace("Would delete user {$user->id} ({$user->email})");
        continue;
    }

    delete_user($user);
    mtrace("Deleted user {$user->id} ({$user->email})");
}

mtrace('Done.');