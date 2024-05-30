<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('elytica_service_token')->nullable();
            $table->unsignedBigInteger('elytica_service_id')->nullable();
            $table->string('elytica_service_refresh_token')->nullable();
            $table->unsignedBigInteger('elytica_service_expires_in')->nullable();
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
              'elytica_service_token',
              'elytica_service_id',
              'elytica_service_refresh_token',
              'elytica_service_expires_in'
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
