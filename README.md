## Usage

### Making a Quiz Public

1. Navigate to your Moodle course
2. Create or edit an existing Quiz
3. In the quiz settings form, find the **"Make quiz public"** checkbox under the *Timing* section
4. Check the box and save your changes
5. Share the quiz URL - users can access it without logging in

> 💡 **Tip**: Public quiz links work for both anonymous users and logged-in users. No special URL format is needed.

### Disabling Public Access

1. Edit the quiz
2. Uncheck the **"Make quiz public"** checkbox
3. Save changes
4. The quiz will require login again

> ⚠️ **Note**: Disabling public access does not delete existing quiz attempts. Previous anonymous attempts remain in the system.

---

## Developer Documentation

### Database Structure

The plugin creates a `local_publictestlink` table:

| Field | Type | Description |
|-------|------|-------------|
| id | INT(10) | Auto-increment primary key |
| quizid | INT(10) | Foreign key to quiz.coursemodule |
| ispublic | TINYINT(1) | Public access flag (0 = disabled, 1 = enabled) |
| timecreated | INT(10) | Timestamp of record creation |
| timemodified | INT(10) | Timestamp of last modification |

**Indexes:**
- Primary key on `id`
- Unique key on `quizid` (one record per quiz)

**Engine:** InnoDB
**Charset:** utf8mb4

---

### Core Functions

#### `local_publictestlink_coursemodule_standard_elements()`

Adds the public quiz checkbox to quiz settings form.

| Property | Details |
|----------|---------|
| **Hook** | `coursemodule_standard_elements` |
| **Location** | `lib.php` |
| **Purpose** | Injects "Make quiz public" checkbox into quiz form |
| **Since** | Version 1.0.0 |

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$formwrapper` | `moodleform_mod` | The form wrapper instance containing module data |
| `$mform` | `MoodleQuickForm` | The Moodle form builder instance |

**Process Flow:**
1. Validates if current module is a quiz
2. Retrieves existing public status from database
3. Creates advanced checkbox element
4. Inserts checkbox before "Timing" section
5. Sets default value and help button

---

#### `local_publictestlink_coursemodule_edit_post_actions()`

Saves the checkbox value when quiz settings are updated.

| Property | Details |
|----------|---------|
| **Hook** | `coursemodule_edit_post_actions` |
| **Location** | `lib.php` |
| **Purpose** | Persists public quiz setting to database |
| **Since** | Version 1.0.0 |

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `stdClass` | Form data submitted from quiz settings |

**Returns:**
| Type | Description |
|------|-------------|
| `stdClass` | Modified form data object |

**Process Flow:**
1. Validates quiz module context
2. Retrieves `ispublic` value from form data
3. Updates existing record or creates new one
4. Returns unmodified data object

---

#### `local_publictestlink_pre_course_module_delete()`

Cleans up public test link records when a quiz is deleted.

| Property | Details |
|----------|---------|
| **Hook** | `pre_course_module_delete` |
| **Location** | `lib.php` |
| **Purpose** | Removes orphaned database records |
| **Since** | Version 1.1.0 |
| **Status** | Ready for implementation (uncomment to enable) |

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$cm` | `cm_info` | Course module instance being deleted |

**Process Flow:**
1. Verifies module is a quiz
2. Retrieves associated public test link record
3. Deletes record from database
4. Prevents orphaned data

---

### Class: `publictestlink_quizcustom`

Located in `/classes/quizcustom.php`

**Namespace:** `local_publictestlink`
**Copyright:** 2024 Aretex Philippines Outsourcing Inc.
**Since:** Version 1.0.0

#### Class Properties

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `int` | Record ID |
| `$quizid` | `int` | Quiz course module ID |
| `$ispublic` | `bool` | Public access status |
| `$timecreated` | `int` | Creation timestamp |
| `$timemodified` | `int` | Last modified timestamp |

#### Methods

**`public static function from_quizid(int $quizid): ?self`**
Retrieves public test link settings for a specific quiz.
- **Parameters**: 
  - `$quizid` - Course module ID
- **Returns**: Instance of the class or null if not found
- **Example**: 
  ```php
  $settings = publictestlink_quizcustom::from_quizid($cm->id);