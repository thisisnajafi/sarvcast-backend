<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 100)->nullable()->after('last_name');
            $table->enum('gender', ['male', 'female', 'unspecified'])->nullable()->after('name');
            $table->date('birthday')->nullable()->after('gender');
            $table->enum('age_group', ['3_5', '6_8', '9_12', '13_plus'])->nullable()->after('birthday');
            $table->enum('account_type', ['child', 'parent', 'shared'])->default('child')->after('age_group');
            $table->json('favorite_category_ids')->nullable()->after('account_type');
            $table->boolean('onboarding_completed')->default(false)->after('favorite_category_ids');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'gender',
                'birthday',
                'age_group',
                'account_type',
                'favorite_category_ids',
                'onboarding_completed',
            ]);
        });
    }
};
