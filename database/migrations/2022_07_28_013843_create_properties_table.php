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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('address_1')->nullable()->index();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 5)->nullable()->index();
            $table->decimal('lat', 10, 7)->nullable()->index();
            $table->decimal('lng', 10, 7)->nullable()->index();
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
        Schema::dropIfExists('properties');
    }
};
