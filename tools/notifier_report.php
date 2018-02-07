<?php
// -----
// Command-line script, takes the path to a collection of files to be searched for
// Zen Cart notify actions.
//
if (empty($_GET['file_path'])) {
    exit('Nothing to do, no "file_path" argument.');
}

if (!is_dir($_GET['file_path'])) {
    exit($_GET['file_path'] . ' is not a directory, please try again.');
}

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_GET['file_path']));

$it->rewind();
while ($it->valid()) {
    if (!$it->isDot() && pathinfo($it->key(), PATHINFO_EXTENSION) == 'php') {
        $fh = @fopen($it->key(), "r");
        if ($fh) {
            $notifiers = array();
            while (($buffer = fgets($fh, 4096)) !== false) {
                if (($next = strpos($buffer, '->notify')) !== false) {
                    $notifier_name = 'unknown';
                    $next += strlen('->notify') + 1;
                    $notifier_parms = '';
                    if (strpos($buffer, ',', $next) === false) {
                        if (strpos($buffer, ')', $next) !== false) {
                            $notifier_name = substr($buffer, $next, strpos($buffer, ')', $next) - $next);
                        }
                    } else {
                        $name_endpos = strpos($buffer, ',', $next);
                        $notifier_name = substr($buffer, $next, $name_endpos - $next);
                        $notifier_parms = str_replace(array(');', "\n", "\r"), '', substr($buffer, $name_endpos + 1));
                    }
                    $notifier_name = str_replace(array('"', "'", '('), '', $notifier_name);
                    $notifiers[] = "notifier_name: $notifier_name, parameters: ($notifier_parms)";
//                    $notifiers[] = $buffer;
                }
            }
            if (!feof($fh)) {
                echo "Error: unexpected fgets() fail\n";
            }
            if (count($notifiers) != 0) {
                error_log($it->getSubPathName() . PHP_EOL, 3, 'notifier_report.txt');
                foreach ($notifiers as $notify) {
                    error_log("\t\t" . $notify . PHP_EOL, 3, 'notifier_report.txt');
                }
                error_log(PHP_EOL, 3, 'notifier_report.txt');
            }
            fclose($fh);
        }
/*
        echo 'SubPathName: ' . $it->getSubPathName() . "<br />";
        echo 'SubPath:     ' . $it->getSubPath() . "<br />";
        echo 'Key:         ' . $it->key() . "<br /><br />";
*/
    }
    $it->next();
}
