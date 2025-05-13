<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(1000)->create();

        User::factory()->create([
            'name' => 'Sollu Teknologi Indonesia',
            'email' => 'superadmin@sollu.id',
            'role' => 'superadmin'
        ]);

        collect([
            ['code' => 'id', 'name' => 'Bahasa Indonesia', 'is_default' => true],
            ['code' => 'en', 'name' => 'English'],
        ])->each(
            fn($language) =>
            Language::factory()->create($language)
        );
    }
}
