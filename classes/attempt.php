<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/questionlib.php');

use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;


class publictestlink_attempt {
    /** @var string to identify the "not started" state, when an attempt has been pre-generated. */
    public const string NOT_STARTED = quiz_attempt::NOT_STARTED;
    /** @var string to identify the in progress state. */
    public const string IN_PROGRESS = quiz_attempt::IN_PROGRESS;
    /** @var string to identify the overdue state. */
    public const string OVERDUE = quiz_attempt::OVERDUE;
    /** @var string to identify the submitted state, when an attempt is awaiting grading. */
    public const string SUBMITTED = quiz_attempt::SUBMITTED;
    /** @var string to identify the finished state. */
    public const string FINISHED = quiz_attempt::FINISHED;
    /** @var string to identify the abandoned state. */
    public const string ABANDONED = quiz_attempt::ABANDONED;


    /** @var int maximum number of slots in the quiz for the review page to default to show all. */
    public const int MAX_SLOTS_FOR_DEFAULT_REVIEW_SHOW_ALL = quiz_attempt::MAX_SLOTS_FOR_DEFAULT_REVIEW_SHOW_ALL;

    /** @var int amount of time considered 'immedately after the attempt', in seconds. */
    public const int IMMEDIATELY_AFTER_PERIOD = quiz_attempt::IMMEDIATELY_AFTER_PERIOD;

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
            'state' => self::NOT_STARTED,
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
                AND state IN (:notstarted, :inprogress, :overdue)
            ORDER BY timestart DESC",
            [
                'quizid' => $quizid,
                'shadowuserid' => $shadowuserid,
                'notstarted' => self::NOT_STARTED,
                'inprogress' => self::IN_PROGRESS,
                'overdue' => self::OVERDUE,
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

    public function get_id(): int {
        return $this->id;
    }

    public function get_shadow_user(): publictestlink_shadow_user {
        return publictestlink_shadow_user::from_id($this->shadowuserid);
    }

    public function get_quba(): question_usage_by_activity {
        return question_engine::load_questions_usage_by_activity($this->questionusageid);
    }

    public function get_quizobj(): quiz_settings {
        return quiz_settings::create($this->quizid);
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