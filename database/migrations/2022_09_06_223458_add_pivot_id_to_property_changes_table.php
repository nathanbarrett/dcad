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

        PropertyChange::query()
            ->whereNull('owner_property_id')
            ->where('type', PropertyChange::TYPE_OWNER_UPDATE)
            ->chunkById(1000, function ($changes) {
                /* @var PropertyChange $propertyChange */
                foreach ($changes as $propertyChange) {
                    if ($propertyChange->context->has('pivot_id')) {
                        $propertyChange->owner_property_id = $propertyChange->context->get('pivot_id');
                        $propertyChange->context = $propertyChange->context->except('pivot_id');
                        $propertyChange->save();
                        continue;
                    }
                    $pivotMatch = OwnerProperty::query()
                        ->where('property_id', $propertyChange->property_id)
                        ->whereBetween('created_at', [$propertyChange->created_at->subMinutes(1), $propertyChange->created_at->addMinutes(1)])
                        ->first();

                    if ($pivotMatch) {
                        $propertyChange->owner_property_id = $pivotMatch->id;
                        $propertyChange->save();
                    }
                }
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
