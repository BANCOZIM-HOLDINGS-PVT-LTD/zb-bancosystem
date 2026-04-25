<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Models\PaymentReminder;
use App\Jobs\SendAbandonmentReminderJob;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Mockery;

class AbandonmentReminderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_reminders_for_abandoned_applications()
    {
        // Mock NotificationService
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('sendSMS')
            ->twice() // We expect 2 reminders to be sent (app1 and app3)
            ->andReturn(true);

        // 1. Abandoned application (2.5 hours ago, has phone) - SHOULD REMIND
        $app1 = ApplicationState::create([
            'session_id' => 'session-1',
            'user_identifier' => 'user-1',
            'channel' => 'web',
            'current_step' => 'employer',
            'form_data' => [
                'formResponses' => ['mobile' => '263771111111', 'firstName' => 'John']
            ],
        ]);
        \DB::table('application_states')->where('id', $app1->id)->update([
            'updated_at' => Carbon::now()->subHours(2)->subMinutes(30)
        ]);
        $app1->refresh();

        // 2. Recently active application (30 mins ago) - SHOULD NOT REMIND
        $app2 = ApplicationState::create([
            'session_id' => 'session-2',
            'user_identifier' => 'user-2',
            'channel' => 'web',
            'current_step' => 'employer',
            'form_data' => [
                'formResponses' => ['mobile' => '263772222222']
            ],
        ]);
        \DB::table('application_states')->where('id', $app2->id)->update([
            'updated_at' => Carbon::now()->subMinutes(30)
        ]);
        $app2->refresh();

        // 3. Abandoned application (5 hours ago, has phone in metadata) - SHOULD REMIND
        $app3 = ApplicationState::create([
            'session_id' => 'session-3',
            'user_identifier' => 'user-3',
            'channel' => 'web',
            'current_step' => 'product',
            'form_data' => [],
            'metadata' => ['phone_number' => '263773333333'],
        ]);
        \DB::table('application_states')->where('id', $app3->id)->update([
            'updated_at' => Carbon::now()->subHours(5)
        ]);
        $app3->refresh();

        // 4. Completed application - SHOULD NOT REMIND
        $app4 = ApplicationState::create([
            'session_id' => 'session-4',
            'user_identifier' => 'user-4',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => ['mobile' => '263774444444']
            ],
        ]);
        \DB::table('application_states')->where('id', $app4->id)->update([
            'updated_at' => Carbon::now()->subHours(3)
        ]);
        $app4->refresh();

        // 5. Awaiting deposit application - SHOULD NOT REMIND (handled by other job)
        $app5 = ApplicationState::create([
            'session_id' => 'session-5',
            'user_identifier' => 'user-5',
            'channel' => 'web',
            'current_step' => 'summary',
            'status' => 'awaiting_deposit',
            'form_data' => [
                'formResponses' => ['mobile' => '263775555555']
            ],
        ]);
        \DB::table('application_states')->where('id', $app5->id)->update([
            'updated_at' => Carbon::now()->subHours(3)
        ]);
        $app5->refresh();

        // 6. Abandoned but already reminded - SHOULD NOT REMIND
        $app6 = ApplicationState::create([
            'session_id' => 'session-6',
            'user_identifier' => 'user-6',
            'channel' => 'web',
            'current_step' => 'employer',
            'form_data' => [
                'formResponses' => ['mobile' => '263776666666']
            ],
        ]);
        \DB::table('application_states')->where('id', $app6->id)->update([
            'updated_at' => Carbon::now()->subHours(4)
        ]);
        $app6->refresh();
        PaymentReminder::create([
            'application_state_id' => $app6->id,
            'reminder_type' => 'abandonment',
            'reminder_stage' => '2_hours',
            'sent_at' => Carbon::now()->subHours(1),
        ]);

        // Run the job
        $job = new SendAbandonmentReminderJob();
        $job->handle($notificationService);

        // Verify reminders were recorded in DB
        $this->assertTrue(PaymentReminder::where('application_state_id', $app1->id)->where('reminder_type', 'abandonment')->exists());
        $this->assertTrue(PaymentReminder::where('application_state_id', $app3->id)->where('reminder_type', 'abandonment')->exists());
        
        // Verify others were NOT recorded
        $this->assertFalse(PaymentReminder::where('application_state_id', $app2->id)->exists());
        $this->assertFalse(PaymentReminder::where('application_state_id', $app4->id)->exists());
        $this->assertFalse(PaymentReminder::where('application_state_id', $app5->id)->exists());
        
        // App 6 should still have only 1 reminder
        $this->assertEquals(1, PaymentReminder::where('application_state_id', $app6->id)->where('reminder_type', 'abandonment')->count());
    }
}
