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
        $this->referenceCodeService = new ReferenceCodeService;
    }

    /** @test */
    public function it_generates_a_unique_reference_code()
    {
        // Create an application state
        $applicationState = ApplicationState::create([
            'session_id' => 'test-session-id',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Generate a reference code
        $referenceCode = $this->referenceCodeService->generateReferenceCode('test-session-id');

        // Assert the reference code is 6 characters long
        $this->assertEquals(6, strlen($referenceCode));

        // Assert the reference code is alphanumeric
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $referenceCode);

        // Refresh the application state from the database
        $applicationState->refresh();

        // Assert the reference code was stored in the application state
        $this->assertEquals($referenceCode, $applicationState->reference_code);

        // Assert the reference code expiration date was set
        $this->assertNotNull($applicationState->reference_code_expires_at);
    }

    /** @test */
    public function it_validates_reference_codes()
    {
        // Create an application state with a reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'test-session-id',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'reference_code' => 'ABC123',
            'reference_code_expires_at' => Carbon::now()->addDays(30),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Assert the reference code is valid
        $this->assertTrue($this->referenceCodeService->validateReferenceCode('ABC123'));

        // Assert an invalid reference code is not valid
        $this->assertFalse($this->referenceCodeService->validateReferenceCode('XYZ789'));

        // Assert an expired reference code is not valid
        $applicationState->update([
            'reference_code_expires_at' => Carbon::now()->subDays(1),
        ]);

        $this->assertFalse($this->referenceCodeService->validateReferenceCode('ABC123'));
    }

    /** @test */
    public function it_retrieves_application_state_by_reference_code()
    {
        // Create an application state with a reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'test-session-id',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'reference_code' => 'ABC123',
            'reference_code_expires_at' => Carbon::now()->addDays(30),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Retrieve the application state by reference code
        $retrievedState = $this->referenceCodeService->getStateByReferenceCode('ABC123');

        // Assert the correct application state was retrieved
        $this->assertNotNull($retrievedState);
        $this->assertEquals('test-session-id', $retrievedState->session_id);

        // Assert an invalid reference code returns null
        $this->assertNull($this->referenceCodeService->getStateByReferenceCode('XYZ789'));

        // Assert an expired reference code returns null
        $applicationState->update([
            'reference_code_expires_at' => Carbon::now()->subDays(1),
        ]);

        $this->assertNull($this->referenceCodeService->getStateByReferenceCode('ABC123'));
    }

    /** @test */
    public function it_extends_reference_code_expiration()
    {
        // Create an application state with a reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'test-session-id',
            'channel' => 'web',
            'user_identifier' => 'test-user',
            'current_step' => 'personal_info',
            'form_data' => ['test' => 'data'],
            'reference_code' => 'ABC123',
            'reference_code_expires_at' => Carbon::now()->addDays(5),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Get the original expiration date
        $originalExpiration = Carbon::parse($applicationState->reference_code_expires_at);

        // Extend the reference code expiration
        $result = $this->referenceCodeService->extendReferenceCode('ABC123', 60);

        // Assert the extension was successful
        $this->assertTrue($result);

        // Refresh the application state from the database
        $applicationState->refresh();

        // Assert the expiration date was extended
        $this->assertTrue(Carbon::parse($applicationState->reference_code_expires_at)->gt($originalExpiration));

        // Assert extending a non-existent reference code returns false
        $this->assertFalse($this->referenceCodeService->extendReferenceCode('XYZ789'));
    }
}
