<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetAFDCounter extends Command
{
    //docker exec -it pontentiqueapi-php-1 bash -c "php artisan app:reset-afd-counter"
    protected $signature = 'app:reset-afd-counter';

    protected $description = 'Resets the AFD global counter';

    public function handle()
    {
        Cache::forever('afd_counter', 0);

        $this->info('Counter reset successfully.');
    }
}
