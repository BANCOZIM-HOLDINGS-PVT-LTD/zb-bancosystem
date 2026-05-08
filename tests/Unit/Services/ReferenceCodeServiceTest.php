<?php

namespace Tests\Unit\Services;

use App\Models\ApplicationState;
use App\Services\ReferenceCodeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReferenceCodeService $referenceCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referenceCodeService = new ReferenceCodeService();
    }

    /** @test */
    public function it_generates_a_unique_reference_code()
    {
        // Create an application state with a national ID in form_data
        $applicationState = ApplicationState::create([
            'session_id' => 'session-1',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => [
                'formResponses' => [
                    'nationalIdNumber' => '12-345678-A-12'
                ]
            ],
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Generate a reference code
        $referenceCode = $this->referenceCodeService->generateReferenceCode('session-1');

        // Assert the reference code is the sanitized national ID
        $this->assertEquals('12345678A12', $referenceCode);
        
        // Refresh the application state from the database
        $applicationState->refresh();

        // Assert the reference code was stored in the application state
        $this->assertEquals($referenceCode, $applicationState->reference_code);
    }

    /** @test */
    public function it_validates_reference_codes()
    {
        // Create an application state with a reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'session-2',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'reference_code' => '12345678A12',
            'reference_code_expires_at' => Carbon::now()->addDays(30),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Assert the reference code is valid
        $this->assertTrue($this->referenceCodeService->validateReferenceCode('12345678A12'));
        
        // Assert an invalid reference code is not valid
        $this->assertFalse($this->referenceCodeService->validateReferenceCode('XYZ789'));
    }

    /** @test */
    public function it_retrieves_application_state_by_reference_code()
    {
        // Create an application state with a reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'session-3',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'reference_code' => '12345678A12',
            'reference_code_expires_at' => Carbon::now()->addDays(30),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Retrieve the application state by reference code
        $retrievedState = $this->referenceCodeService->getStateByReferenceCode('12345678A12');

        // Assert the correct application state was retrieved
        $this->assertNotNull($retrievedState);
        $this->assertEquals('session-3', $retrievedState->session_id);
    }

    /** @test */
    public function it_extends_reference_code_expiration()
    {
        // Create an application state with a reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'session-4',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'reference_code' => '12345678A12',
            'reference_code_expires_at' => Carbon::now()->addDays(5),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Get the original expiration date
        $originalExpiration = Carbon::parse($applicationState->reference_code_expires_at);

        // Extend the reference code expiration
        $result = $this->referenceCodeService->extendReferenceCode('12345678A12', 60);

        // Assert the extension was successful
        $this->assertTrue($result);

        // Refresh the application state from the database
        $applicationState->refresh();

        // Assert the expiration date was extended
        $this->assertTrue(Carbon::parse($applicationState->reference_code_expires_at)->gt($originalExpiration));
    }
}