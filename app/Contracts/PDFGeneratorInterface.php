<?php

namespace App\Contracts;

use App\Models\ApplicationState;

interface PDFGeneratorInterface
{
    /**
     * Generate PDF for application state
     */
    public function generatePDF(ApplicationState $applicationState, array $options = []): array;

    /**
     * Generate PDF from form data
     */
    public function generateFromFormData(array $formData, string $formId, array $options = []): array;

    /**
     * Validate application state for PDF generation
     */
    public function validateForGeneration(ApplicationState $applicationState): array;

    /**
     * Get supported form types
     */
    public function getSupportedFormTypes(): array;
}
