<?php
require_once __DIR__ . '/../lib/wa_hooks.php';

// TODO: Query DB for sessions happening tomorrow and call notifyPatientReminder()
// Example:
// $sql = "SELECT id FROM bookings WHERE DATE(date_time) = DATE(NOW() + INTERVAL 1 DAY)";
// foreach ($results as $row) {
//     notifyPatientReminder($row['id']);
// }
