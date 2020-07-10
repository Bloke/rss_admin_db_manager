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

$plugin['version'] = '4.6.0';
$plugin['author'] = 'Rob Sable / Stef Dawson';
$plugin['author_uri'] = 'https://stefdawson.com/';
$plugin['description'] = 'Database management system for Textpattern';

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

$plugin['flags'] = '2';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String
$plugin['textpack'] = <<< EOT
#@owner rss_admin_db_manager
#@language en, en-gb, en-us
#@admin-side
rss_db_bk => Database backup
rss_db_man => Database manager
rss_db_run => Run SQL
rss_db_row_number => No.
#@rss_sql_run
rss_db_goto_dm => Database management
rss_db_query_preamble1 => Each query must be on a single line. You may run multiple queries at once by starting a new line.
rss_db_query_preamble2 => Supported query types: SELECT, INSERT, UPDATE, CREATE, REPLACE, and DELETE.
rss_db_query_success => {done}/{total} query(s) executed successfully
rss_db_query_unsupported => - QUERY TYPE NOT SUPPORTED
rss_db_query_warning => WARNING: All SQL run in this window will immediately and permanently change your database.
rss_db_query_run => Run
#@rss_db_man
rss_db_head_table => Table
rss_db_head_records => Records
rss_db_head_use_data => Data usage
rss_db_head_use_index => Index usage
rss_db_head_use_total => Total usage
rss_db_head_use_overhead => Overhead
rss_db_head_error_number => Error
rss_db_head_actions => Actions
rss_db_mysql_host => Database host:
rss_db_mysql_name => Database name:
rss_db_mysql_user => Database user:
rss_db_mysql_version => Database version:
rss_db_table_backup => Backup
rss_db_table_drop => Drop
rss_db_table_dropped => Dropped {table}
rss_db_table_optimize => Optimize
rss_db_table_optimized => Optimized {table}
rss_db_table_repair => Repair
rss_db_table_repair_all => Repair all
rss_db_table_repaired => Repaired {table}
rss_db_table_repaired_all => Repaired all tables
#@rss_db_bk
rss_db_backup_count => Backup file(s): {count}
rss_db_backup_create => Create a new backup of the {db} database
rss_db_backup_date => Backup date/time
rss_db_backup_error => Backup failed. Error: {error}
rss_db_backup_failed => Backup failed: folder is not writable
rss_db_backup_name => Backup file name
rss_db_backup_none => No database backups
rss_db_backup_ok => Backed up: {db} to "{filename}"
rss_db_backup_path => Backup path:
rss_db_backup_previous => Previous backup files
rss_db_backup_restore => Restore
rss_db_backup_size => Backup file size
rss_db_debug_mode => Debug mode:
rss_db_delete_error => Unable to delete: "{filename}"
rss_db_delete_ok => Deleted: "{filename}"
rss_db_gzip_file => gzipped file
rss_db_include_log => Include txp_log:
rss_db_lock_tables => Lock tables:
rss_db_mysql_path => mysql path:
rss_db_mysqldump_path => mysqldump path:
rss_db_restore_error => Failed to restore. Error: {error}
rss_db_restore_ok => Restored: "{filename}" to {db}
rss_db_sql_file => .sql file
EOT;
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (txpinterface === 'admin') {
    new rss_admin_db_manager();
}

/**
 * Admin-side class.
 */
class rss_admin_db_manager
{
    /**
     * The plugin's event.
     *
     * @var string
     */
    protected $event = __CLASS__;

    /**
     * The plugin's privileges.
     *
     * @var string
     */
    protected $privs = '1';

    /**
     * Prefs and their defaults
     *
     * @var array
     */
    protected $prefs = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->prefs = array(
            'rss_dbbk_lock'   => array(
                'val' => 1,
                'type' => 'yesnoradio',
            ),
            'rss_dbbk_txplog' => array(
                'val' => 1,
                'type' => 'yesnoradio',
            ),
            'rss_dbbk_debug'  => array(
                'val' => 0,
                'type' => 'yesnoradio',
            ),
            'rss_dbbk_path'   => array(
                'val' => get_pref('tempdir', sys_get_temp_dir()),
                'type' => 'text_input',
            ),
            'rss_dbbk_dump'   => array(
                'val' => 'mysqldump',
                'type' => 'text_input',
            ),
            'rss_dbbk_mysql'  => array(
                'val' => 'mysql',
                'type' => 'text_input',
            ),
        );

