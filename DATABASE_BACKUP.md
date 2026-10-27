# Local Database Backup and Recovery

This application can use SQLite or MySQL. The default in `.env.example` is
SQLite, while the current local runtime is configured for MySQL on
`127.0.0.1:3306`. Check the active `.env` locally before following a procedure;
never copy its credentials into commands saved in source control or into this
document.

These procedures are manual. No backup schedule or automatic retention policy
is configured by this guide.

## Before every backup

1. Identify the active `DB_CONNECTION` and the database name or SQLite file path
   from the local environment configuration.
2. Store backups outside the repository, preferably on a protected local drive
   or an access-controlled external drive. Do not put database backups under
   `public/`, `storage/app/public/`, or a version-controlled directory.
3. Ensure the destination has enough free space and restrict access because
   backups contain customer, payment, inventory, and staff records.
4. Record the backup time, database, application version, and the person who
   made the backup. Keep the checksum with the backup.

## MySQL

Run these commands in PowerShell. Replace the prompts with the values from the
local configuration; `mysqldump` prompts for the password rather than putting
it in shell history. The validated workstation has the XAMPP client tools at
`C:\xampp\mysql\bin`, and that directory is not on `PATH`; update
`$MySqlBin` if MySQL is installed elsewhere.

```powershell
$DbName = Read-Host "Database name"
$DbUser = Read-Host "Database user"
$MySqlBin = "C:\xampp\mysql\bin"
$BackupDirectory = Join-Path $env:USERPROFILE "Documents\heim-db-backups"
New-Item -ItemType Directory -Path $BackupDirectory -Force | Out-Null
$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$DumpPath = Join-Path $BackupDirectory "heim-$Timestamp.sql"

& (Join-Path $MySqlBin "mysqldump.exe") --host=127.0.0.1 --port=3306 "--user=$DbUser" --password `
  --single-transaction --routines --triggers --events --hex-blob `
  "--result-file=$DumpPath" $DbName
if ($LASTEXITCODE -ne 0) {
    throw "mysqldump failed; do not treat this backup as complete."
}

$Dump = Get-Item -LiteralPath $DumpPath
if ($Dump.Length -le 0) {
    throw "The SQL dump is empty."
}
Get-FileHash -LiteralPath $DumpPath -Algorithm SHA256
```

Keep the `mysqldump` output and exit code. If the server or utility is
unavailable, or the dump exits unsuccessfully, resolve that problem and repeat
the backup; do not assume a partial dump is usable.

### Verify a MySQL backup

File existence, non-zero size, and a SHA-256 hash detect incomplete or changed
files, but do not prove that a database can be recovered. Periodically restore
the dump into a new, isolated test database or a separate local MySQL instance.
Confirm that the tables and representative records are present and that the
application can connect to that test database. Do not use the live database as
the restore target for a verification exercise.

### Restore MySQL

Restore only with an explicit recovery decision. Stop the web app and queue
workers first. Preserve a fresh backup of the current database, then restore
the selected dump into a new, empty recovery database. Verify its schema and
records before directing the application to it. Do not restore over the live
database merely to test a backup.

From **Command Prompt** (`cmd.exe`), import a dump into an intentionally chosen
empty recovery database (substitute the connection values and file path):

```text
C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 --user=YOUR_USER --password --database=YOUR_EMPTY_RECOVERY_DATABASE < "C:\path\to\backup.sql"
```

The `--password` option prompts for the password. Use a separate database name
that has been confirmed empty; the restore may replace tables with matching
names. Before returning the application to service, verify the selected
database configuration and test key workflows. Keep the pre-restore backup
until the recovery is accepted.

## SQLite

Use the `sqlite3` command-line utility's online backup command. Set
`$DatabasePath` to the active SQLite database file identified from local
configuration. The backup can be created while SQLite is in use:

```powershell
$DatabasePath = "C:\path\to\database.sqlite"
$BackupDirectory = Join-Path $env:USERPROFILE "Documents\heim-db-backups"
New-Item -ItemType Directory -Path $BackupDirectory -Force | Out-Null
$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$BackupPath = Join-Path $BackupDirectory "heim-$Timestamp.sqlite"

sqlite3.exe $DatabasePath ".backup '$BackupPath'"
if ($LASTEXITCODE -ne 0) {
    throw "SQLite backup failed; do not treat this backup as complete."
}
sqlite3.exe $BackupPath "PRAGMA integrity_check;"
if ($LASTEXITCODE -ne 0) {
    throw "SQLite integrity check failed."
}
Get-FileHash -LiteralPath $BackupPath -Algorithm SHA256
```

The integrity check must return `ok`. As with MySQL, periodically verify
recovery by opening a separate copy and checking representative data and
application connectivity.

### Restore SQLite

Stop the web app and any queue workers before restoring. Copy the current
database file to a separate recovery point, then restore the chosen backup to
the configured database path. Run `PRAGMA integrity_check;` against the
restored file and verify key application workflows before resuming service.
Do not replace the active database as a way to test a backup.

## Recovery safeguards

- Never run `php artisan migrate:fresh`, `php artisan migrate:refresh`, or
  `php artisan db:wipe` against a database containing business records.
- A backup is not verified until it has been restored into an isolated target
  and checked.
- Keep more than one dated recovery point; do not overwrite the last known-good
  backup.
- Document who authorized and performed a production/local-business recovery,
  which backup was used, and the verification results.
