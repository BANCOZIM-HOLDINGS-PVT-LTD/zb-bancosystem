<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WhatsAppStateMachine;
use App\Services\WhatsAppCloudApiService;
use App\Services\StateManager;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class WhatsAppStateMachineTest extends TestCase
{
    private $stateMachine;
    private $whatsAppService;
    private $stateManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->whatsAppService = $this->createMock(WhatsAppCloudApiService::class);
        $this->stateManager = $this->createMock(StateManager::class);
        
        $this->stateMachine = new WhatsAppStateMachine(
            $this->whatsAppService,
            $this->stateManager
        );
    }

    /** @test */
    public function it_has_no_more_than_10_rows_in_intent_selection()
    {
        $reflection = new ReflectionClass($this->stateMachine);
        $method = $reflection->getMethod('sendIntentSelectionList');
        $method->setAccessible(true);

        // We need to capture the arguments passed to sendInteractiveList
        $this->whatsAppService->expects($this->once())
            ->method('sendInteractiveList')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(function ($sections) {
                    $totalRows = 0;
                    foreach ($sections as $section) {
                        $totalRows += count($section['rows']);
                    }
                    return $totalRows <= 10;
                })
            )
            ->willReturn(true);

        $method->invoke($this->stateMachine, '263771234567');
    }

    /** @test */
    public function it_has_no_more_than_10_rows_in_hardcoded_categories()
    {
        $reflection = new ReflectionClass($this->stateMachine);
        $property = $reflection->getProperty('hardcodedCategories');
        $property->setAccessible(true);
        $categories = $property->getValue($this->stateMachine);

        foreach ($categories as $intent => $cats) {
            // categories + 1 for 'back' button
            $this->assertLessThanOrEqual(10, count($cats) + 1, "Intent {$intent} has too many categories");
        }
    }
}
