    /**
     * Download account opening PDF
     * 
     * @param int $id The account opening ID
     * @return Response The HTTP response
     */
    public function downloadAccountOpening(int $id)
    {
        try {
            $accountOpening = \App\Models\AccountOpening::findOrFail($id);
            
            // Generate PDF using AccountOpeningService
            $service = app(\App\Services\AccountOpeningService::class);
            $pdfPath = $service->generatePDF($accountOpening);
            
            $filename = "account_opening_{$accountOpening->reference_code}.pdf";
            
            return response()->download(storage_path("app/public/{$pdfPath}"), $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error('Account opening PDF download failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Failed to download PDF',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
