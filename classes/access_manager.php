<?php

require_once('../locallib.php');
require_once(__DIR__ . '/attempt.php');
require_once(__DIR__ . '/shadow_user.php');
require_once(__DIR__ . '/quizcustom.php');

use mod_quiz\quiz_settings;

/**
 * Manages access for various parts of the quiz flow.
 */
class publictestlink_access_manager {
    public function __construct(
        protected quiz_settings $quizobj,
        protected int $timenow,
        protected ?publictestlink_shadow_user $shadowuser = null,
        protected ?publictestlink_attempt $attempt = null,
    ) { }


    /**
     * Gets the quiz from the quiz object.
     * 
     * @return stdClass The quiz object
     */
    private function get_quiz(): stdClass {
        return $this->quizobj->get_quiz();
    }

    /**
     * Checks if people can start an attempt.
     */
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

        // Is the quiz public?
        $quizcustom = publictestlink_quizcustom::from_quizid((int)$this->quizobj->get_quizid());
        if ($quizcustom === null || !$quizcustom->get_ispublic()) {
            $reasons[] = get_string('accesserror_quiznotpublic', $MODULE);
        } else {
            // Is the quiz open now?
            if ($quiz->timeopen && $this->timenow < $quiz->timeopen) {
                $reasons[] = get_string('accesserror_quiznotopen', $MODULE);
            }
    
            // Is the quiz closed now?
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
    
                    // Has the user exceeded the number of attempts?
                    if ($attemptcount >= $attemptsallowed) {
                        $reasons[] = get_string('accesserror_maxattempts', $MODULE);
                    }
                }
            }
        }

        return $reasons;
    }

    /**
     * Gets the reasons for why one cannot access the quiz.
     * @return ?string The formatted reasons, or `null` when there is no problem with accessing the quiz.
     */
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