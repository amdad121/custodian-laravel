<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Commands;

use Illuminate\Console\Command;

/** Applies automated code rewrites for upgrading between Custodian versions. */
class UpgradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'custodian:upgrade';

    /**
     * The console command description.
     */
    protected $description = 'Upgrade application code between Custodian versions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('No upgrade steps are defined yet — this is the first release of Custodian.');
        $this->info('Future releases that change public API will add automated rewrites here.');

        return self::SUCCESS;
    }
}
