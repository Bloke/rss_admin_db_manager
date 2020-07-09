<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'rss_admin_db_manager';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '4.5.1';
$plugin['author'] = 'Rob Sable';
$plugin['author_uri'] = 'http://www.wilshireone.com/';
$plugin['description'] = 'Database management system.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php. Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional. If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'rss_admin_db_manager';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML. Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '4.5';
$plugin['author'] = 'Rob Sable';
$plugin['author_uri'] = 'http://www.wilshireone.com/';
$plugin['description'] = 'Database management system.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface')) @include_once ('zem_tpl.php');

// --- BEGIN PLUGIN CODE ---

if (txpinterface === 'admin') {
    add_privs('rss_db_man', '1');
    register_tab("extensions", "rss_db_man", "DB Manager");
    register_callback("rss_db_man", "rss_db_man");
    add_privs('rss_sql_run', '1');
    register_tab("extensions", "rss_sql_run", "Run SQL");
    register_callback("rss_sql_run", "rss_sql_run");
    add_privs('rss_db_bk', '1');
    register_tab("extensions", "rss_db_bk", "DB Backup");
    register_callback("rss_db_bk", "rss_db_bk");
}

function rss_db_bk($event, $step)
{
    global $prefs, $rss_dbbk_path, $rss_dbbk_dump, $rss_dbbk_mysql, $rss_dbbk_lock, $rss_dbbk_txplog, $rss_dbbk_debug, $DB;
    if (!isset($rss_dbbk_lock)) {
        $rss_dbbk_lock = "1";
        $rs = safe_insert('txp_prefs', "name='rss_dbbk_lock', val='$rss_dbbk_lock'");
    }

    if (!isset($rss_dbbk_txplog)) {
        $rss_dbbk_txplog = "1";
        $rs = safe_insert('txp_prefs', "name='rss_dbbk_txplog', val='$rss_dbbk_txplog'");
    }

    if (!isset($rss_dbbk_debug)) {
        $rss_dbbk_debug = "0";
        $rs = safe_insert('txp_prefs', "name='rss_dbbk_debug', val='$rss_dbbk_debug'");
    }

    if (empty($rss_dbbk_path)) {
        $rss_dbbk_path = get_pref('tempdir', sys_get_temp_dir());
        $rs = safe_upsert('txp_prefs', "val='" . addslashes($rss_dbbk_path) . "'", "name='rss_dbbk_path'");
    }

    if (empty($rss_dbbk_dump)) {
        $rss_dbbk_dump = "mysqldump";
        $rs = safe_upsert('txp_prefs', "val='" . addslashes($rss_dbbk_dump) . "'", "name='rss_dbbk_dump'");
    }

    if (empty($rss_dbbk_mysql)) {
        $rss_dbbk_mysql = "mysql";
        $rs = safe_upsert('txp_prefs', "val='" . addslashes($rss_dbbk_mysql) . "'", "name='rss_dbbk_mysql'");
    }

    include (txpath . '/include/txp_prefs.php');

    $bkpath = $rss_dbbk_path;
    $iswin = preg_match('/Win/', php_uname());
    $mysql_hup = ' -h' . $DB->host . ' -u' . $DB->user . ' -p' . escapeshellcmd($DB->pass);
    $txplogps = ps('rss_dbbk_txplog');

    if (ps("save")) {
        pagetop("DB Manager", "Preferences Saved");
        safe_update("txp_prefs", "val = '" . addslashes(ps('rss_dbbk_path')) . "'", "name = 'rss_dbbk_path'");
        safe_update("txp_prefs", "val = '" . addslashes(ps('rss_dbbk_dump')) . "'", "name = 'rss_dbbk_dump'");
        safe_update("txp_prefs", "val = '" . addslashes(ps('rss_dbbk_mysql')) . "'", "name = 'rss_dbbk_mysql'");
        safe_update("txp_prefs", "val = '" . ps('rss_dbbk_lock') . "'", "name = 'rss_dbbk_lock'");
        if (isset($txplogps)) safe_update("txp_prefs", "val = '" . ps('rss_dbbk_txplog') . "'", "name = 'rss_dbbk_txplog'");
        safe_update("txp_prefs", "val = '" . ps('rss_dbbk_debug') . "'", "name = 'rss_dbbk_debug'");
        header("Location: index.php?event=rss_db_bk");
    } elseif (gps("bk")) {
        $bk_table = (gps("bk_table")) ? " --tables " . gps("bk_table") . " " : "";
        $tabpath = (gps("bk_table")) ? "-" . gps("bk_table") : "";
        $gzip = gps("gzip");
        $filename = time() . '-' . $DB->db . $tabpath;
        $backup_path = $bkpath . '/' . $filename . '.sql';
        $lock = ($rss_dbbk_lock) ? "" : " --skip-lock-tables --skip-add-locks ";
        echo $txplogps;
        $nolog = ($rss_dbbk_txplog) ? "" : " --ignore-table=" . $DB->db . ".txp_log ";
        $nolog = (isset($bk_table) && gps("bk_table") == "txp_log") ? "" : $nolog;

        if ($gzip) {
            $backup_path.= '.gz';
            $backup_cmd = $rss_dbbk_dump . $mysql_hup . ' -Q --add-drop-table ' . $lock . $nolog . $DB->db . $bk_table . ' | gzip > ' . $backup_path;
        } else {
            $backup_cmd = $rss_dbbk_dump . $mysql_hup . ' -Q --add-drop-table ' . $lock . $nolog . $DB->db . $bk_table . ' > ' . $backup_path;
        }

        $bkdebug = ($rss_dbbk_debug) ? $backup_cmd : '';
        $error = "";

        if (function_exists('passthru')) {
            passthru($backup_cmd, $error);
        } else {
            $dumpIt = popen($backup_cmd, 'r');
            pclose($dumpIt);
        }

        if (!is_writable($bkpath)) {
            pagetop("DB Manager", "BACKUP FAILED: folder is not writable");
        } elseif ($error) {
            unlink($backup_path);
            pagetop("DB Manager", "BACKUP FAILED.  ERROR NO: " . $error);
        } elseif (!is_file($backup_path)) {
            pagetop("DB Manager", "BACKUP FAILED.  ERROR NO: " . $error);
        } elseif (filesize($backup_path) == 0) {
            unlink($backup_path);
            pagetop("DB Manager", "BACKUP FAILED.  ERROR NO: " . $error);
        } else {
            pagetop("DB Manager", "Backed Up: " . $DB->db . " to " . $filename);
        }
    } elseif (gps("download")) {
        $fn = gps("download");
        $file_path = $bkpath . '/' . $fn;
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        if (substr($fn, -2) == "gz") header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=" . basename($file_path) . ";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($file_path));
        @readfile($file_path);
    } elseif (gps("restore")) {
        if (stristr(gps("restore") , '.gz')) {
            $backup_cmd = 'gunzip < ' . $bkpath . '/' . gps("restore") . ' | ' . $rss_dbbk_mysql . $mysql_hup . ' ' . $DB->db;
        } else {
            $backup_cmd = $rss_dbbk_mysql . $mysql_hup . ' ' . $DB->db . ' < ' . $bkpath . '/' . gps("restore");
        }

        $bkdebug = ($rss_dbbk_debug) ? $backup_cmd : '';
        $error = "";

        if (function_exists('passthru')) {
            passthru($backup_cmd, $error);
        } else {
            $dumpIt = popen($backup_cmd, 'r');
            pclose($dumpIt);
        }

        if ($error) {
            pagetop("DB Manager", "FAILED TO RESTORE: " . $error);
        } else {
            pagetop("DB Manager", "Restored: " . gps("restore") . " to " . $DB->db);
        }
    } elseif (gps("delete")) {
        if (is_file($bkpath . '/' . gps("delete"))) {
            if (!unlink($bkpath . '/' . gps("delete"))) {
                pagetop("DB Manager", "Unable to Delete: " . gps("delete"));
            } else {
                pagetop("DB Manager", "Deleted: " . gps("delete"));
            }
        } else {
            pagetop("DB Manager", "Unable to Delete: " . gps("delete"));
        }
    } else {
        pagetop("DB Backup");
    }

    $gzp = (!$iswin) ? " | " . href('gzipped file', "index.php?event=rss_db_bk&amp;bk=$DB->db&amp;gzip=1") : "";
    $sqlversion = getRow("SELECT VERSION() AS version");
    $sqlv = explode("-", $sqlversion['version']);
    $allownologs = ((float)$sqlv[0] >= (float)"4.1.9") ? tda(gTxt('Include txp_log:') , ' style="text-align:right;vertical-align:middle"') . tda(yesnoRadio("rss_dbbk_txplog", $rss_dbbk_txplog) , ' style="text-align:left;vertical-align:middle"') : '';

    if (isset($bkdebug) && $bkdebug) {
        echo '<p align="center">' . $bkdebug . '</p>';
    }

    echo startTable('txp-list') .
        form(
            tr(
                tda(gTxt('Lock Tables:'), ' style="text-align:right;vertical-align:middle"') .
                tda(yesnoRadio("rss_dbbk_lock", $rss_dbbk_lock), ' style="text-align:left;vertical-align:middle"') .
                $allownologs .
                tda(gTxt('Debug Mode:'), ' style="text-align:right;vertical-align:middle"') .
                tda(yesnoRadio("rss_dbbk_debug", $rss_dbbk_debug), ' style="text-align:left;vertical-align:middle"') .
                tda(fInput("submit", "save", gTxt("save") , "publish") .
                eInput("rss_db_bk") .
                sInput('saveprefs'), " colspan=\"2\" class=\"noline\"")
            ) .
            tr(
                tda(gTxt('Backup Path:'), ' style="text-align:right;vertical-align:middle"') .
                tda(text_input("rss_dbbk_path", $rss_dbbk_path, '50'), ' colspan="15"')
            ) .
            tr(
                tda(gTxt('mysqldump Path:'), ' style="text-align:right;vertical-align:middle"') .
                tda(text_input("rss_dbbk_dump", $rss_dbbk_dump, '50'), ' colspan="15"')
            ) .
            tr(
                tda(gTxt('mysql Path:'), ' style="text-align:right;vertical-align:middle"') .
                tda(text_input("rss_dbbk_mysql", $rss_dbbk_mysql, '50'), ' colspan="15"')
            )
        ) .
        endTable() .
        tag_start('div', array('class' => 'txp-listtables')) .
        startTable("txp-list", '', 'txp-list') .
        tr(
            tda(hed('Create a new backup of the ' . $DB->db . ' database' .
                br .
                href('.sql file', "index.php?event=rss_db_bk&amp;bk=$DB->db") . $gzp, 3), ' colspan="7" style="text-align:center;"')
        ) .
        tr(
            tdcs(hed("Previous Backup Files", 1), 7)
        ) .
        tr(
            hcell("No.") .
            hcell("Backup File Name") .
            hcell("Backup Date/Time") .
            hcell("Backup File Size") .
            hcell("") .
            hcell("") .
            hcell("")
        );

    $totalsize = 0;
    $no = 0;

    if (!is_folder_empty($bkpath)) {
        if (file_exists($bkpath)) {
            $database_files = array();
            $dir = new DirectoryIterator($bkpath);

            foreach ($dir as $file) {
                $extension = $file->getExtension();

                if (!$file->isFile() || !$file->isReadable() || !in_array($extension, array('sql', 'gz'))) {
                    continue;
                }


                $mtime = $file->getMTime();
                $database_files[$mtime] = $file->getFilename();
            }

            krsort($database_files);

            foreach ($database_files as $stamp => $filename) {
                $no++;
                $database_text = substr($filename, 0, 50);
                $date_text = strftime("%A, %B %d, %Y [%H:%M:%S]", $stamp);
                $size_text = filesize($bkpath . '/' . $filename);
                $totalsize+= $size_text;
                echo tr(td($no) . td($database_text) . td($date_text) . td(prettyFileSize($size_text)) . '<td><a href="index.php?event=rss_db_bk&amp;download=' . $filename . '">Download</a></td>' . '<td><a href="index.php?event=rss_db_bk&amp;restore=' . $filename . '"onclick="return verify(\'' . gTxt('are_you_sure') . '\')">Restore</a></td>' . '<td><a href="index.php?event=rss_db_bk&amp;delete=' . $filename . '"onclick="return verify(\'' . gTxt('are_you_sure') . '\')">Delete</a></td>');
            }

            echo tr(tag($no . " Backup File(s)", "th", ' colspan="3"') . tag(prettyFileSize($totalsize) , "th", ' colspan="4"'));
        } else {
            echo tr(tda(hed('You have no database backups' . br . 'Create a new backup of the ' . $DB->db . ' database' . br . href('.sql file', "index.php?event=rss_db_bk&amp;bk=$DB->db") . $gzp, 3) , ' colspan="7" style="text-align:center;"'));
        }
    } else {
        echo tr(tda(hed('You have no database backups' . br . 'Create a new backup of the ' . $DB->db . ' database' . br . href('.sql file', "index.php?event=rss_db_bk&amp;bk=$DB->db") . $gzp, 3) , ' colspan="7" style="text-align:center;"'));
    }

    echo endTable().tag_end('div');
}

