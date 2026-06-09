<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses schema — designed to live behind Joranski\Addressing\Models\Address.
 *
 * Column names follow Google libaddressinput / W3C convention so this schema
 * can be lifted into the future joranski/laravel-addressing Composer package
 * without rename overhead. Default-shipping/billing tracking is delegated to
 * the address_usages pivot (2024_00_00_000001_create_address_usages_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('old_address_id')->nullable()->unique();
            $table->uuid('address_uuid')->nullable()->unique();

            // ISO 3166-1 alpha-2 country code, FK to countries.iso2.
            $table->char('country_code', 2)->nullable();
            $table->foreign('country_code')->references('iso2')->on('countries')->nullOnDelete();

            $table->nullableMorphs('addressable');
            $table->string('response_id', 80)->nullable();
            $table->string('label', 150)->nullable();
            $table->string('freeform_address')->nullable();

            // W3C / libaddressinput field names.
            $table->string('address_line1', 150)->nullable();
            $table->string('address_line2', 150)->nullable();
            $table->string('locality', 100)->nullable();              // city / town
            $table->string('administrative_area', 100)->nullable();    // state / province / prefecture
            $table->string('postal_code', 25)->nullable();
            $table->string('dependent_locality', 100)->nullable();    // neighborhood (BR, IE, CN)
            $table->string('sorting_code', 50)->nullable();           // CEDEX (FR)
            $table->string('organization', 150)->nullable();
            $table->string('recipient', 150)->nullable();
            $table->string('county', 50)->nullable();
            $table->string('delivery_instructions')->nullable();
            $table->boolean('validate_address')->default(true);

            // Deliverability outcome from the configured AddressVerifier.
            $table->string('verdict', 20)->default('unverified');

            $table->string('location', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('global_code', 20)->nullable();

            $table->boolean('address_complete')->default(false);
            $table->boolean('has_unconfirmed_components')->default(false);
            $table->boolean('has_inferred_components')->default(false);
            $table->boolean('has_replaced_components')->default(false);
            $table->boolean('business')->default(false);
            $table->boolean('po_box')->default(false);
            $table->boolean('residential')->default(false);

            $table->json('dump')->nullable();

            $driver = Schema::getConnection()->getDriverName();

            $table->string('street')->virtualAs(
                in_array($driver, ['sqlite', 'pgsql'], true)
                    ? "COALESCE(address_line1,'') || ' ' || COALESCE(address_line2,'')"
                    : "CONCAT(COALESCE(address_line1,''), ' ', COALESCE(address_line2,''))"
            );

            $table->string('full_address')->virtualAs(
                in_array($driver, ['sqlite', 'pgsql'], true)
                    ? "COALESCE(address_line1,'') || ' ' || COALESCE(address_line2,'') || ', ' || COALESCE(locality,'') || ', ' || COALESCE(administrative_area,'') || ' ' || COALESCE(postal_code,'')"
                    : "CONCAT(COALESCE(address_line1,''), ' ', COALESCE(address_line2,''), ', ', COALESCE(locality,''), ', ', COALESCE(administrative_area,''), ' ', COALESCE(postal_code,''))"
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
