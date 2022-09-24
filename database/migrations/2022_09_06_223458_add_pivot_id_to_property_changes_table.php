<?php

use App\Models\OwnerProperty;
use App\Models\PropertyChange;
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
        Schema::table('property_changes', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_property_id')
                ->nullable()
                ->after('property_id');
            $table->foreign('owner_property_id')
                ->references('id')
                ->on('owner_property');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_changes', function (Blueprint $table) {
            $table->dropColumn('owner_property_id');
        });
    }
};
