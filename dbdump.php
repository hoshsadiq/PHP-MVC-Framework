<?php

define('DS', DIRECTORY_SEPARATOR);
$dir = __DIR__ . DS;
$database = 'overheard';
$mysql = $_SERVER["MYSQL_HOME"] . DS;

$cmdexp = $mysql . "mysqldump -h localhost -u root --add-drop-database --database $database > ${dir}db.sql";
$cmdimp = $mysql . DS . "mysql --host=localhost --user=root $database < ${dir}db.sql";

echo '<div style="padding: 10px 20px; margin-bottom: 30px; background: #eee; border: 2px solid #ddd;"><a href="?export">Export</a> or <a href="?import">Import</a><a href="./" style="float: right;">Back to homepage</a></div>';


$do = $_SERVER['QUERY_STRING'];
if ($do == 'export') {
    echo 'Exporting to <i>db.sql</i>...<br> Output:<pre>';
    $output = `$cmdexp`;
    var_dump($output);
    echo '</pre>';
} elseif ($do == 'import') {

    if (file_exists($dir . 'db.sql')) {
        echo "Importing from <i>db.sql</i> to `$database`...<br> Output:<pre>";
        $output = `$cmdimp`;
        var_dump($output);
        echo '</pre>';
    } else {
        echo 'File db.sql not found';
    }
}

?>