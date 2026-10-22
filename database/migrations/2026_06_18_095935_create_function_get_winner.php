<?php

use App\Support\GetWinnerProcedure;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();

        GetWinnerProcedure::create();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        GetWinnerProcedure::drop();
    }
};
