<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_platform_admin', true)->first();

        SupportTicket::factory()->count(5)->create();
        SupportTicket::factory()->inReview()->count(3)->create();
        SupportTicket::factory()->resolved($admin)->count(4)->create();
        SupportTicket::factory()->discarded()->count(2)->create();
    }
}

