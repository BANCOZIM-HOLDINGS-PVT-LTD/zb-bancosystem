<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use App\Services\ReferenceCodeService;
use App\Services\ZimPost\Exceptions\ZimPostApiException;
use App\Services\ZimPost\ZimPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeliveryTrackingController extends Controller
{
    protected $referenceCodeService;
    protected ZimPostService $zimPost;

    public function __construct(ReferenceCodeService $referenceCodeService, ZimPostService $zimPost)
    {
        $this->referenceCodeService = $referenceCodeService;
        $this->zimPost = $zimPost;
    }

    public function getStatus(string $reference): JsonResponse
    {
        $reference = strtoupper(str_replace([" ", "-"], "", trim($reference)));

        // Search for loan applications
        $application = $this->referenceCodeService->getStateByReferenceCode($reference);

        if (!$application) {
            $application = ApplicationState::where("session_id", $reference)->first();
        }

        // If still not found, search in form_data for National ID
        if (!$application) {
            $applications = ApplicationState::whereNotNull("form_data")->get();
            foreach ($applications as $app) {
                $formData = $app->form_data ?? [];
                $formResponses = $formData["formResponses"] ?? [];
                $natId = $formResponses["idNumber"] ?? ($formResponses["nationalIdNumber"] ?? null);

                if ($natId && strtoupper(str_replace([" ", "-"], "", $natId)) === $reference) {
                    $application = $app;
                    break;
                }
            }
        }

        if (!$application) {
            return response()->json(["error" => "Delivery not found. Please check your reference number or National ID."], 404);
        }

        $formData = $application->form_data ?? [];
        $formResponses = $formData["formResponses"] ?? [];

        // Get customer information
        $firstName = $formResponses["firstName"] ?? "";
        $lastName = $formResponses["lastName"] ?? ($formResponses["surname"] ?? "");
        $customerName = trim($firstName . " " . $lastName) ?: "N/A";

        $product = $formData["business"] ?? "N/A";

        // Get delivery tracking information
        $deliveryTracking = $application->delivery;

        // Determine status and depot
        $status = $deliveryTracking ? $deliveryTracking->status : "processing";
        $depot = $deliveryTracking ? ($deliveryTracking->delivery_depot ?? $deliveryTracking->gain_depot_location ?? "Not yet assigned") : "Not yet assigned";

        // Calculate estimated delivery date
        $estimatedDelivery = null;
        if ($deliveryTracking && $deliveryTracking->estimated_delivery_date) {
            $estimatedDelivery = Carbon::parse($deliveryTracking->estimated_delivery_date)->format("F j, Y");
        } else {
            // Default to 5-7 business days from now if not set
            $estimatedDelivery = Carbon::now()->addDays(7)->format("F j, Y");
        }

        // Determine tracking number (Swift or Gain voucher)
        $trackingNumber = null;
        $trackingType = null;

        if ($deliveryTracking) {
            if ($deliveryTracking->post_office_tracking_number) {
                $trackingNumber = $deliveryTracking->post_office_tracking_number;
                $trackingType = "Zimpost Tracking Number";
            } elseif ($deliveryTracking->gain_voucher_number) {
                $trackingNumber = $deliveryTracking->gain_voucher_number;
                $trackingType = "Gain Voucher Number";
            } elseif ($deliveryTracking->outlet_voucher_number) {
                $trackingNumber = $deliveryTracking->outlet_voucher_number;
                $trackingType = "Outlet Voucher Number";
            }
        }

        if (!$trackingNumber) {
            $trackingNumber = "Not yet assigned";
            $trackingType = "Tracking Number";
        }

        $live = $this->fetchZimPostLive($deliveryTracking, $application);

        return response()->json([
            "sessionId" => $application->session_id,
            "customerName" => $customerName,
            "product" => $product,
            "status" => $status,
            "depot" => $depot,
            "estimatedDelivery" => $estimatedDelivery,
            "trackingNumber" => $trackingNumber,
            "trackingType" => $trackingType,
            "purchaseType" => $application->payment_type ?? "loan",
            "paymentPaid" => (bool) $application->deposit_paid,
            "deliveredAt" => $deliveryTracking ? $deliveryTracking->delivered_at : null,
            "live" => $live,
        ]);
    }

    /**
     * Fetch live ZimPost status + tracking events for this delivery, if it's a ZimPost shipment.
     * Returns null when the delivery is not ZimPost-linked. On API failure returns a structured
     * `{ status: 'unavailable' }` marker so the front-end can show a friendly fallback.
     */
    protected function fetchZimPostLive($deliveryTracking, ApplicationState $application): ?array
    {
        if (! $deliveryTracking) {
            return null;
        }

        $lookupId = $deliveryTracking->zimpost_delivery_id
            ?? $deliveryTracking->zimpost_tracking_number
            ?? null;

        if (! $lookupId && $deliveryTracking->post_office_tracking_number
            && $this->zimPost->looksLikeZimPostTracking($deliveryTracking->post_office_tracking_number)
        ) {
            $lookupId = $deliveryTracking->post_office_tracking_number;
        }

        if (! $lookupId) {
            $reference = $application->reference_code;
            if (! $reference) {
                return null;
            }
            try {
                $match = $this->zimPost->findByReference($reference);
            } catch (ZimPostApiException $e) {
                return ['status' => 'unavailable', 'reason' => 'api_error'];
            }
            if (! $match) {
                return null;
            }
            $lookupId = $match['id'] ?? ($match['tracking_number'] ?? null);
            if ($lookupId) {
                $deliveryTracking->forceFill([
                    'zimpost_delivery_id' => $match['id'] ?? null,
                    'zimpost_tracking_number' => $match['tracking_number'] ?? null,
                    'zimpost_last_synced_at' => now(),
                    'zimpost_snapshot' => $match,
                ])->save();
            }
        }

        if (! $lookupId) {
            return null;
        }

        try {
            $detail = $this->zimPost->getDelivery($lookupId);
        } catch (ZimPostApiException $e) {
            Log::warning('ZimPost detail fetch failed for public tracker', [
                'lookup_id' => $lookupId,
                'error_code' => $e->errorCode,
            ]);
            $snapshot = $deliveryTracking->zimpost_snapshot;
            if (is_array($snapshot)) {
                return $this->shapeLiveResponse($snapshot, fromCache: true);
            }
            return ['status' => 'unavailable', 'reason' => 'api_error'];
        }

        $deliveryTracking->forceFill([
            'zimpost_delivery_id' => $detail['id'] ?? $deliveryTracking->zimpost_delivery_id,
            'zimpost_tracking_number' => $detail['tracking_number'] ?? $deliveryTracking->zimpost_tracking_number,
            'zimpost_last_synced_at' => now(),
            'zimpost_snapshot' => $detail,
        ])->save();

        return $this->shapeLiveResponse($detail);
    }

    /**
     * Shape the ZimPost detail payload into the slim structure the front-end expects.
     */
    protected function shapeLiveResponse(array $detail, bool $fromCache = false): array
    {
        $driver = $detail['driver'] ?? [];
        $events = $detail['tracking_events'] ?? ($detail['events'] ?? []);

        return [
            'status' => $detail['status'] ?? null,
            'trackingNumber' => $detail['tracking_number'] ?? null,
            'amountUsd' => $detail['amount_usd'] ?? null,
            'distanceKm' => $detail['distance_km'] ?? null,
            'durationMinutes' => $detail['duration_minutes'] ?? null,
            'vehicleType' => $detail['vehicle_type'] ?? null,
            'createdAt' => $detail['created_at'] ?? null,
            'driver' => $driver ? [
                'name' => $driver['name'] ?? null,
                'phone' => $driver['phone'] ?? null,
                'vehicleRegistration' => $driver['vehicle_registration'] ?? ($driver['vehicle_reg'] ?? null),
            ] : null,
            'events' => array_map(function ($e) {
                return [
                    'status' => $e['status'] ?? ($e['event'] ?? null),
                    'at' => $e['at'] ?? ($e['timestamp'] ?? ($e['created_at'] ?? null)),
                    'note' => $e['note'] ?? ($e['description'] ?? null),
                    'location' => $e['location'] ?? null,
                ];
            }, is_array($events) ? $events : []),
            'fromCache' => $fromCache,
        ];
    }
}

