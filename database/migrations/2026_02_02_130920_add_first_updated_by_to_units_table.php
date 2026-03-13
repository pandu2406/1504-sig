<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('first_updated_by')->nullable()->after('last_updated_by');
        });

        // Backfill first_updated_by with current last_updated_by for units that are already updated
        \Illuminate\Support\Facades\DB::table('units')
            ->whereNotNull('last_updated_by')
            ->where('last_updated_by', '!=', '')
            ->update(['first_updated_by' => \Illuminate\Support\Facades\DB::raw('last_updated_by')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('first_updated_by');
        });
    }
};
