<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use Illuminate\Http\Request;

class PendingApplicationController extends Controller
{
    /**
     * Check if user has pending applications (not yet delivered)
     */
    public function check(Request $request)
    {
        // If user is not authenticated, they can apply
        if (!auth()->check()) {
            return response()->json(['can_apply' => true, 'has_pending' => false]);
        }

        $userId = auth()->id();
        
        // Find any application that is not yet delivered
        $pendingApp = ApplicationState::where('user_identifier', $userId)
            ->whereNull('delivered_at')
            ->latest()
            ->first();

        if ($pendingApp) {
            $metadata = $pendingApp->metadata ?? [];
            $status = $metadata['admin_status'] ?? 'pending';
            
            // Don't block if application was rejected or cancelled
            if (in_array($status, ['rejected', 'cancelled'])) {
                return response()->json(['can_apply' => true, 'has_pending' => false]);
            }

            return response()->json([
                'can_apply' => false,
                'has_pending' => true,
                'pending_application' => [
                    'reference_code' => $pendingApp->reference_code,
                    'status' => $status,
                    'created_at' => $pendingApp->created_at->toISOString(),
                ],
            ]);
        }

        return response()->json(['can_apply' => true, 'has_pending' => false]);
    }
}
