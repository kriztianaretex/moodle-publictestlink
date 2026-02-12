<?php

use core\exception\moodle_exception;

class publictestlink_quizcustom {
    public function __construct(
        protected int $id,
        protected int $quizid,
        protected bool $ispublic
    ) {}

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

    public static function from_id(int $id): ?self {
        global $DB;
        $record = $DB->get_record('local_publictestlink_quizcustom', ['id' => $id], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $record->ispublic === "1"
        );
    }

    public static function from_quizid(int $quizid): ?self {
        global $DB;
        $record = $DB->get_record('local_publictestlink_quizcustom', ['quizid' => $quizid], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $record->ispublic === "1"
        );
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_quizid(): int {
        return $this->quizid;
    }

    public function get_ispublic(): bool {
        return $this->ispublic;
    }

    public function set_is_public(bool $ispublic) {
        global $DB;
        /** @var moodle_database $DB */

        $DB->update_record('local_publictestlink_quizcustom', [
            'id' => $this->id,
            'ispublic' => (int)$ispublic
        ]);

        $this->ispublic = $ispublic;
    }

    public function delete() {
        global $DB;
        /** @var moodle_database $DB */

        $DB->delete_records('local_publictestlink_quizcustom', ['quizid' => $this->quizid]);
    }
}