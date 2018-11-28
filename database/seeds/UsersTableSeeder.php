<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        App\User::create([
            'name' => 'user',
            'email' => 'user@mail.com',
            'password' => bcrypt('abcdf1234')
        ]);

        App\User::create([
            'name' => 'user2',
            'email' => 'user2@mail.com',
            'password' => bcrypt('abcdf1234')
        ]);
    }
}
