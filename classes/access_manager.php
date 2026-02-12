<?php

require_once('../locallib.php');
require_once(__DIR__ . '/attempt.php');
require_once(__DIR__ . '/shadow_user.php');
require_once(__DIR__ . '/quizcustom.php');

use mod_quiz\quiz_settings;

class publictestlink_access_manager {
    public function __construct(
        protected quiz_settings $quizobj,
        protected int $timenow,
        protected ?publictestlink_shadow_user $shadowuser = null,
        protected ?publictestlink_attempt $attempt = null,
    ) { }


    private function get_quiz(): stdClass {
        return $this->quizobj->get_quiz();
    }

    // Checks if the public can access the quiz.
    public function can_start_attempt(): bool {
        return empty($this->prevent_access());
    }

    /**
     * Checks a few conditions to see if the public can access the quiz.
     * @return string[] all reasons why one cannot access the quiz. If it's empty, they can access.
     */
    public function prevent_access(): array {
        global $MODULE;

        $reasons = [];

        $quiz = $this->get_quiz();

        $quizcustom = publictestlink_quizcustom::from_quizid((int)$this->quizobj->get_cmid());
        if ($quizcustom === null || !$quizcustom->get_ispublic()) {
            $reasons[] = get_string('accesserror_quiznotpublic', $MODULE);
        } else {
            if ($quiz->timeopen && $this->timenow < $quiz->timeopen) {
                $reasons[] = get_string('accesserror_quiznotopen', $MODULE);
            }
    
            if ($quiz->timeclose && $this->timenow > $quiz->timeclose) {
                $reasons[] = get_string('accesserror_quizclosed', $MODULE);
            }
    
            if ($this->shadowuser) {
                $attemptsallowed = (int)$this->quizobj->get_num_attempts_allowed();
                if ($attemptsallowed !== 0) {
                    $attemptcount = publictestlink_attempt::count_submitted_attempts(
                        $quiz->id,
                        $this->shadowuser->get_id()
                    );
    
                    if ($attemptcount >= $attemptsallowed) {
                        $reasons[] = get_string('accesserror_maxattempts', $MODULE);
                    }
                }
            }
    
            // TODO time limit handling
            if ($this->attempt && $quiz->timelimit) {
                $end = $this->attempt->get_timestart() + $quiz->timelimit;
                if ($this->timenow > $end) {
                    $reasons[] = get_string('accesserror_timelimitexpired', $MODULE);
                }
            }
        }

        return $reasons;
    }

    public function get_formatted_reasons(): ?string {
        $accessprevents = $this->prevent_access();
        if (empty($accessprevents)) return null;

        $messages = implode(
            ", ",
            array_map(fn($v) => "$v", $accessprevents)
        );

        return (
            "You cannot access this quiz yet because of the following reasons: " .
            $messages
        );
    }
}