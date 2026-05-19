<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Countries reference table — populated by `php artisan addressing:sync-countries`.
 *
 * Slim shape after the universal-addressing refactor: per-country subdivisions,
 * translations, timezones, and rarely-used metadata are sourced at runtime from
 * commerceguys/addressing rather than persisted here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->char('iso2', 2)->primary()->unique();
            $table->char('iso3', 3)->nullable()->unique();
            $table->string('name');
            $table->char('numeric_code', 3)->nullable();
            $table->string('phone_code')->nullable();
            $table->string('capital')->nullable();
            $table->string('currency', 3)->nullable()->index();
            $table->tinyInteger('currency_decimals')->nullable();
            $table->string('currency_name', 50)->nullable();
            $table->string('currency_symbol', 10)->nullable();
            $table->string('region', 50)->nullable()->index();
            $table->string('subregion', 50)->nullable()->index();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->string('emoji', 5)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
