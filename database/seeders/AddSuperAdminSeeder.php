<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AddSuperAdminSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //Add Super Admin User
        $super_admin_role = Role::where('name', 'Super Admin')->first();
        $user = User::updateOrCreate(['id' => 1], [
            'first_name' => 'super',
            'last_name'  => 'admin',
            'email'      => 'superadmin@gmail.com',
            'password'   => Hash::make('superadmin'),
            'gender'     => 'male',
            'image'      => 'logo.svg',
            'mobile'     => ""
        ]);
        $user->assignRole([$super_admin_role->id]);

//        SessionYear::updateOrCreate(['id' => 1], [
//            'name'       => '2022-23',
//            'default'    => 1,
//            'start_date' => '2022-06-01',
//            'end_date'   => '2023-04-30',
//        ]);

        SystemSetting::upsert([
            ["id" => 1, "name" => "time_zone", "data" => "Asia/Kolkata", "type" => "string"],
            ["id" => 2, "name" => "date_format", "data" => "d-m-Y", "type" => "date"],
            ["id" => 3, "name" => "time_format", "data" => "h:i A", "type" => "time"],
            ["id" => 4, "name" => "theme_color", "data" => "#22577A", "type" => "string"],
            ["id" => 5, "name" => "session_year", "data" => 1, "type" => "string"],
            ["id" => 6, "name" => "system_version", "data" => "1.1.0", "type" => "string"],
            ["id" => 7, "name" => "email_verified", "data" => 0, "type" => "boolean"],
            ["id" => 8, "name" => "subscription_alert", "data" => 7, "type" => "integer"],
            ["id" => 9, "name" => "currency_code", "data" => "USD", "type" => "string"],
            ["id" => 10, "name" => "currency_symbol", "data" => "$", "type" => "string"],
            ["id" => 11, "name" => "additional_billing_days", "data" => "5", "type" => "integer"],
            ["id" => 12, "name" => "system_name", "data" => "eSchool Saas - School Management System", "type" => "string"],
            ["id" => 13, "name" => "address", "data" => "#262-263, Time Square Empire, SH 42 Mirjapar highway, Bhuj - Kutch 370001 Gujarat India.", "type" => "string"],
            ["id" => 14, "name" => "billing_cycle_in_days", "data" => "30", "type" => "integer"],
            ["id" => 15, "name" => "current_plan_expiry_warning_days", "data" => "7", "type" => "integer"],
            ["id" => 16, "name" => "front_site_theme_color", "data" => "#e9f9f3", "type" => "text"],
            ["id" => 17, "name" => "primary_color", "data" => "#3ccb9b", "type" => "text"],
            ["id" => 18, "name" => "secondary_color", "data" => "#245a7f", "type" => "text"],
            ["id" => 19, "name" => "short_description", "data" => "eSchool-Saas - Manage Your School", "type" => "text"],
            ["id" => 20, "name" => "facebook", "data" => "https://www.facebook.com/wrteam.in/", "type" => "text"],
            ["id" => 21, "name" => "instagram", "data" => "https://www.instagram.com/wrteam.in/", "type" => "text"],
            ["id" => 22, "name" => "linkedin", "data" => "https://in.linkedin.com/company/wrteam", "type" => "text"],
            ["id" => 23, "name" => "footer_text", "data" => "<p>&copy;&nbsp;<strong><a href='https://wrteam.in/' target='_blank' rel='noopener noreferrer'>WRTeam</a></strong>. All Rights Reserved</p>", "type" => "text"],
            ["id" => 24, "name" => "tagline", "data" => "We Provide the best Education", "type" => "text"],

        ], ['id'], ['name', 'data', 'type']);
    }
}
