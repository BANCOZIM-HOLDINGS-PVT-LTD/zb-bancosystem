<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IDVerificationTest extends TestCase
{
    /**
     * Test ID verification endpoint with valid image
     *
     * @return void
     */
    public function test_id_verification_endpoint_accepts_valid_image()
    {
        // Create a fake image
        $image = UploadedFile::fake()->image('id_card.jpg', 1920, 1080);

        // Mock the Didit API response
        Http::fake([
            'verification.didit.me/*' => Http::response([
                'status' => 'approved',
                'data' => [
                    'document' => [
                        'document_type' => 'NATIONAL_ID',
                        'extracted_data' => [
                            'id_number' => '08-2047823-Q-29',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                            'date_of_birth' => '1990-05-15',
                            'address' => '123 Main Street, Harare'
                        ]
                    ],
                    'confidence_score' => 95
                ]
            ], 200)
        ]);

        // Make the request
        $response = $this->postJson('/api/verify-id-card', [
            'id_card_image' => $image,
            'country' => 'ZW',
            'document_type' => 'NATIONAL_ID'
        ]);

        // Assert response
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'ID card verified successfully'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'verified',
                         'id_number',
                         'first_name',
                         'last_name',
                         'date_of_birth',
                         'card_type',
                         'confidence'
                     ]
                 ]);
    }

    /**
     * Test ID verification endpoint rejects invalid file types
     *
     * @return void
     */
    public function test_id_verification_rejects_invalid_file_type()
    {
        // Create a fake PDF file
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/verify-id-card', [
            'id_card_image' => $file,
            'country' => 'ZW',
            'document_type' => 'NATIONAL_ID'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_card_image']);
    }

    /**
     * Test ID verification endpoint rejects oversized images
     *
     * @return void
     */
    public function test_id_verification_rejects_oversized_image()
    {
        // Create a fake image larger than 10MB
        $image = UploadedFile::fake()->create('large_image.jpg', 11000);

        $response = $this->postJson('/api/verify-id-card', [
            'id_card_image' => $image,
            'country' => 'ZW',
            'document_type' => 'NATIONAL_ID'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_card_image']);
    }

    /**
     * Test ID verification endpoint requires all fields
     *
     * @return void
     */
    public function test_id_verification_requires_all_fields()
    {
        $response = $this->postJson('/api/verify-id-card', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_card_image', 'country', 'document_type']);
    }

    /**
     * Test ID verification handles API failures gracefully
     *
     * @return void
     */
    public function test_id_verification_handles_api_failure()
    {
        $image = UploadedFile::fake()->image('id_card.jpg', 1920, 1080);

        // Mock API failure
        Http::fake([
            'verification.didit.me/*' => Http::response([
                'status' => 'failed',
                'message' => 'Document verification failed'
            ], 422)
        ]);

        $response = $this->postJson('/api/verify-id-card', [
            'id_card_image' => $image,
            'country' => 'ZW',
            'document_type' => 'NATIONAL_ID'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false
                 ]);
    }

    /**
     * Test Zimbabwean ID validator service
     *
     * @return void
     */
    public function test_zimbabwean_id_validator()
    {
        $validator = new \App\Services\ZimbabweanIDValidator();

        // Test valid ID
        $result = $validator->validate('08-2047823-Q-29');
        $this->assertTrue($result['valid']);
        $this->assertEquals('08-2047823-Q-29', $result['formatted']);

        // Test valid ID without dashes
        $result = $validator->validate('082047823Q29');
        $this->assertTrue($result['valid']);
        $this->assertEquals('08-2047823-Q-29', $result['formatted']);

        // Test invalid ID
        $result = $validator->validate('99-1234567-X-99');
        $this->assertFalse($result['valid']);

        // Test empty ID
        $result = $validator->validate('');
        $this->assertFalse($result['valid']);
    }
}

