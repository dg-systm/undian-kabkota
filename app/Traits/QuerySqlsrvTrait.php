<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait QuerySqlsrvTrait
{
    protected function enableIdentityInsert(string $driver, string $table): void
    {
        if ($driver === 'sqlsrv') {
            DB::unprepared("SET IDENTITY_INSERT {$table} ON");
        }
    }

    protected function disableIdentityInsert(string $driver, string $table): void
    {
        if ($driver === 'sqlsrv') {
            DB::unprepared("SET IDENTITY_INSERT {$table} OFF");
        }
    }

    protected function withIdentityInsert(string $table, callable $callback): void
    {
        $driver = DB::connection()->getDriverName();

        $this->enableIdentityInsert($driver, $table);

        try {
            $callback();
        } finally {
            $this->disableIdentityInsert($driver, $table);
        }
    }
}
