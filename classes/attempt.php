<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/shadow_user.php');


class publictestlink_attempt {
    public const string IN_PROGRESS = 'inprogress';
    public const string SUBMITTED = 'submitted';
    // public const string FINISHED = 'finished';

    public function __construct(
        protected int $id,
        protected int $shadowuserid,
        protected int $questionusageid,
        protected int $quizid,
        protected string $state,
        protected int $timestart,
        protected ?int $timeend
    ) {}

    public static function create(int $quizid, int $shadowuserid, question_usage_by_activity $quba, ?int $timestart = null) {
        global $DB;    
        /** @var moodle_database $DB */

        $timestart = $timestart ?? time();

        question_engine::save_questions_usage_by_activity($quba);
        
        $record = (object) [
            'shadowuserid' => $shadowuserid,
            'questionusageid' => $quba->get_id(),
            'quizid' => $quizid,
            'state' => self::IN_PROGRESS,
            'timestart' => $timestart,
            'timeend' => null
        ];
        $id = $DB->insert_record('local_publictestlink_quizattempt', $record);
        
        return new self(
            $id,
            $record->shadowuserid,
            $record->questionusageid,
            $record->quizid,
            $record->state,
            $record->timestart,
            $record->timeend
        );
    }

    public static function get_existing_attempt(int $quizid, int $shadowuserid): ?self {
        global $DB;
        /** @var moodle_database $DB */

        $record = $DB->get_record_sql(
            "SELECT *
            FROM {local_publictestlink_quizattempt}
            WHERE quizid = :quizid
                AND shadowuserid = :shadowuserid
                AND state = :inprogress
            ORDER BY timestart DESC
            LIMIT 1",
            [
                'quizid' => $quizid,
                'shadowuserid' => $shadowuserid,
                'inprogress' => self::IN_PROGRESS
            ]
        );

        if (!$record) return null;

        return new self(
            $record->id,
            $record->shadowuserid,
            $record->questionusageid,
            $record->quizid,
            $record->state,
            $record->timestart,
            $record->timeend
        );
    }

    public static function count_submitted_attempts(int $quizid, int $shadowuserid) {
        global $DB;
        /** @var moodle_database $DB */

        return $DB->count_records('local_publictestlink_quizattempt', [
            'quizid' => $quizid,
            'shadowuserid' => $shadowuserid,
            'state' => self::SUBMITTED
        ]);
    }

    public static function from_id(int $id) {
        global $DB;
        /** @var moodle_database $DB */
        $record = $DB->get_record('local_publictestlink_quizattempt', ['id' => $id], '*', MUST_EXIST);
        return new self(
            $record->id,
            $record->shadowuserid,
            $record->questionusageid,
            $record->quizid,
            $record->state,
            $record->timestart,
            $record->timeend
        );
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_shadow_user(): publictestlink_shadow_user {
        return publictestlink_shadow_user::from_id($this->shadowuserid);
    }

    public function get_quba(): question_usage_by_activity {
        return question_engine::load_questions_usage_by_activity($this->questionusageid);
    }

    public function get_quizid(): int {
        return $this->quizid;
    }

    public function get_state(): string {
        return $this->state;
    }

    public function get_state_readable(): string {
        global $MODULE;

        $map = [
            self::IN_PROGRESS => get_string('attempt_state_inprogress', $MODULE),
            self::SUBMITTED => get_string('attempt_state_submitted', $MODULE)
        ];

        return $map[$this->state];
    }

    public function get_timestart(): int {
        return $this->timestart;
    }

    public function get_timeend(): ?int {
        return $this->timeend;
    }

    public function mark_submitted() {
        global $DB;
        /** @var moodle_database $DB */

        $DB->update_record('local_publictestlink_quizattempt', [
            'id' => $this->id,
            'state' => self::SUBMITTED
        ]);

        $this->state = self::SUBMITTED;
    }

    public function is_in_progress() {
        return $this->state === self::IN_PROGRESS;
    }
}