function rss_db_man($event, $step)
{
    global $DB;

    if (gps("opt_table")) {
        $query = "OPTIMIZE TABLE " . gps("opt_table");
        safe_query($query);
        pagetop("DB Manager", "Optimzed: " . gps("opt_table"));
    } elseif (gps("rep_table")) {
        $query = "REPAIR TABLE " . gps("rep_table");
        safe_query($query);
        pagetop("DB Manager", "Repaired: " . gps("rep_table"));
    } elseif (gps("rep_all")) {
        $query = "REPAIR TABLE " . gps("rep_all");
        safe_query($query);
        pagetop("DB Manager", "Repaired All Tables");
    } elseif (gps("drop_table")) {
        $query = "DROP TABLE " . gps("drop_table");
        safe_query($query);
        pagetop("DB Manager", "Dropped: " . gps("drop_table"));
    } else {
        pagetop("Database Manager");
    }

    $sqlversion = getRow("SELECT VERSION() AS version");
    $headatts = ' style="color:#0069D1;padding:0 10px 0 5px;"';

    echo startTable('dbinfo') .
        tr(
            hcell("Database Host:") .
            tda($DB->host, $headatts) .
            hcell("Database Name:") .
            tda($DB->db, $headatts) .
            hcell("Database User:") .
            tda($DB->user, $headatts) .
            hcell("Database Version:") .
            tda("MySQL v" . $sqlversion['version'], $headatts)
        ) .
        endTable() .
        br;

    echo startTable('list', '', 'txp-list') .
        tr(
            hcell("No.") .
            hcell("Tables") .
            hcell("Records") .
            hcell("Data Usage") .
            hcell("Index Usage") .
            hcell("Total Usage") .
            hcell("Overhead") .
            hcell("ErrNo") .
            hcell("Repair") .
            hcell("Backup") .
            hcell("Drop")
        );

    if (version_compare($sqlversion['version'], '3.23', '>=')) {
        $no = 0;
        $row_usage = 0;
        $data_usage = 0;
        $index_usage = 0;
        $overhead_usage = 0;
        $alltabs = array();
        $tablesstatus = getRows("SHOW TABLE STATUS");

        foreach ($tablesstatus as $tablestatus) {
            extract($tablestatus);
            $q = "SHOW KEYS FROM `" . $Name . "`";
            safe_query($q);
            $mysqlErrno = mysqli_errno($DB->link);
            $alltabs[] = $Name;
            $color = ($mysqlErrno != 0) ? ' style="color:#D10000;"' : ' style="color:#4B9F00;"';
            $color2 = ($Data_free > 0) ? ' style="color:#D10000;"' : ' style="color:#4B9F00;"';
            $no++;
            $row_usage+= $Rows;
            $data_usage+= $Data_length;
            $index_usage+= $Index_length;
            $overhead_usage+= $Data_free;
            echo tr(td($no) . td(href($Name, "index.php?event=rss_sql_run&amp;tn=" . $Name)) . td(" " . $Rows) . td(prettyFileSize($Data_length)) . td(prettyFileSize($Index_length)) . td(prettyFileSize($Data_length + $Index_length)) . tda(prettyFileSize($Data_free) , $color2) . tda(" " . $mysqlErrno, $color) . td(href("Repair", "index.php?event=rss_db_man&amp;rep_table=" . $Name)) . td(href("Backup", "index.php?event=rss_db_bk&amp;bk=1&amp;bk_table=" . $Name) . '<td><a href="index.php?event=rss_db_man&amp;drop_table=' . $Name . '"onclick="return verify(\'' . gTxt('are_you_sure') . '\')">Drop</a></td>'));
        }

        echo tr(hcell("Total") . hcell($no . " Tables") . hcell(number_format($row_usage)) . hcell(prettyFileSize($data_usage)) . hcell(prettyFileSize($index_usage)) . hcell(prettyFileSize($data_usage + $index_usage)) . hcell(prettyFileSize($overhead_usage)) . hcell() . tda(href(strong("Repair All") , "index.php?event=rss_db_man&amp;rep_all=" . implode(",", $alltabs)) , ' style="text-align:center;" colspan="3"'));
    } else {
        echo tr(tda("Could Not Show Table Status Because Your MYSQL Version Is Lower Than 3.23.", ' style="text-align:center;" colspan=14"'));
    }

    echo tr(tda(href("Run SQL", "index.php?event=rss_sql_run") , ' style="text-align:center;" colspan="14"')) . endTable();
}