        add_privs('rss_db_man', $this->privs);
        add_privs('rss_sql_run', $this->privs);
        add_privs('rss_db_bk', $this->privs);

        register_callback(array($this, 'welcome'), 'plugin_lifecycle.'.$this->event);

        register_tab("extensions", "rss_db_man", gTxt('rss_db_man'));
        register_callback(array($this, 'db_man'), "rss_db_man");

        register_tab("extensions", "rss_sql_run", gTxt('rss_db_run'));
        register_callback(array($this, 'db_sql'), "rss_sql_run");

        register_tab("extensions", "rss_db_bk", gTxt('rss_db_bk'));
        register_callback(array($this, 'db_bak'), "rss_db_bk");
    }

    /**
     * Runs on plugin installation.
     *
     * @param      string  $evt    Textpattern event (panel)
     * @param      string  $stp    Textpattern step (action)
     */
    public function welcome($evt, $stp)
    {
        switch ($stp) {
            case 'installed':
                $this->install();
                break;
            case 'deleted':
                $this->uninstall();
                break;
        }

        return;
    }

    /**
     * Install prefs.
     *
     * @param array $set Array of key => values to forcibly set. Defaults will be used otherwise
     */
    public function install($set = array())
    {
        foreach ($this->prefs as $key => $options) {
            if (get_pref($key, null) === null || isset($set[$key])) {
                if ($options['type'] === 'yesnoradio') {
                    $newval = isset($set[$key]) ? $set[$key] : $options['val'];
                } else {
                    $newval = empty($set[$key]) ? $options['val'] : $set[$key] ;
                }

                set_pref($key, $newval, $this->event, PREF_HIDDEN, $options['type']);
            }
        }
    }

    /**
     * Delete prefs and language strings.
     */
    public function uninstall()
    {
        remove_pref(null, $this->event);
        safe_delete('txp_lang', "owner = 'rss\_admin\_db\_manager'");
    }

    /**
     * Database backup panel
     *
     * @param   string  $evt  Textpattern event (panel)
     * @param   string  $stp  Textpattern step (action)
     */
    public function db_bak($event, $step)
    {
        global $DB;

        $mysql_hup = ' -h' . $DB->host . ' -u' . $DB->user . ' -p' . escapeshellcmd($DB->pass);

        foreach ($this->prefs as $key => $options) {
            $$key = get_pref($key, $options['val']);
        }

        if (ps("save")) {
            foreach ($this->prefs as $key => $options) {
                $in = ps($key);
                $this->install(array($key => $in));
                $$key = get_pref($key, $in, true);
            }

            pagetop(gTxt('rss_db_bak'), gTxt('preferences_saved'));

        } elseif (gps("bk")) {
            $bk_table = (gps("bk_table")) ? " --tables " . gps("bk_table") . " " : "";
            $tabpath = (gps("bk_table")) ? "-" . gps("bk_table") : "";
            $gzip = gps("gzip");
            $filename = time() . '-' . $DB->db . $tabpath;
            $backup_path = $rss_dbbk_path . '/' . $filename . '.sql';
            $lock = ($rss_dbbk_lock) ? "" : " --skip-lock-tables --skip-add-locks ";
            $nolog = ($rss_dbbk_txplog) ? "" : " --ignore-table=" . $DB->db . ".txp_log ";
            $nolog = (isset($bk_table) && gps("bk_table") == "txp_log") ? "" : $nolog;

            $backup_cmd = $rss_dbbk_dump . $mysql_hup . ' -Q --add-drop-table ' . $lock . $nolog . $DB->db . $bk_table . ($gzip ? ' | gzip' : '') . ' > ' . $backup_path . ($gzip ? '.gz' : '');

            $bkdebug = ($rss_dbbk_debug) ? $backup_cmd : '';
            $error = "";

            if (function_exists('passthru')) {
                passthru($backup_cmd, $error);
            } else {
                $dumpIt = popen($backup_cmd, 'r');
                pclose($dumpIt);
            }

            if (!is_writable($rss_dbbk_path)) {
                $message = array(gTxt('rss_db_backup_failed'), E_WARNING);
            } elseif ($error) {
                unlink($backup_path);
                $message = array(gTxt('rss_db_backup_error', array('{error}' => $error)), E_WARNING);
            } elseif (!is_file($backup_path)) {
                $message = array(gTxt('rss_db_backup_error', array('{error}' => $error)), E_WARNING);
            } elseif (filesize($backup_path) == 0) {
                unlink($backup_path);
                $message = array(gTxt('rss_db_backup_error', array('{error}' => $error)), E_WARNING);
            } else {
                $message = gTxt('rss_db_backup_ok', array('{db}' => $DB->db, '{filename}' => $filename));
            }

            pagetop(gTxt('rss_db_bk'), $message);

        } elseif (gps("download")) {
            $fn = gps("download");
            $file_path = $rss_dbbk_path . '/' . $fn;

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");

            if (substr($fn, -2) == "gz") {
                header("Content-Type: application/zip");
            }

            header("Content-Disposition: attachment; filename=" . basename($file_path) . ";");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . filesize($file_path));
            @readfile($file_path);
        } elseif (gps("restore")) {
            $safe_restore = txpspecialchars(gps("restore"));

            if (stristr(gps("restore") , '.gz')) {
                $backup_cmd = 'gunzip < ' . $rss_dbbk_path . '/' . $safe_restore . ' | ' . $rss_dbbk_mysql . $mysql_hup . ' ' . $DB->db;
            } else {
                $backup_cmd = $rss_dbbk_mysql . $mysql_hup . ' ' . $DB->db . ' < ' . $rss_dbbk_path . '/' . $safe_restore;
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
                $message = array(gTxt('rss_db_restore_error', array('{error}' => $error)), E_WARNING);
            } else {
                $message = gTxt('rss_db_restore_ok', array('{filename}' => $safe_restore, '{db}' => $DB->db));
            }

            pagetop(gTxt('rss_db_bk'), $message);

        } elseif (gps("delete")) {
            $safe_delete = txpspecialchars(gps("delete"));

            if (is_file($rss_dbbk_path . '/' . $safe_delete)) {
                if (!unlink($rss_dbbk_path . '/' . $safe_delete)) {
                    $message = array(gTxt('rss_db_delete_error', array('{filename}' => $safe_delete)), E_WARNING);
                } else {
                    $message = gTxt('rss_db_delete_ok', array('{filename}' => $safe_delete));
                }
            } else {
                $message = array(gTxt('rss_db_delete_error', array('{filename}' => $safe_delete)), E_WARNING);
            }

            pagetop(gTxt('rss_db_bk'), $message);

        } else {
            pagetop(gTxt('rss_db_bk'));
        }

        $gzp = (!IS_WIN) ? " | " . href(gTxt('rss_db_gzip_file'), "index.php?event=rss_db_bk&amp;bk=$DB->db&amp;gzip=1") : "";
        $sqlversion = getRow("SELECT VERSION() AS version");
        $sqlv = explode("-", $sqlversion['version']);
        $allownologs = ((float)$sqlv[0] >= (float)"4.1.9") ? tda(gTxt('rss_db_include_log') , ' style="text-align:right;vertical-align:middle"') . tda(yesnoRadio("rss_dbbk_txplog", $rss_dbbk_txplog) , ' style="text-align:left;vertical-align:middle"') : '';

        if (isset($bkdebug) && $bkdebug) {
            echo '<p align="center">' . $bkdebug . '</p>';
        }

        echo tag_start('div', array('class' => 'txp-listtables')) .
            startTable('txp-list') .
            form(
                tr(
                    tda(gTxt('rss_db_lock_tables'), ' style="text-align:right;vertical-align:middle"') .
                    tda(yesnoRadio("rss_dbbk_lock", $rss_dbbk_lock), ' style="text-align:left;vertical-align:middle"') .
                    $allownologs .
                    tda(gTxt('rss_db_debug_mode'), ' style="text-align:right;vertical-align:middle"') .
                    tda(yesnoRadio("rss_dbbk_debug", $rss_dbbk_debug), ' style="text-align:left;vertical-align:middle"') .
                    tda(fInput("submit", "save", gTxt("save") , "publish") .
                    eInput("rss_db_bk") .
                    sInput('saveprefs'), " colspan=\"2\" class=\"noline\"")
                ) .
                tr(
                    tda(gTxt('rss_db_backup_path'), ' style="text-align:right;vertical-align:middle"') .
                    tda(fInput('text', "rss_dbbk_path", $rss_dbbk_path, '', '', '', '50'), ' colspan="15"')
                ) .
                tr(
                    tda(gTxt('rss_db_mysqldump_path'), ' style="text-align:right;vertical-align:middle"') .
                    tda(fInput('text', "rss_dbbk_dump", $rss_dbbk_dump, '', '', '', '50'), ' colspan="15"')
                ) .
                tr(
                    tda(gTxt('rss_db_mysql_path'), ' style="text-align:right;vertical-align:middle"') .
                    tda(fInput('text', "rss_dbbk_mysql", $rss_dbbk_mysql, '', '', '', '50'), ' colspan="15"')
                )
            ) .
            endTable() . tag_end('div').
            tag_start('div', array('class' => 'txp-listtables')) .
            startTable("txp-list", '', 'txp-list') .
            tr(
                tda(hed(gTxt('rss_db_backup_create', array('{db}' => $DB->db)) .
                    br .
                    href(gTxt('rss_db_sql_file'), "index.php?event=rss_db_bk&amp;bk=$DB->db") . $gzp, 3), ' colspan="7" style="text-align:center;"')
            ) .
            tr(
                tdcs(hed(gTxt('rss_db_backup_previous'), 1), 7)
            ) .
            tr(
                hcell(gTxt('rss_db_row_number')) .
                hcell(gTxt('rss_db_backup_name')) .
                hcell(gTxt('rss_db_backup_date')) .
                hcell(gTxt('rss_db_backup_size')) .
                hcell("") .
                hcell("") .
                hcell("")
            );

        $totalsize = 0;
        $no = 0;

        if (!$this->is_folder_empty($rss_dbbk_path)) {
            $database_files = array();
            $dir = new DirectoryIterator($rss_dbbk_path);

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
                $size_text = filesize($rss_dbbk_path . '/' . $filename);
                $totalsize+= $size_text;
                echo tr(td($no) . td($database_text) . td($date_text) . td($this->prettyFileSize($size_text)) . '<td><a href="index.php?event=rss_db_bk&amp;download=' . $filename . '">'.gTxt('download').'</a></td>' . '<td><a href="index.php?event=rss_db_bk&amp;restore=' . $filename . '"onclick="return verify(\'' . gTxt('are_you_sure') . '\')">'.gTxt('rss_db_backup_restore').'</a></td>' . '<td><a href="index.php?event=rss_db_bk&amp;delete=' . $filename . '"onclick="return verify(\'' . gTxt('are_you_sure') . '\')">'.gTxt('delete').'</a></td>');
            }

            echo tr(tag(gTxt('rss_db_backup_count', array('{count}' => $no)), "th", ' colspan="3"') . tag($this->prettyFileSize($totalsize) , "th", ' colspan="4"'));
        } else {
            if (file_exists($rss_dbbk_path)) {
                echo tr(tda(hed(gTxt('rss_db_backup_none'), 3), ' colspan="7" style="text-align:center;"'));
            } else {
                echo tr(tda(hed(gTxt('path_not_writable', array('{list}' => $rss_dbbk_path)), 3), ' colspan="7" style="text-align:center;"'));
            }
        }

        echo endTable().tag_end('div');
    }

    /**
     * Database table management panel
     *
     * @param   string  $evt  Textpattern event (panel)
     * @param   string  $stp  Textpattern step (action)
     */
    public function db_man($event, $step)
    {
        global $DB;

        if (gps("opt_table")) {
            $query = "OPTIMIZE TABLE " . doSlash(gps("opt_table"));
            safe_query($query);
            $message = gTxt('rss_db_table_optimized', array('{table}' => txpspecialchars(gps("opt_table"))));
        } elseif (gps("rep_table")) {
            $query = "REPAIR TABLE " . doSlash(gps("rep_table"));
            safe_query($query);
            $message = gTxt('rss_db_table_repaired', array('{table}' => txpspecialchars(gps("rep_table"))));
        } elseif (gps("rep_all")) {
            $query = "REPAIR TABLE " . doSlash(gps("rep_all"));
            safe_query($query);
            $message = gTxt('rss_db_table_repaired_all');
        } elseif (gps("drop_table")) {
            $query = "DROP TABLE " . doSlash(gps("drop_table"));
            safe_query($query);
            $message = gTxt('rss_db_table_dropped', array('{table}' => txpspecialchars(gps("drop_table"))));
        } else {
            $message = '';
        }

        pagetop(gTxt('rss_db_man'), $message);
        $sqlversion = getRow("SELECT VERSION() AS version");
        $headatts = ' style="color:#0069D1;padding:0 10px 0 5px;"';

        echo tag_start('div', array('class' => 'txp-listtables')) .
            startTable('dbinfo') .
            tr(
                hcell(gTxt('rss_db_mysql_host')) .
                tda($DB->host, $headatts) .
                hcell(gTxt('rss_db_mysql_name')) .
                tda($DB->db, $headatts) .
                hcell(gTxt('rss_db_mysql_user')) .
                tda($DB->user, $headatts) .
                hcell(gTxt('rss_db_mysql_version')) .
                tda("MySQL v" . $sqlversion['version'], $headatts)
            ) .
            endTable() . tag_end('div') .
            br;

        echo tag_start('div', array('class' => 'txp-listtables')) .
            startTable('list', '', 'txp-list') .
            tr(
                hcell(gTxt('rss_db_row_number')) .
                hcell(gTxt('rss_db_head_table')) .
                hcell(gTxt('rss_db_head_records')) .
                hcell(gTxt('rss_db_head_use_data')) .
                hcell(gTxt('rss_db_head_use_index')) .
                hcell(gTxt('rss_db_head_use_total')) .
                hcell(gTxt('rss_db_head_use_overhead')) .
                hcell(gTxt('rss_db_head_error_number')) .
                hcell(gTxt('rss_db_head_actions'))
            );

        $no = 0;
        $row_usage = 0;
        $data_usage = 0;
        $index_usage = 0;
        $overhead_usage = 0;
        $alltabs = array();
        $tablesstatus = getRows("SHOW TABLE STATUS");

        foreach ($tablesstatus as $tablestatus) {
            extract($tablestatus);
            $sani_name = txpspecialchars($Name);

            $q = "SHOW KEYS FROM `" . doSlash($Name) . "`";
            safe_query($q);
            $mysqlErrno = mysqli_errno($DB->link);

            if ($Engine === 'MyISAM') {
                $alltabs[] = $sani_name;
            }

            $color = ($mysqlErrno != 0) ? ' style="color:#D10000;"' : ' style="color:#4B9F00;"';
            $color2 = ($Data_free > 0) ? ' style="color:#D10000;"' : ' style="color:#4B9F00;"';
            $no++;
            $row_usage+= $Rows;
            $data_usage+= $Data_length;
            $index_usage+= $Index_length;
            $overhead_usage+= $Data_free;

            echo tr(
                td($no) .
                td(href($sani_name, "index.php?event=rss_sql_run&amp;tn=" . $sani_name)) .
                td(" " . $Rows) .
                td($this->prettyFileSize($Data_length)) .
                td($this->prettyFileSize($Index_length)) .
                td($this->prettyFileSize($Data_length + $Index_length)) .
                tda($this->prettyFileSize($Data_free), $color2) .
                tda(" " . $mysqlErrno, $color) .
                td(($Engine === 'MyISAM' ? href(gTxt('rss_db_table_repair'), "index.php?event=rss_db_man&amp;rep_table=" . $sani_name) .n : '').
                    href(gTxt('rss_db_table_backup'), "index.php?event=rss_db_bk&amp;bk=1&amp;bk_table=" . $sani_name) .n.
                    href(gTxt('rss_db_table_optimize'), "index.php?event=rss_db_man&amp;opt_table=" . $sani_name) .n.
                    '<a href="index.php?event=rss_db_man&amp;drop_table=' . $sani_name . '"onclick="return verify(\'' . gTxt('are_you_sure') . '\')">' . gTxt('rss_db_table_drop') . '</a>'));
        }

        echo tr(
            hcell("Total") .
            hcell($no . " Tables") .
            hcell(number_format($row_usage)) .
            hcell($this->prettyFileSize($data_usage)) .
            hcell($this->prettyFileSize($index_usage)) .
            hcell($this->prettyFileSize($data_usage + $index_usage)) .
            hcell($this->prettyFileSize($overhead_usage)) .
            hcell() .
            tda(href(strong(gTxt('rss_db_table_repair_all')), "index.php?event=rss_db_man&amp;rep_all=" . implode(",", $alltabs)) , ' style="text-align:center;"'));

        echo tr(tda(href(gTxt('rss_db_run'), "index.php?event=rss_sql_run") , ' style="text-align:center;" colspan="9"')) .
            endTable() .
            tag_end('div');
    }

    /**
     * Database run SQL panel
     *
     * @param   string  $evt  Textpattern event (panel)
     * @param   string  $stp  Textpattern step (action)
     */
    public function db_sql($event, $step)
    {
        global $DB;

        pagetop(gTxt('rss_db_run'));
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
                        $text.= graf($sql_query . gTxt('rss_db_query_unsupported'), ' style="color:#D10000;"');
                        $totalquerycount++;
                    }
                }

                $text.= graf(gTxt('rss_db_query_success', array('{done}' => $successquery, '{total}' => $totalquerycount)), ' style="color:#0069D1;"');
            }
        }

        echo startTable('edit') .
            tr(
                td(
                    form(
                        graf(gTxt('rss_db_query_preamble1') . br . gTxt('rss_db_query_preamble2')) .
                        graf(gTxt('rss_db_query_warning'), ' style="font-weight:bold;"') .
                        text_area('sql_query', '200', '550', $sql_query2) .
                        br .
                        fInput('submit', 'run', gTxt('rss_db_query_run'), 'publish') . n.
                        href(gTxt('rss_db_goto_dm'), "index.php?event=rss_db_man") .
                        eInput('rss_sql_run'), '', ' verify(\'' . gTxt('are_you_sure') . '\')"'
                    )
                )
            ) .
            tr(
                td(graf($text . br . implode('', $rsd)))
            ) .
            endTable();
    }

    /**
     * Display sizes in the most suitable format.
     *
     * @param  int    $bytes Number of bytes to convert into better units
     * @return string
     */
    protected function prettyFileSize($bytes)
    {
        return format_filesize($bytes, ($bytes == 0 ? 0 : 2));
    }

    /**
     * Determines if the passed folder contains files.
     *
     * @param  string  $dir Directory to test
     * @return boolean
     */
    protected function is_folder_empty($dir)
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
        } else {
            return true;
        }
    }
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. Textpattern database manager

