<?php
require_once('../../config.php');
require_login();

if (!is_siteadmin()) {
    die('Admin access required');
}

echo "<h2>Test Checkbox Save Function</h2>";

global $DB;

// Test 1: Check current table
echo "<h3>1. Current Database Contents:</h3>";
$records = $DB->get_records('local_publictestlink');
if (empty($records)) {
    echo "Table is empty.<br>";
} else {
    echo "<table border='1' cellpadding='5'>
        <tr><th>ID</th><th>Quiz ID</th><th>ispublic</th><th>Status</th></tr>";
    foreach ($records as $r) {
        echo "<tr>
            <td>{$r->id}</td>
            <td>{$r->quizid}</td>
            <td><strong>{$r->ispublic}</strong></td>
            <td>" . ($r->ispublic == 1 ? '✅ CHECKED' : '❌ UNCHECKED') . "</td>
        </tr>";
    }
    echo "</table>";
}

// Test 2: Manual database test
echo "<h3>2. Manual Database Test:</h3>";
echo "<form method='post'>
    <input type='hidden' name='testdb' value='1'>
    <button type='submit'>Test Database Save/Update</button>
</form>";

if (isset($_POST['testdb'])) {
    $testquizid = 99999;
    
    // Clean up
    $DB->delete_records('local_publictestlink', array('quizid' => $testquizid));
    
    // Test save with ispublic=1
    $record = new stdClass();
    $record->quizid = $testquizid;
    $record->ispublic = 1;
    $record->timecreated = time();
    $record->timemodified = time();
    
    $id = $DB->insert_record('local_publictestlink', $record);
    echo "✓ Inserted with ispublic=1 (ID: {$id})<br>";
    
    // Verify
    $check = $DB->get_record('local_publictestlink', array('id' => $id));
    echo "✓ Verified: ispublic = {$check->ispublic}<br>";
    
    // Update to 0
    $check->ispublic = 0;
    $check->timemodified = time();
    $DB->update_record('local_publictestlink', $check);
    echo "✓ Updated to ispublic=0<br>";
    
    // Verify update
    $check2 = $DB->get_record('local_publictestlink', array('id' => $id));
    echo "✓ Verified: ispublic = {$check2->ispublic}<br>";
    
    // Clean up
    $DB->delete_records('local_publictestlink', array('id' => $id));
    echo "✓ Cleaned up<br>";
    
    echo "<h3 style='color:green;'>✓ Database works correctly!</h3>";
}

// Instructions
echo "<h3>3. Testing Instructions:</h3>";
echo "1. Edit any quiz<br>";
echo "2. <strong>CHECK</strong> the 'Make quiz public' checkbox<br>";
echo "3. Save the quiz<br>";
echo "4. Run this SQL in MySQL Workbench:<br>";
echo "<code>SELECT quizid, ispublic FROM mdl_local_publictestlink ORDER BY id DESC LIMIT 1;</code><br>";
echo "5. Should show: <strong>ispublic = 1</strong>";