<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'club_name' => fake()->optional()->company(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraphs(2, true),
            'status' => TicketStatus::New,
            'admin_notes' => null,
            'handled_by' => null,
            'resolved_at' => null,
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::InReview,
        ]);
    }

    public function resolved(?User $handler = null): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Resolved,
            'handled_by' => $handler?->id ?? User::factory(),
            'resolved_at' => now(),
            'admin_notes' => fake()->sentence(),
        ]);
    }

    public function discarded(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Discarded,
            'admin_notes' => 'Descartado por duplicado o spam.',
        ]);
    }
}

