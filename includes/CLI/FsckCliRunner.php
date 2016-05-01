<?php
/*
Copyright 2009-2014 Guillaume Boudreau

This file is part of Greyhole.

Greyhole is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Greyhole is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Greyhole.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('includes/CLI/AbstractCliRunner.php');

class FsckCliRunner extends AbstractCliRunner {
    private $dir = '';
    private $fsck_options = array();
    
    private static $available_options = array(
       'email-report'             => Fsck::OPTION_EMAIL,
       'dont-walk-metadata-store' => Fsck::OPTION_SKIP_METASTORE,
       'if-conf-changed'          => Fsck::OPTION_IF_CONF_CHANGED,
       'disk-usage-report'        => Fsck::OPTION_DU,
       'find-orphaned-files'      => Fsck::OPTION_ORPHANED,
       'checksums'                => Fsck::OPTION_CHECKSUMS,
       'delete-orphaned-metadata' => Fsck::OPTION_DEL_ORPHANED_METADATA
    );

    function __construct($options, $cli_command) {
        parent::__construct($options, $cli_command);

        if (isset($this->options['dir'])) {
            $this->dir = $this->options['dir'];
            if (!is_dir($this->dir)) {
                $this->log("$this->dir is not a directory. Exiting.");
                $this->finish(1);
            }
        }
        
        foreach (self::$available_options as $cli_option => $option) {
            if (isset($this->options[$cli_option])) {
                $this->fsck_options[] = $option;
            }
        }
    }

    public function run() {
        if (empty($this->dir)) {
            Fsck::scheduleForAllShares($this->fsck_options);
            $this->dir = 'all shares';
        } else {
            Fsck::scheduleForDir($this->dir, $this->fsck_options);
        }
        $this->log("fsck of $this->dir has been scheduled. It will start after all currently pending tasks have been completed.");
        if (isset($this->options['checksums'])) {
            $this->log("Any mismatch in checksums will be logged in both " . Config::get(CONFIG_GREYHOLE_LOG_FILE) . " and " . FSCKLogFile::PATH . "/fsck_checksums.log");
        }

        // Also clean the tasks_completed table
        DB::deleteExecutedTasks();
    }
}

?>
