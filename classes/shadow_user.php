<?php

use core\exception\moodle_exception;

/**
 * Cleans the name through the following:
 * * Remove trailing whitespace
 * * `clean_param($name, PARAM_TEXT)`
 * 
 * Then, throws an exception when the name is empty.
 * 
 * @param string $name The name.
 * @return string The cleaned name.
 * @throws moodle_exception Thrown if the name is empty.
 */
function clean_name(string $name) {
    $name = trim($name);
    $name = clean_param($name, PARAM_TEXT);

    if (empty($name)) throw new moodle_exception('missingname');

    return $name;
}

/**
 * Cleans the email through the following:
 * * Change all letters to lowercase
 * * `clean_param($email, PARAM_EMAIL)`
 * Then, throws an excpetion when the email is empty.
 * 
 * @param string $email The email.
 * @return string The cleaned email.
 * @throws moodle_exception Thrown if the email is empty.
 */
function clean_email(string $email) {
    $email = core_text::strtolower(trim($email));
    $email = clean_param($email, PARAM_EMAIL);

    if (!validate_email($email)) throw new moodle_exception('invalidemail');

    return $email; 
}

/**
 * Manages shadow users.
 */
class publictestlink_shadow_user {
    public function __construct(
        protected int $id,
        protected string $email,
        protected string $firstname,
        protected string $lastname
    ) {}

    /**
     * Creates a new shadow user in the database given basic information.
     * 
     * @param string $email The email of the shadow user. Will be cleaned.
     * @param string $firstname The first name of the shadow user. Will be cleaned.
     * @param string $lastname The last name of the shadow user. Will be cleaned.
     * @return self The instance.
     * @see clean_email()
     * @see clean_name()
     */
    public static function create(string $email, string $firstname, string $lastname): self {
        global $DB;
    
        $email = clean_email($email);

        $firstname = clean_name($firstname);
        $lastname = clean_name($lastname);

        $record = (object) [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'timecreated' => time()
        ];
        $id = $DB->insert_record('local_publictestlink_shadowuser', $record);

        return new self(
            $id, $record->email, $record->firstname, $record->lastname
        );
    }

    /**
     * Gets the user from the ID.
     * @param int $id The shadow user ID.
     * @return ?self The user, or `null` if not found.
     */
    public static function from_id(int $id): ?self {
        global $DB;
        $record = $DB->get_record('local_publictestlink_shadowuser', ['id' => $id], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->email, $record->firstname, $record->lastname
        );
    }

    /**
     * Gets the user from their email.
     * @param string $email The email of the shadow user. Will be cleaned.
     * @return ?self The user, or `null` if not found.
     * @see clean_email()
     */
    public static function from_email(string $email): ?self {
        global $DB;
        $email = clean_email($email);

        $record = $DB->get_record('local_publictestlink_shadowuser', ['email' => $email], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->email, $record->firstname, $record->lastname
        );
    }

    /**
     * Gets the shadow user ID.
     * @return int The shadow user ID.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets the email of the shadow user.
     * @return string The shadow user's email.
     */
    public function get_email(): string {
        return $this->email;
    }

    /**
     * Gets the first name of the shadow user.
     * @return string The shadow user's first name.
     */
    public function get_firstname(): string {
        return $this->firstname;
    }

    /**
     * Gets the last name of the shadow user.
     * @return string The shadow user's last name.
     */
    public function get_lastname(): string {
        return $this->lastname;
    }

    /**
     * Updates the names of the shadow user.
     * @param string $firstname The first name of the shadow user. Will be cleaned.
     * @param string $lastname The last name of the shadow user. Will be cleaned.
     * @see clean_name()
     */
    public function update_names(string $firstname, string $lastname) {
        global $DB;
        /** @var moodle_database $DB */

        $firstname = clean_name($firstname);
        $lastname = clean_name($lastname);

        $DB->update_record('local_publictestlink_shadowuser', [
            'id' => $this->id,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]);

        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }
}