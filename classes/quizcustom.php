<?php

/**
 * Manages custom options for quizzes
 */
class publictestlink_quizcustom {
    public function __construct(
        protected int $id,
        protected int $quizid,
        protected bool $ispublic
    ) {}

    /**
     * Creates a new instance through the database and returns it.
     * @param int $quizid The quiz ID.
     * @param bool $ispublic Is the quiz public?
     * @return self The instance.
     */
    public static function create(int $quizid, bool $ispublic): self {
        global $DB;

        $record = (object) [
            'quizid' => $quizid,
            'ispublic' => (int)$ispublic
        ];
        $id = $DB->insert_record('local_publictestlink_quizcustom', $record);

        return new self(
            $id, $record->quizid, $record->ispublic
        );
    }

    /**
     * Returns the instance given an ID, otherwise returns `null` if it doesn't exist.
     * @param int $id The instance ID.
     * @return ?self The instance, or `null` if not found.
     */
    public static function from_id(int $id): ?self {
        global $DB;
        $record = $DB->get_record('local_publictestlink_quizcustom', ['id' => $id], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $record->ispublic === "1"
        );
    }

    /**
     * Returns the instance given a quiz ID, otherwise returns `null` if it doesn't exist.
     * @param int $id The quiz ID.
     * @return ?self The instance, or `null` if not found.
     */
    public static function from_quizid(int $quizid): ?self {
        global $DB;
        $record = $DB->get_record('local_publictestlink_quizcustom', ['quizid' => $quizid], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $record->ispublic === "1"
        );
    }

    /**
     * Gets the instance ID.
     * @return int The instance ID.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets the quiz ID.
     * @return int The quiz ID.
     */
    public function get_quizid(): int {
        return $this->quizid;
    }

    /**
     * Gets the quiz's availability to non-users.
     * @return bool `true` if the quiz is public, `false` otherwise.
     */
    public function get_ispublic(): bool {
        return $this->ispublic;
    }

    /**
     * Sets the quiz's availability to non-users.
     * @param bool $ispublic `true` if the quiz is public, `false` otherwise.
     */
    public function set_is_public(bool $ispublic) {
        global $DB;
        /** @var moodle_database $DB */

        $DB->update_record('local_publictestlink_quizcustom', [
            'id' => $this->id,
            'ispublic' => (int)$ispublic
        ]);

        $this->ispublic = $ispublic;
    }

    /**
     * Deletes the instance from the database.
     */
    public function delete() {
        global $DB;
        /** @var moodle_database $DB */

        $DB->delete_records('local_publictestlink_quizcustom', ['quizid' => $this->quizid]);
    }
}