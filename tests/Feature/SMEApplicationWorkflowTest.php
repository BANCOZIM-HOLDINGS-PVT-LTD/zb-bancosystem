<?php

namespace Tests\Feature;

use App\Models\ApplicationState;
use App\Services\ApplicationWorkflowService;
use App\Services\SMEApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SMEApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_initializes_sme_applications_with_type_and_workflow_metadata(): void
    {
        $application = ApplicationState::create([
            'session_id' => 'sme-session-001',
            'channel' => 'web',
            'user_identifier' => 'client@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'intent' => 'smeBiz',
                'formType' => 'sme_business',
                'companyType' => 'private_limited',
                'companyTypeName' => 'Private Limited Company (Pvt Ltd)',
            ],
            'metadata' => [],
        ]);

        $service = new SMEApplicationWorkflowService(
            $this->createMock(ApplicationWorkflowService::class)
        );

        $service->initializeSMEApplication($application);

        $application->refresh();

        $this->assertSame('sme', $application->application_type);
        $this->assertSame('sme', $application->metadata['workflow_type']);
        $this->assertSame('submitted', $application->metadata['sme_stage']);
        $this->assertSame('private_limited', $application->metadata['company_type']);
        $this->assertSame('Private Limited Company (Pvt Ltd)', $application->metadata['company_type_name']);
        $this->assertArrayHasKey('sme_submitted_at', $application->metadata);
    }

    public function test_it_validates_required_sme_company_and_business_fields(): void
    {
        $application = new ApplicationState([
            'form_data' => [
                'companyType' => 'trust',
                'formResponses' => [
                    'businessName' => 'Trust Trading',
                    'businessRegistrationNumber' => 'REG-123',
                    'businessAddress' => '10 First Street, Harare',
                    'firstName' => 'Tariro',
                    'surname' => 'Moyo',
                    'nationalIdNumber' => '63-123456-A-12',
                    'mobile' => '+263771234567',
                ],
            ],
        ]);

        $service = new SMEApplicationWorkflowService(
            $this->createMock(ApplicationWorkflowService::class)
        );

        $result = $service->validateSMEApplication($application);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    public function test_it_rejects_invalid_sme_company_type(): void
    {
        $application = new ApplicationState([
            'form_data' => [
                'companyType' => 'employer',
                'formResponses' => [
                    'businessName' => 'Invalid Business',
                    'businessRegistrationNumber' => 'REG-999',
                    'businessAddress' => '20 Second Street, Harare',
                    'firstName' => 'Rudo',
                    'surname' => 'Ncube',
                    'nationalIdNumber' => '63-654321-B-12',
                    'mobile' => '+263772345678',
                ],
            ],
        ]);

        $service = new SMEApplicationWorkflowService(
            $this->createMock(ApplicationWorkflowService::class)
        );

        $result = $service->validateSMEApplication($application);

        $this->assertFalse($result['valid']);
        $this->assertContains('A valid company type is required for SME applications.', $result['errors']);
    }

    public function test_it_advances_sme_stage_and_records_transition(): void
    {
        $application = ApplicationState::create([
            'session_id' => 'sme-session-002',
            'channel' => 'web',
            'user_identifier' => 'client@example.com',
            'current_step' => 'pending_review',
            'form_data' => [
                'intent' => 'smeBiz',
                'formType' => 'sme_business',
            ],
            'metadata' => [
                'workflow_type' => 'sme',
                'sme_stage' => 'submitted',
            ],
            'application_type' => 'sme',
        ]);

        $service = new SMEApplicationWorkflowService(
            $this->createMock(ApplicationWorkflowService::class)
        );

        $advanced = $service->advanceToStage($application, SMEApplicationWorkflowService::STAGE_DOCUMENT_REVIEW, [
            'notes' => 'Documents received.',
        ]);

        $application->refresh();

        $this->assertTrue($advanced);
        $this->assertSame(SMEApplicationWorkflowService::STAGE_DOCUMENT_REVIEW, $application->metadata['sme_stage']);
        $this->assertSame('Documents received.', $application->metadata['sme_sme_document_review_notes']);
        $this->assertDatabaseHas('state_transitions', [
            'state_id' => $application->id,
            'from_step' => 'submitted',
            'to_step' => SMEApplicationWorkflowService::STAGE_DOCUMENT_REVIEW,
            'channel' => 'admin',
        ]);
    }

    public function test_it_blocks_invalid_sme_stage_transitions(): void
    {
        $application = ApplicationState::create([
            'session_id' => 'sme-session-003',
            'channel' => 'web',
            'user_identifier' => 'client@example.com',
            'current_step' => 'pending_review',
            'form_data' => [
                'intent' => 'smeBiz',
                'formType' => 'sme_business',
            ],
            'metadata' => [
                'workflow_type' => 'sme',
                'sme_stage' => 'submitted',
            ],
            'application_type' => 'sme',
        ]);

        $service = new SMEApplicationWorkflowService(
            $this->createMock(ApplicationWorkflowService::class)
        );

        $advanced = $service->advanceToStage($application, SMEApplicationWorkflowService::STAGE_APPROVED);

        $application->refresh();

        $this->assertFalse($advanced);
        $this->assertSame('submitted', $application->metadata['sme_stage']);
        $this->assertDatabaseMissing('state_transitions', [
            'state_id' => $application->id,
            'to_step' => SMEApplicationWorkflowService::STAGE_APPROVED,
        ]);
    }
}
