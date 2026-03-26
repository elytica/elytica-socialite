<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('elytica_service_id')->nullable();
            $table->text('elytica_service_token')->nullable();
            $table->text('elytica_service_refresh_token')->nullable();
            $table->timestamp('elytica_service_token_expires_at')->nullable();
        });

        if (Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'elytica_service_id',
                'elytica_service_token',
                'elytica_service_refresh_token',
                'elytica_service_token_expires_at',
            ]);
        });
    }
};