function rss_sql_run($event, $step)
{
    global $DB;

    pagetop("Run SQL Query");
    $text = "";
    $rsd[] = "";
    $sql_query2 = "";

    if (gps("tn")) {
        $tq = "select * from " . gps("tn");
    }

    if (gps("sql_query") || gps("tn")) {
        $sql_queries2 = (gps("sql_query")) ? trim(gps("sql_query")) : trim($tq);
        $totalquerycount = 0;
        $successquery = 0;

        if ($sql_queries2) {
            $sql_queries = array();
            $sql_queries2 = explode("\n", $sql_queries2);

            foreach ($sql_queries2 as $sql_query2) {
                $sql_query2 = trim(stripslashes($sql_query2));
                $sql_query2 = preg_replace("/[\r\n]+/", '', $sql_query2);

                if (!empty($sql_query2)) {
                    $sql_queries[] = $sql_query2;
                }
            }

            foreach ($sql_queries as $sql_query) {
                if (preg_match("/^\\s*(insert|update|replace|delete|create|truncate) /i", $sql_query)) {
                    $run_query = safe_query($sql_query);

                    if (!$run_query) {
                        $text.= graf(mysqli_error($DB->link) , ' style="color:#D10000;"');
                        $text.= graf($sql_query, ' style="color:#D10000;"');
                    } else {
                        $successquery++;
                        $text.= graf($sql_query, ' style="color:#4B9F00;"');
                    }

                    $totalquerycount++;
                } elseif (preg_match("/^\\s*(select) /i", $sql_query)) {
                    $run_query = safe_query($sql_query);

                    if ($run_query) {
                        $successquery++;
                    }

                    if ($run_query && mysqli_num_rows($run_query) > 0) {
                        /* get column metadata */
                        $i = 0;
                        $headers = "";

                        while ($i < mysqli_num_fields($run_query)) {
                            $meta = mysqli_fetch_field($run_query);
                            $headers.= hcell($meta->name);
                            $i++;
                        }

                        $rsd[] = '<div class="scrollWrapper">' . startTable('list', '', 'txp-list scrollable') . '<thead>' . tr($headers) . '</thead><tbody>';

                        while ($a = mysqli_fetch_assoc($run_query)) {
                            $out[] = $a;
                        }

                        mysqli_free_result($run_query);

                        foreach ($out as $b) {
                            $data = "";

                            foreach ($b as $f) {
                                $data.= td($f);
                            }

                            $rsd[] = tr($data);
                        }

                        $rsd[] = '</tbody>' . endTable() . '</div>' . br;
                        $out = array();
                    } else {
                        $text.= graf(mysqli_error($DB->link) , ' style="color:#D10000;"');
                    }

                    $text.= graf($sql_query, ' style="color:#D10000;"');
                    $totalquerycount++;
                } elseif (preg_match("/^\\s*(drop|show|grant) /i", $sql_query)) {
                    $text.= graf($sql_query . " - QUERY TYPE NOT SUPPORTED", ' style="color:#D10000;"');
                    $totalquerycount++;
                }
            }

            $text.= graf($successquery . "/" . $totalquerycount . " Query(s) Executed Successfully", ' style="color:#0069D1;"');
        }
    }

    echo startTable('edit') .
        tr(
            td(
                form(
                    graf("Each query must be on a single line.You may run multiple queries at once by starting a new line." .
                        br .
                        "Supported query types include SELECT, INSERT, UPDATE, CREATE, REPLACE, and DELETE.") .
                    graf("WARNING: All SQL run in this window will immediately and permanently change your database.", ' style="font-weight:bold;"') .
                    text_area('sql_query', '200', '550', $sql_query2) .
                    br .
                    fInput('submit', 'run', gTxt('Run'), 'publish') .
                    href("Go to Database Manager", "index.php?event=rss_db_man") .
                    eInput('rss_sql_run'), '', ' verify(\'' . gTxt('are_you_sure') . '\')"'
                )
            )
        ) .
        tr(
            td(graf($text . br . implode('', $rsd)))
        ) .
        endTable();
}

