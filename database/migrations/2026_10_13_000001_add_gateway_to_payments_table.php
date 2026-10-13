<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'gateway')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('gateway', 50)->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'gateway')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('gateway');
            });
        }
    }
};
