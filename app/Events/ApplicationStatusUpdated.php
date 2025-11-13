<?php

namespace App\Events;

use App\Models\ApplicationState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $applicationState;
    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(ApplicationState $applicationState, array $notification)
    {
        $this->applicationState = $applicationState;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('application.' . $this->applicationState->session_id),
            new Channel('application.' . $this->applicationState->reference_code),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->applicationState->session_id,
            'reference_code' => $this->applicationState->reference_code,
            'notification' => $this->notification,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}