function prettyFileSize($bytes)
{
    if ($bytes < 1024) {
        return "$bytes bytes";
    } elseif (strlen($bytes) <= 9 && strlen($bytes) >= 7) {
        return number_format($bytes / 1048576, 2) . " MB";
    } elseif (strlen($bytes) >= 10) {
        return number_format($bytes / 1073741824, 2) . " GB";
    }

    return number_format($bytes / 1024, 2) . " KB";
}

function is_folder_empty($dir)
{
    if (is_dir($dir)) {
        $dl = opendir($dir);

        if ($dl) {
            while ($name = readdir($dl)) {
                if (!is_dir("$dir/$name")) {
                    return false;
                    break;
                }
            }

            closedir($dl);
        }

        return true;
    } else return true;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. Textpattern Database Manager

The rss_admin_db_manager plugin adds three menu items to your Textpattern admin interface. Each contains different functionality to help manage your "MySQL":http://www.mysql.com/ database. You can think of this plugin as a lightweight replacement for "phpMyAdmin":http://www.phpmyadmin.net/home_page/.

h2(#database-backup). Database Backup

The *DB Backup panel* allows you to backup, download and restore the MySQL database that is used for your Textpattern installation.

The database backups and restores are run using MySQL's "mysqldump":http://dev.mysql.com/doc/mysql/en/mysqldump.html command.

On this panel you are able to:

* Create a .sql backup file on windows with the additional option of creating a gzipped backup on *nix operating systems
* View a list of previous backup files
* Restore your database from one of the previous backups
* Download a backup file
* Delete old backups

h2(#backup-preferences). Backup Preferences

You have the ability to set several preferences related to your database backups. You can set these options on the backup panel. The options include:

* *Lock Tables* - Your host may or may not support this option.For example, by default, Textdrive doesn't allow table locking.If your backup fails, try setting this to “No”.
* *Debug Mode* - Turning debugging on will echo the command being run to the screen.
* *Backup Path* - Set the directory that your backups will be saved to.
* *Mysqldump Path* - It's likely that the default will work for you.If not, enter the full path the the executable.
* *Mysql Path* - It's likely that the default will work for you.If not, enter the full path the the executable.

h2(#database-manager). Database Manager

The *DB Manager panel* displays information about your MySQL database and all of its tables. A detailed list includes the name of the table, number of rows and file space usage.

You will also be alerted of any overhead or errors that need to be repaired.Tables can be repaired, dropped or backed up from this listing.

* Clicking on the name of the table will run a @select * FROM [table name]@ SQL statement and take you to the *Run SQL panel* to display the results.
* Repair a single table in the listing by clicking the Repair link.
* Repair all tables in the listing by clicking the Repair All link.
* Backup a single table in the listing by clicking the Backup link.
* Drop a single table in the listing by clicking the Drop link.

h2(#run-sql-window). Run SQL Window

The *Run SQL tab* allows for free form entry and execution of SQL statements. The SQL window accepts

SELECT, INSERT, UPDATE, CREATE, REPLACE, TRUNCATE, and DELETE statements. If a SELECT statement is run, the results will be displayed below the SQL window in a table.

The table markup allows you to add your own styles for creating a "CSS Scrollable Table":http://www.agavegroup.com/?p=31.

h2(#major-ransom-contributors). Major Ransom Contributors

* Jan Willem de Bruijn
* Heikki Yl
# --- END PLUGIN HELP ---
-->
<?php
}
?>