The rss_admin_db_manager plugin adds three menu items to your Textpattern admin interface. Each contains different functionality to help manage your "MySQL":https://www.mysql.com/ database. You can think of this plugin as a lightweight replacement for "phpMyAdmin":https://www.phpmyadmin.net/.

h2(#database-backup). Database backup

The _Database backup_ panel allows you to backup, download and restore the MySQL database that is used for your Textpattern installation.

The database backups and restores are run using MySQL's "mysqldump":https://dev.mysql.com/doc/mysql/en/mysqldump.html command.

On this panel you are able to:

* Create a .sql backup file on windows with the additional option of creating a gzipped backup on *nix operating systems
* View a list of previous backup files
* Restore your database from one of the previous backups
* Download a backup file
* Delete old backups

h3(#backup-preferences). Backup preferences

Set several preferences related to your database backups. You can set these options on the Database backup panel. The options are:

* *Lock Tables* - Your host may or may not support this option. If your backup fails, try setting this to “No”.
* *Include txp_log* - Include or omit the txp_log table from the backup.
* *Debug Mode* - Turning debugging on will echo the command being run to the screen.
* *Backup Path* - Set the directory that your backups will be saved to. Defaults to your Textpattern temp directory.
* *Mysqldump Path* - It's likely that the default will work for you. If not, enter the full path to the executable.
* *Mysql Path* - It's likely that the default will work for you. If not, enter the full path to the executable.

h2(#database-manager). Database manager

The _Database manager_ panel displays information about your MySQL database and all of its tables. A detailed list includes the name of the table, number of rows and file space usage.

You will also be alerted of any overhead or errors that need to be repaired. Tables can be repaired, optimized, dropped or backed up from this listing.

* Clicking on the name of the table will run a @select * FROM [table name]@ SQL statement and take you to the _Run SQL_ panel to display the results.
* Repair a single (MyISAM) table by clicking its corresponding _Repair_ link.
* Repair all (MyISAM) tables by clicking the _Repair all_ link at the bottom of the table.
* Optimize a single table by clicking its corresponding _Optimize_ link.
* Backup a single table by clicking its corresponding _Backup_ link.
* Drop a single table by clicking its corresponding _Drop_ link.

h2(#run-sql-window). Run SQL

The _Run SQL_ panel allows free form entry and execution of SQL statements. The SQL window accepts SELECT, INSERT, UPDATE, CREATE, REPLACE, TRUNCATE, and DELETE statements. If a SELECT statement is run, the results will be displayed below the SQL command in a table.

h2(#major-ransom-contributors). Major Ransom Contributors

* Jan Willem de Bruijn
* Heikki Yl
# --- END PLUGIN HELP ---
-->
<?php
}
?>