<?php

require_once('../locallib.php');
require_once(__DIR__ . '/shadow_user.php');

use core\url as moodle_url;
use core\exception\moodle_exception;


class publictestlink_session {
    private const EXPIRE_SECONDS = 2 * HOURSECS;
    private const COOKIE_NAME = 'local_publictestlink_session';

    public function __construct(
        protected int $id,
        protected int $shadowuserid,
        protected string $rawtoken,
        protected int $expireson,
        protected int $lastaccessedon,
        protected bool $isrevoked,
    ) {}

    private static function generate_token(): string {
        return bin2hex(random_bytes(32));
    }

    private static function hash_token(string $token): string {
        return hash('sha256', $token);
    }

    public static function create(int $shadowuserid): self {
        global $DB;

        $rawtoken = self::generate_token(); // 64 chars
        $hashedtoken = self::hash_token($rawtoken);

        $timenow = time();

        $record = (object) [
            'shadowuserid' => $shadowuserid,
            'token' => $hashedtoken,
            'expireson' => $timenow + self::EXPIRE_SECONDS,
            'lastaccessedon' => $timenow,
            'isrevoked' => 0
        ];
        $id = $DB->insert_record('local_publictestlink_session', $record);

        return new self(
            $id, $record->shadowuserid, $rawtoken, $record->expireson, $record->lastaccessedon, $record->isrevoked
        );
    }

    private static function from_token(string $rawtoken): ?self {
        global $DB;
        $record = $DB->get_record(
            'local_publictestlink_session',
            [
                'token' => self::hash_token($rawtoken),
                'isrevoked' => 0
            ],
            "*",
            IGNORE_MISSING
        );

        if (!$record) return null;

        return new self(
            $record->id, $record->shadowuserid, $rawtoken, $record->expireson, $record->lastaccessedon, $record->isrevoked
        );
    }

    public static function access_session(string $rawtoken): self {
        global $DB, $PLUGIN_URL, $MODULE;
        /** @var moodle_database $DB */

        $previoussession = self::from_token($rawtoken);

        if (!$previoussession || !$previoussession->is_valid()) {
            throw new moodle_exception('notloggedin', $MODULE, new moodle_url($PLUGIN_URL . '/landing.php'));
        }

        $record = (object) [
            'id' => $previoussession->id,
            'lastaccessedon' => time()
        ];

        $DB->update_record('local_publictestlink_session', $record);

        return new self(
            $previoussession->id,
            $previoussession->shadowuserid,
            $previoussession->rawtoken,
            $previoussession->expireson,
            $record->lastaccessedon,
            $previoussession->isrevoked
        );
    }

    public static function revoke_session(string $rawtoken): ?self {
        global $DB;
        /** @var moodle_database $DB */

        $previoussession = self::from_token($rawtoken);

        if (!$previoussession) return null;

        $record = (object) [
            'id' => $previoussession->id,
            'isrevoked' => 1
        ];

        $DB->update_record('local_publictestlink_session', $record);

        return new self(
            $previoussession->id,
            $previoussession->shadowuserid,
            $previoussession->rawtoken,
            $previoussession->expireson,
            $previoussession->lastaccessedon,
            $record->isrevoked
        );
    }

    public static function login(publictestlink_shadow_user $shadowuser) {
        global $DB, $MODULE, $PLUGIN_URL;
        /** @var moodle_database $DB */

        $session = self::create($shadowuser->get_id());
        $session->store_cookie();

        return $session;
    }

    public static function logout() {
        $rawtoken = self::get_cookie();
        if (!empty($rawtoken)) self::revoke_session($rawtoken);
        self::revoke_cookie();
    }

    public function store_cookie() {
        setcookie(
            self::COOKIE_NAME,
            $this->rawtoken,
            [
                'expires'  => $this->expireson,
                'path'     => '/',
                'secure'   => is_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    public static function revoke_cookie() {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');
    }

    public static function get_cookie() {
        return $_COOKIE[self::COOKIE_NAME];
    }

    public static function check_session(): ?self {
        $rawtoken = self::get_cookie();
        if (empty($rawtoken)) return null;

        try {
            return self::access_session($rawtoken);
        } catch (moodle_exception $e) {
            if ($e->errorcode === 'notloggedin') {
                return null;
            }
            throw $e;
        }
    }

    public function is_expired(): bool {
        return $this->expireson < time();
    }

    public function is_valid(): bool {
        return !$this->is_expired() && !$this->isrevoked;
    }

    public function get_user(): publictestlink_shadow_user {
        return publictestlink_shadow_user::from_id($this->shadowuserid);
    }
}