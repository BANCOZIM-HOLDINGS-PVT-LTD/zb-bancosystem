<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Convert National IDs from old format (XX-XXXXXXXA12) to new format (XX-XXXXXXX-A-12)
     * Old format: 63-2018123X18 (no dashes around letter)
     * New format: 63-2018123-X-18 (dashes around letter)
     */
    public function up(): void
    {
        // Get all users with national_id
        $users = DB::table('users')->whereNotNull('national_id')->get();

        foreach ($users as $user) {
            $oldId = $user->national_id;

            // Check if it's in old format (XX-XXXXXXXA12 or XX-XXXXXXA12)
            // Pattern: XX-XXXXXXX[A-Z]XX or XX-XXXXXX[A-Z]XX
            if (preg_match('/^([0-9]{2})-([0-9]{6,7})([A-Z])([0-9]{2})$/', $oldId, $matches)) {
                // Convert to new format: XX-XXXXXXX-A-XX
                $newId = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . '-' . $matches[4];

                // Update the user's national_id
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['national_id' => $newId]);

                echo "Updated user {$user->id}: {$oldId} -> {$newId}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Convert back from new format to old format
     */
    public function down(): void
    {
        // Get all users with national_id
        $users = DB::table('users')->whereNotNull('national_id')->get();

        foreach ($users as $user) {
            $newId = $user->national_id;

            // Check if it's in new format (XX-XXXXXXX-A-XX)
            if (preg_match('/^([0-9]{2})-([0-9]{6,7})-([A-Z])-([0-9]{2})$/', $newId, $matches)) {
                // Convert back to old format: XX-XXXXXXXA12
                $oldId = $matches[1] . '-' . $matches[2] . $matches[3] . $matches[4];

                // Update the user's national_id
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['national_id' => $oldId]);
            }
        }
    }
};
