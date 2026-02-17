<?php

require_once('../locallib.php');
require_once(__DIR__ . '/shadow_user.php');

use core\url as moodle_url;
use core\exception\moodle_exception;


/**
 * Manages sessions for non-users.
 */
class publictestlink_session {
    /** @var int The amount of seconds for the session to expire. */
    private const EXPIRE_SECONDS = 2 * HOURSECS;

    /** @var string The cookie name used for storing the session in the database. */
    private const COOKIE_NAME = 'local_publictestlink_session';

    public function __construct(
        protected int $id,
        protected int $shadowuserid,

        /** @var string The raw token. */
        protected string $rawtoken,

        protected int $expireson,
        protected int $lastaccessedon,
        protected bool $isrevoked,
    ) {}

    /**
     * Generates a random 64-char length token.
     * Only returns hexadecimal characters (0123456789abcdef).
     * @return string The token.
     */
    private static function generate_token(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hashes the token using SHA256.
     * @param string $token The token.
     * @return string The 64-char hash.
     */
    private static function hash_token(string $token): string {
        return hash('sha256', $token);
    }

    /**
     * Creates a session given a shadow user.
     * @param int $shadowuserid The shadow user's ID.
     * @return self The session.
     */
    public static function create(int $shadowuserid): self {
        global $DB;

        $rawtoken = self::generate_token();
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

    /**
     * Gets a session instance given the raw token.
     * @param string $rawtoken The raw token.
     * @return ?self The instance, or `null` if it doesn't exist.
     */
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

    /**
     * Gets the session through a raw token and then updates the `lastaccessedon` time. Throws an error when the raw token is invalid.
     * @param string $rawtoken The raw token.
     * @return self The accessed session.
     * @throws moodle_exception Throws when the raw token doesn't match any session.
     */
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

    /**
     * Revokes the session through the raw token. Returns `null` if the session is not found.
     * @param string $rawtoken The raw token.
     * @return ?self The revoked session, or `null` if not found.
     */
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

    /**
     * Logs the user in by creating a session and then storing the cookie in the browser.
     * @return self The new session.
     */
    public static function login(publictestlink_shadow_user $shadowuser): self {
        $session = self::create($shadowuser->get_id());
        $session->store_cookie();

        return $session;
    }

    /**
     * Logs the user out by revoking the session and then removing the cookie from the browser.
     */
    public static function logout() {
        $rawtoken = self::get_cookie();
        if (!empty($rawtoken)) self::revoke_session($rawtoken);
        self::revoke_cookie();
    }

    /**
     * Stores the cookie in the browser.
     */
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

    /**
     * Revokes the cookie from the browser.
     */
    public static function revoke_cookie() {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');
    }

    /**
     * Gets the token from the cookie stored in the browser, or `null` if not found.
     * @return ?string The token, or `null` if not found.
     */
    public static function get_cookie(): ?string {
        $value = $_COOKIE[self::COOKIE_NAME] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Accesses the session through the token stored in the cookie.
     * @return ?self The session instance, or `null` if not found.
     */
    public static function check_session(): ?self {
        $rawtoken = self::get_cookie();
        if ($rawtoken === null) return null;

        try {
            return self::access_session($rawtoken);
        } catch (moodle_exception $e) {
            if ($e->errorcode === 'notloggedin') {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Returns `true` if the session has expired through `expireson`.
     * @return bool `true` if the session has expired, `false` otherwise.
     */
    public function is_expired(): bool {
        return $this->expireson < time();
    }

    /**
     * Returns `true` if the session is valid according to all restrictions (expiry & revoked status)
     * @return bool `true` if the session is valid, `false` otherwise.
     */
    public function is_valid(): bool {
        return !$this->is_expired() && !$this->isrevoked;
    }

    /**
     * Gets the shadow user attached to the session.
     * @return publictestlink_shadow_user The shadow user.
     */
    public function get_user(): publictestlink_shadow_user {
        return publictestlink_shadow_user::from_id($this->shadowuserid);
    }
}