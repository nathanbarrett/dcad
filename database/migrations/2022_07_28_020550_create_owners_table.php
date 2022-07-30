<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('name_2')->nullable();
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code', 5)->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('owner_property', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')
                ->references('id')->on('owners');
            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')
                ->references('id')->on('properties');
            $table->boolean('active')->default(0)->index();
            $table->date('deed_transferred_at')->nullable()->index();
            $table->string('account_num')->nullable()->index();
            $table->decimal('ownership_percent', 5, 2)->default(100.00)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('owner_property');
        Schema::dropIfExists('owners');
    }
};
