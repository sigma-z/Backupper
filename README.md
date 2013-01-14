Backupper
===

Backupper is a in PHP (requires PHP 5.3 or greater) written command line tool for backup folders.
It's only tested on Microsoft Windows, until now.

For file access reasons you should run the script as __administrator / root__.

[On GitHub]: https://github.com/sigma-z/Backupper

How to use
---

### 1. Create back config
 * You copy the file './config/default.php.dist' to './config/default.php' and open it in a text editor.

### 2. Configure your backup
 * You can have more than one source for your backup - use / as or directory separator.
 * You specify the target folder. You can use %date% and %mode% as placeholders in your target folder.
  * %date% will be replace by date('Y-m-d')
  * %mode% will be replace the given backup mode (see below)
 * You may specify global exclusions of file names or directory names and/or you may specify exclusions for each source separately.
 * You may specify exclusion for absolute file paths - use / as or directory separator.

### 3. Run the backup tool

    php backupper.php [name=<backup name>] [mode=full|differential|incremental]

If you have not specified the argument 'name' it uses 'default' as backup name.

If you have not specified the argument 'mode' it uses 'full' as backup mode.

Features
---
 * Supports backup modes: full, differential, and incremental
 * Displays during backup process: written megabytes, write speed, time elapsed, and current processing file
 * Supports excluding file names or whole paths
 * Multi-backups via config files
 * Shows summary at the end of backup process

ToDos
---
 * Add script argument help for usage information
 * Error logging
  * Logging errors, if a file could not be backupped
  * Display errors after backup process
