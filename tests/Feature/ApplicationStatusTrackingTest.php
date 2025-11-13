<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Services\NotificationService;
use App\Services\ReferenceCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ApplicationStatusTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;
    protected $referenceCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
        $this->referenceCodeService = app(ReferenceCodeService::class);
    }

    /** @test */
    public function it_can_get_application_status_with_enhanced_tracking()
    {
        // Create a test application
        $application = ApplicationState::create([
            'session_id' => 'test-session-123',
            'reference_code' => 'ABC123',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@example.com',
                ],
                'business' => 'Small Business Loan',
                'amount' => 50000,
            ],
            'metadata' => [
                'status' => 'under_review',
                'documents_verified' => true,
                'documents_verified_at' => now()->subDays(2)->toIso8601String(),
                'credit_check_completed' => true,
                'credit_check_completed_at' => now()->subDays(1)->toIso8601String(),
            ],
        ]);

        // Test getting status
        $response = $this->getJson("/api/application/status/{$application->reference_code}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'sessionId',
                'status',
                'applicantName',
                'business',
                'loanAmount',
                'submittedAt',
                'lastUpdated',
                'timeline',
                'progressPercentage',
                'estimatedCompletionDate',
                'nextAction',
                'notifications',
            ]);

        $data = $response->json();
        
        // Verify enhanced tracking data
        $this->assertEquals('under_review', $data['status']);
        $this->assertEquals('John Doe', $data['applicantName']);
        $this->assertGreaterThan(0, $data['progressPercentage']);
        $this->assertNotEmpty($data['timeline']);
        $this->assertIsArray($data['notifications']);
    }

    /** @test */
    public function it_can_get_detailed_progress_information()
    {
        // Create a test application
        $application = ApplicationState::create([
            'session_id' => 'test-session-456',
            'reference_code' => 'DEF456',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
                'business' => 'Equipment Loan',
                'amount' => 75000,
            ],
            'metadata' => [
                'status' => 'approved',
                'documents_verified' => true,
                'documents_verified_at' => now()->subDays(3)->toIso8601String(),
                'credit_check_completed' => true,
                'credit_check_completed_at' => now()->subDays(2)->toIso8601String(),
                'approval_details' => [
                    'amount' => 75000,
                    'approved_at' => now()->subDays(1)->toIso8601String(),
                    'disbursement_date' => now()->addDays(1)->format('Y-m-d'),
                ],
            ],
        ]);

        // Test getting progress details
        $response = $this->getJson("/api/application/progress/{$application->reference_code}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'currentStage' => [
                    'name',
                    'description',
                    'icon',
                    'color',
                ],
                'completedMilestones',
                'upcomingMilestones',
                'estimatedTimeRemaining',
                'actionItems',
            ]);

        $data = $response->json();
        
        // Verify progress details
        $this->assertEquals('Approved', $data['currentStage']['name']);
        $this->assertNotEmpty($data['completedMilestones']);
        $this->assertIsArray($data['upcomingMilestones']);
    }

    /** @test */
    public function it_can_send_milestone_notifications()
    {
        // Create a test application
        $application = ApplicationState::create([
            'session_id' => 'test-session-789',
            'reference_code' => 'GHI789',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'Bob',
                    'lastName' => 'Johnson',
                ],
            ],
            'metadata' => [],
        ]);

        // Send milestone notification
        $success = $this->notificationService->sendMilestoneNotification(
            $application,
            'documents_verified',
            ['message' => 'All documents have been successfully verified.']
        );

        $this->assertTrue($success);

        // Verify notification was stored
        $application->refresh();
        $metadata = $application->metadata;
        
        $this->assertTrue($metadata['documents_verified']);
        $this->assertNotEmpty($metadata['notifications']);
        $this->assertEquals('Documents Verified', $metadata['notifications'][0]['title']);
    }

    /** @test */
    public function it_can_update_status_and_send_notifications()
    {
        // Create a test application
        $application = ApplicationState::create([
            'session_id' => 'test-session-update',
            'reference_code' => 'UPD123',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Wilson',
                ],
            ],
            'metadata' => [
                'status' => 'under_review',
            ],
        ]);

        // Update status to approved
        $response = $this->postJson("/api/application/status/{$application->session_id}", [
            'status' => 'approved',
            'approval_details' => [
                'amount' => 60000,
                'disbursement_date' => now()->addDays(2)->format('Y-m-d'),
            ],
        ]);

        $response->assertStatus(200);

        // Verify status was updated
        $application->refresh();
        $metadata = $application->metadata;
        
        $this->assertEquals('approved', $metadata['status']);
        $this->assertNotEmpty($metadata['status_history']);
        $this->assertEquals(60000, $metadata['approval_details']['amount']);
    }

    /** @test */
    public function it_can_mark_notifications_as_read()
    {
        // Create a test application with notifications
        $application = ApplicationState::create([
            'session_id' => 'test-session-notif',
            'reference_code' => 'NOT123',
            'current_step' => 'completed',
            'form_data' => [],
            'metadata' => [
                'notifications' => [
                    [
                        'id' => 'notif_1',
                        'type' => 'info',
                        'title' => 'Test Notification',
                        'message' => 'This is a test notification',
                        'timestamp' => now()->toIso8601String(),
                        'read' => false,
                    ],
                ],
            ],
        ]);

        // Mark notification as read
        $response = $this->postJson("/api/application/notifications/{$application->reference_code}/mark-read", [
            'notification_ids' => ['notif_1'],
        ]);

        $response->assertStatus(200);

        // Verify notification was marked as read
        $application->refresh();
        $notifications = $application->metadata['notifications'];
        
        $this->assertTrue($notifications[0]['read']);
    }

    /** @test */
    public function it_builds_comprehensive_timeline_with_details()
    {
        // Create a test application with various milestones
        $application = ApplicationState::create([
            'session_id' => 'test-timeline',
            'reference_code' => 'TML123',
            'current_step' => 'completed',
            'form_data' => [],
            'metadata' => [
                'status' => 'approved',
                'documents_verified' => true,
                'documents_verified_at' => now()->subDays(4)->toIso8601String(),
                'credit_check_completed' => true,
                'credit_check_completed_at' => now()->subDays(3)->toIso8601String(),
                'committee_review_started' => true,
                'committee_review_started_at' => now()->subDays(2)->toIso8601String(),
                'status_updated_at' => now()->subDays(1)->toIso8601String(),
            ],
        ]);

        // Get status to build timeline
        $response = $this->getJson("/api/application/status/{$application->reference_code}");
        
        $response->assertStatus(200);
        $data = $response->json();
        
        // Verify timeline includes detailed steps
        $timeline = $data['timeline'];
        $this->assertGreaterThan(5, count($timeline)); // Should have multiple detailed steps
        
        // Check for specific timeline events
        $timelineTitles = array_column($timeline, 'title');
        $this->assertContains('Application Started', $timelineTitles);
        $this->assertContains('Application Submitted', $timelineTitles);
        $this->assertContains('Document Verification', $timelineTitles);
        $this->assertContains('Credit Assessment', $timelineTitles);
        $this->assertContains('Committee Review', $timelineTitles);
        $this->assertContains('Application Approved', $timelineTitles);
    }
}