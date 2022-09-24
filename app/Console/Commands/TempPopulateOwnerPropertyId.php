<?php

namespace App\Console\Commands;

use App\Models\OwnerProperty;
use App\Models\PropertyChange;
use Illuminate\Console\Command;

class TempPopulateOwnerPropertyId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcad:tmp:update_owner_property_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates a new column in the property_changes table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $query = PropertyChange::query()
            ->whereNull('owner_property_id')
            ->where('type', PropertyChange::TYPE_OWNER_UPDATE);

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $query->chunkById(1000, function ($changes) use ($bar) {
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
                $bar->advance(count($changes));
            });

        $bar->finish();

        return self::SUCCESS;
    }
}
