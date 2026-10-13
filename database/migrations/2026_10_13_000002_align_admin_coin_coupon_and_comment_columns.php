<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coin_transactions')) {
            Schema::table('coin_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('coin_transactions', 'type')) {
                    $table->string('type', 50)->nullable();
                }
                if (! Schema::hasColumn('coin_transactions', 'status')) {
                    $table->string('status', 20)->default('completed');
                }
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'coins')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('coins')->default(0);
            });
        }

        if (Schema::hasTable('coupon_codes')) {
            Schema::table('coupon_codes', function (Blueprint $table) {
                if (! Schema::hasColumn('coupon_codes', 'value')) {
                    $table->decimal('value', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('coupon_codes', 'status')) {
                    $table->string('status', 20)->nullable();
                }
            });
        }

        if (Schema::hasTable('story_comments') && ! Schema::hasColumn('story_comments', 'comment')) {
            Schema::table('story_comments', function (Blueprint $table) {
                $table->text('comment')->nullable();
            });
        }

        if (Schema::hasTable('quiz_questions') && ! Schema::hasColumn('quiz_questions', 'explanation')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->text('explanation')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Columns stay. They match fields the admin panel already writes.
    }
};
