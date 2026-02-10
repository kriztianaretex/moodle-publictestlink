<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/questionlib.php');


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

    public static function get_or_create(
        int $quizid,
        int $shadowuserid,
        question_usage_by_activity $quba
    ): self {
        global $DB;

        $record = $DB->get_record_sql(
            "SELECT *
            FROM {local_publictestlink_quizattempt}
            WHERE quizid = :quizid
                AND shadowuserid = :shadowuserid
                AND state = :inprogress
            ORDER BY timestart DESC",
            [
                'quizid' => $quizid,
                'shadowuserid' => $shadowuserid,
                'inprogress' => self::IN_PROGRESS
            ]
        );

        if ($record) {
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

        return self::create($quizid, $shadowuserid, $quba);
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

    public function get_timestart(): int {
        return $this->timestart;
    }

    public function get_timeend(): ?int {
        return $this->timeend;
    }
}