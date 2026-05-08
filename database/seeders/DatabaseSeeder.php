<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AdminUser\Models\User;

class DatabaseSeeder extends Seeder
    {
            /**
     * Seed the application's database.
         */
    public function run(): void
        {
                    $this->call([
                                            AITemplateCategorySeeder::class,
                                            AITemplateSeeder::class,
                                        ]);

                // Create admin user from environment variables
                $adminEmail = env('ADMIN_EMAIL', 'moebarbar@hotmail.com');
                    $adminPassword = env('ADMIN_PASSWORD', 'Admin@123456');
                    $adminName = env('ADMIN_NAME', 'Admin');
                    $adminUsername = env('ADMIN_USERNAME', 'admin');

                User::query()->updateOrCreate(
                                ['email' => $adminEmail],
                                [
                                    'name' => $adminName,
                                    'username' => $adminUsername,
                                    'email' => $adminEmail,
                                    'locale' => 'en',
                                    'email_verified_at' => now(),
                                    'password' => Hash::make($adminPassword),
                                ]
                            );

                foreach (range(1, 50) as $number) {
                                User::query()->updateOrCreate(
                                                    ['username' => 'user'.$number],
                                                    [
                                                        'name' => 'User '.$number,
                                                        'username' => 'user'.$number,
                                                        'email' => 'user'.$number.'@example.com',
                                                        'locale' => 'en',
                                                        'email_verified_at' => now(),
                                                        'password' => Hash::make('123456'),
                                                    ]
                                                );
                }
        }
    }
