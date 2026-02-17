<?php

use core\exception\moodle_exception;

/**
 * Generates and manages tokenized links.
 */
class publictestlink_link_token {
    public function __construct(
        protected int $id,
        protected int $quizid,
        protected string $token,
        protected int $timecreated
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
     * Creates a token for a quiz.
     * @param int $quizid The quiz ID.
     * @return self The tokenized link.
     */
    public static function create(int $quizid): self {
        global $DB;
        /** @var moodle_database $DB */

        $record = (object) [
            'quizid' => $quizid,
            'token' => self::generate_token(),
            'timecreated' => time()
        ];
        $id = $DB->insert_record('local_publictestlink_linktoken', $record);

        return new self(
            $id, $record->quizid, $record->token, $record->timecreated,
        );
    }

    /**
     * Deletes a token for a quiz.
     * @param int $quizid The quiz ID.
     */
    public static function delete(int $quizid) {
        global $DB;
        /** @var moodle_database $DB */
        $DB->delete_records('local_publictestlink_linktoken', ['quizid' => $quizid], IGNORE_MISSING);
    }

    /**
     * Retrieves an instance given a token.
     * @param string $token The token.
     * @return ?self The instance, or `null` if not found.
     */
    public static function from_token(string $token): ?self {
        global $DB;
        /** @var moodle_database $DB */
        $record = $DB->get_record('local_publictestlink_linktoken', ['token' => $token], "*", IGNORE_MISSING);

        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $token, $record->timecreated
        );
    }

    /**
     * Retrieves an instance given a quiz ID.
     * @param int $quizid The quiz ID.
     * @return ?self The instance, or `null` if not found.
     */
    public static function from_quizid(int $quizid): ?self {
        global $DB;
        /** @var moodle_database $DB */
        $record = $DB->get_record('local_publictestlink_linktoken', ['quizid' => $quizid], "*", IGNORE_MISSING);

        if (!$record) return null;

        return new self(
            $record->id, $quizid, $record->token, $record->timecreated
        );
    }

    /**
     * Ensures that a link is generated for a quiz. If there isn't an already existing link, a new one is generated.
     * @param int $quizid The quiz ID.
     * @return ?self The instance, or a newly created instance if there isn't one.
     */
    public static function ensure_for_quiz(int $quizid): self {
        $existing = self::from_quizid($quizid);
        if ($existing !== null) {
            return $existing;
        }

        return self::create($quizid);
    }

    /**
     * Requires that a valid token exists. If there isn't any, throw an error.
     * @param string $token The token.
     * @return self The instance.
     * @throws moodle_exception Throws when the token doesn't exist.
     */
    public static function require_token(string $token) {
        $invalidtoken = new moodle_exception('accesserror_quiznotpublic', MODULE, '/');

        if (empty($token)) throw $invalidtoken;

        $linktoken = self::from_token($token);
        if ($linktoken === null) throw $invalidtoken;

        return $linktoken;
    }

    /**
     * Gets the ID of the instance.
     * @return int The ID.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets the quiz ID attached to the instance.
     * @return int The quiz ID.
     */
    public function get_quizid(): int {
        return $this->quizid;
    }

    /**
     * Gets the token attached to the instance.
     * @return string The token.
     */
    public function get_token(): string {
        return $this->token;
    }

    /**
     * Gets the time when the instance is created.
     * @return int The time created.
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }
}