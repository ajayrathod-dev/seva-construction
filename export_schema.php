<?php
require_once 'connection.php';
$tables = ['construction', 'hero_slides', 'site_settings', 'admins'];
$output = "";
foreach ($tables as $t) {
    $result = $conn->query("SHOW CREATE TABLE `$t`");
    $row = $result->fetch_row();
    $output .= $row[1] . ";\n\n";
}
file_put_contents(__DIR__ . '/schema_export.sql', $output);
echo "Schema exported to schema_export.sql\n";
