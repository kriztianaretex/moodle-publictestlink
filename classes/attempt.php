<?php

require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/shadow_user.php');

use core\exception\moodle_exception;
use mod_quiz\quiz_settings;


class publictestlink_attempt {
    /** @var string The user's still doing the attempt. */
    public const string IN_PROGRESS = 'inprogress';

    /** @var string The user has submitted their attempt. */
    public const string SUBMITTED = 'submitted';

    public function __construct(
        protected int $id,
        protected int $shadowuserid,
        protected int $questionusageid,
        protected int $quizid,
        protected string $state,
        protected int $timestart,
        protected ?int $timeend
    ) {}

    /**
     * Creates a new attempt in the database.
     * 
     * @param int $quizid The quiz ID.
     * @param int $shadowuserid The shadow user ID.
     * @param question_usage_by_activity $quba The initialized QUBA. Must have called `question_usage_by_activity::set_preferred_behaviour`.
     * @param ?int $timestart The starting time of the attempt. If not passed in, the value is taken from `time()`.
     * @return self The created attempt.
     */
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

    /**
     * Gets an existing attempt and returns it. If there isn't one, it returns `null`.
     * 
     * @param int $quizid The quiz ID.
     * @param int $shadowuserid The shadow user's ID.
     * @param string $state The state of the attempts to search for.
     * @return ?self The attempt, or `null` if none exists.
     */
    public static function get_existing_attempt(int $quizid, int $shadowuserid, string $state = self::IN_PROGRESS): ?self {
        global $DB;
        /** @var moodle_database $DB */

        $record = $DB->get_record_sql(
            "SELECT *
            FROM {local_publictestlink_quizattempt}
            WHERE quizid = :quizid
                AND shadowuserid = :shadowuserid
                AND state = :attemptstate
            ORDER BY timestart DESC
            LIMIT 1",
            [
                'quizid' => $quizid,
                'shadowuserid' => $shadowuserid,
                'attemptstate' => $state
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

    /**
     * Requires that there is an attempt and returns it, otherwise throw an error.
     * 
     * @param int $quizid The quiz ID.
     * @param int $shadowuserid The shadow user's ID.
     * @param string $state The state of the attempts to search for.
     * @return self The attempt.
     * @throws moodle_exception Thrown if there is no attempt.
     */
    public static function require_attempt(int $quizid, int $shadowuserid, string $state = self::IN_PROGRESS): self {
        $attempt = self::get_existing_attempt($quizid, $shadowuserid, $state);
        if ($attempt === null) throw new moodle_exception('invalidaccess');

        return $attempt;
    }

    /**
     * Counts all submitted attempts of a shadow user in a quiz.
     * 
     * @param int $quizid The quiz ID.
     * @param int $shadowuserid The shadow user's ID.
     */
    public static function count_submitted_attempts(int $quizid, int $shadowuserid) {
        global $DB;
        /** @var moodle_database $DB */

        return $DB->count_records('local_publictestlink_quizattempt', [
            'quizid' => $quizid,
            'shadowuserid' => $shadowuserid,
            'state' => self::SUBMITTED
        ]);
    }

    /**
     * Finds an attempt through ID. Throws an error if it doesn't exist.
     * 
     * @param int $id The attempt ID.
     * @return self The attempt.
     * @throws dml_missing_record_exception Thrown when the attempt doesn't exist.
     */
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

    /**
     * Gets all the attempts from a quiz.
     * 
     * @param int $quizid The quiz ID.
     * @return self[] The attempts, sorted by `timestart`. Empty if none found.
     */
    public static function get_all_attempts(int $quizid) {
        global $DB;
        /** @var moodle_database $DB */

        $records = $DB->get_records('local_publictestlink_quizattempt', ['quizid' => $quizid], 'timestart ASC', '*');
        return array_map(fn ($record) => new self(
            $record->id,
            $record->shadowuserid,
            $record->questionusageid,
            $record->quizid,
            $record->state,
            $record->timestart,
            $record->timeend
        ), $records);
    }

    /**
     * Gets the ID of the attempt.
     * @return int The ID.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets the shadow user of the attempt.
     * @return publictestlink_shadow_user The shadow user.
     */
    public function get_shadow_user(): publictestlink_shadow_user {
        return publictestlink_shadow_user::from_id($this->shadowuserid);
    }

    /**
     * Gets the QUBA of the attempt.
     * @return question_usage_by_activity The QUBA. No need to initialize anything after calling this function.
     */
    public function get_quba(): question_usage_by_activity {
        return question_engine::load_questions_usage_by_activity($this->questionusageid);
    }

    /**
     * Gets the quiz ID of the attempt.
     * @return int The quiz ID.
     */
    public function get_quizid(): int {
        return $this->quizid;
    }

    /**
     * Gets the quiz object of the attempt.
     * @return quiz_settings The quiz object.
     */
    public function get_quizobj(): quiz_settings {
        return quiz_settings::create($this->get_quizid());
    }

    /**
     * Gets the state of the attempt.
     * @return string The state.
     */
    public function get_state(): string {
        return $this->state;
    }

    /**
     * Gets the readable format of the current state of the attempt.
     * @return string The formatted string of the current state.
     */
    public function get_state_readable(): string {
        global $MODULE;

        $map = [
            self::IN_PROGRESS => get_string('attempt_state_inprogress', MODULE),
            self::SUBMITTED => get_string('attempt_state_submitted', MODULE)
        ];

        return $map[$this->state];
    }

    /**
     * Gets the starting time of the attempt.
     * @return int The starting time of the attempt.
     */
    public function get_timestart(): int {
        return $this->timestart;
    }

    /**
     * Gets the end time of the attempt, or `null` if the attempt is ongoing.
     * @return ?int The end time of the attempt, or `null` if the attempt is ongoing.
     */
    public function get_timeend(): ?int {
        return $this->timeend;
    }

    /**
     * Marks the attempt as submitted, adds an ending time, then commits it to the database.
     */
    public function mark_submitted() {
        global $DB;
        /** @var moodle_database $DB */

        $timenow = time();

        $DB->update_record('local_publictestlink_quizattempt', [
            'id' => $this->id,
            'state' => self::SUBMITTED,
            'timeend' => $timenow
        ]);

        $this->state = self::SUBMITTED;
        $this->timeend = $timenow;
    }

    /**
     * Returns `true` if the attempt is still in progress.
     * 
     * @return bool `true` if the attempt is still in progress, otherwise `false`.
     */
    public function is_in_progress(): float {
        return $this->state === self::IN_PROGRESS;
    }

    /**
     * Gets the total marks of the attempt. This is the `7` in `7/10`.
     * @return float The total marks of the attempt.
     */
    public function get_total_mark(): float {
        return (float)$this->get_quba()->get_total_mark();
    }

    /**
     * Gets the maximum marks of the attempt. This is the `10` in `7/10`.
     * @return float The maximum marks of the attempt.
     */
    public function get_max_mark(): float {
        return $this->get_quizobj()->get_quiz()->sumgrades;
    }

    /**
     * Gets the grade of the attempt scaled according to the max grade of the quiz.
     * If this attempt has a raw score of `7/10` and the max grade is `20`, then this method returns `14` (`7 / 10 * 20`).
     * 
     * @return float The scaled grade of the attempt.
     */
    public function get_scaled_grade(): float {
        return (float)quiz_rescale_grade(
            $this->get_total_mark(),
            $this->get_quizobj()->get_quiz(),
            false
        );
    }

    /**
     * Gets the maximum grade of the quiz, the basis for scaling the raw score.
     * "Raw score" refers to the sum of marks across all quizzes (e.g. `7/10`).
     * "Grade" refers to scaling the raw score based on the maximum grade (e.g. `7/10` -> `14/20` if max grade is 20).
     * 
     * @return float The maximum grade of the quiz.
     */
    public function get_max_grade() {
        return $this->get_quizobj()->get_quiz()->grade;
    }

    /**
     * Gets the percentage of the grade achieved against the maximum grade.
     * @return float The percentate of the grade achieved against the maximum grade. Always in the range 0 to 1 inclusive.
     */
    public function get_percentage() {
        return $this->get_scaled_grade() / $this->get_max_grade();
    }
}