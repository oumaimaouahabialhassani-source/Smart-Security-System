<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RunBackup extends Command
{
    protected $signature = 'backup:run {--label=manual : Label embedded in the file name}';

    protected $description = 'Dump the whole database to storage/app/backups (pure PHP, no mysqldump needed)';

    public function handle(): int
    {
        $name = 'backup-'.now()->format('Y-m-d-His').'-'.$this->option('label').'.sql';

        Storage::makeDirectory('backups');
        $path = Storage::path('backups/'.$name);
        $out = fopen($path, 'w');

        fwrite($out, "-- ".config('app.name')." database backup\n-- Generated ".now()->toDateTimeString()."\n\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = array_map(fn ($t) => array_values((array) $t)[0], DB::select('SHOW TABLES'));

        foreach ($tables as $table) {
            $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
            $createSql = ((array) $create)['Create Table'];

            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n");

            DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use ($out, $table) {
                foreach ($rows as $row) {
                    $values = implode(', ', array_map(
                        fn ($v) => $v === null ? 'NULL' : DB::getPdo()->quote((string) $v),
                        (array) $row,
                    ));
                    fwrite($out, "INSERT INTO `{$table}` VALUES ({$values});\n");
                }
            });

            fwrite($out, "\n");
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);

        $this->info("Backup written: {$name} (".round(filesize($path) / 1024)." KB)");

        return self::SUCCESS;
    }
}
