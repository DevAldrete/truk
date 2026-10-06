<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The demo dataset is the only seeder today; it is safe to run repeatedly on
     * a freshly migrated database and is what `composer db:reset` runs.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
