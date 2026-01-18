<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use App\Models\CashPurchase;
use App\Services\ReferenceCodeService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DeliveryTrackingController extends Controller
{
    protected $referenceCodeService;

    public function __construct(ReferenceCodeService $referenceCodeService)
    {
        $this->referenceCodeService = $referenceCodeService;
    }

    public function getStatus(string $reference): JsonResponse
    {
        $reference = strtoupper(str_replace([" ", "-"], "", trim($reference)));

        // First, try to find a cash purchase by purchase number or national ID
        $cashPurchase = CashPurchase::where('purchase_number', $reference)
            ->orWhere('national_id', $reference)
            ->first();

        if ($cashPurchase) {
            return $this->getCashPurchaseStatus($cashPurchase);
        }

        // If not found, search regular loan applications
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

        return response()->json([
            "sessionId" => $application->session_id,
            "customerName" => $customerName,
            "product" => $product,
            "status" => $status,
            "depot" => $depot,
            "estimatedDelivery" => $estimatedDelivery,
            "trackingNumber" => $trackingNumber,
            "trackingType" => $trackingType,
            "purchaseType" => "loan", // Indicate this is a loan application
            "deliveredAt" => $deliveryTracking ? $deliveryTracking->delivered_at : null,
        ]);
    }

    /**
     * Get cash purchase delivery status
     */
    private function getCashPurchaseStatus(CashPurchase $purchase): JsonResponse
    {
        // Calculate estimated delivery date
        $estimatedDelivery = null;
        if ($purchase->dispatched_at) {
            $estimatedDelivery = Carbon::parse($purchase->dispatched_at)->addDays(3)->format("F j, Y");
        } elseif ($purchase->created_at) {
            // Default to 5-7 business days from purchase date
            $estimatedDelivery = Carbon::parse($purchase->created_at)->addDays(7)->format("F j, Y");
        }

        // Determine depot information
        $depot = "Not yet assigned";
        if ($purchase->delivery_type === 'gain_outlet' && $purchase->depot_name) {
            $depot = $purchase->depot_name . ($purchase->region ? " ({$purchase->region})" : "");
        } elseif ($purchase->delivery_type === 'zimpost' && $purchase->city) {
            $depot = "Zimpost Delivery - {$purchase->city}";
        }

        // Determine tracking number
        $trackingNumber = "Not yet assigned";
        $trackingType = $purchase->delivery_type === 'zimpost' ? "Zimpost Tracking Number" : "Depot Collection";

        return response()->json([
            "sessionId" => $purchase->purchase_number,
            "customerName" => $purchase->full_name,
            "product" => $purchase->product_name,
            "status" => $purchase->status,
            "depot" => $depot,
            "estimatedDelivery" => $estimatedDelivery,
            "trackingNumber" => $trackingNumber,
            "trackingType" => $trackingType,
            "purchaseType" => "cash", // Indicate this is a cash purchase
            "paymentStatus" => $purchase->payment_status,
            "amountPaid" => '$' . number_format($purchase->amount_paid, 2),
            "deliveredAt" => $purchase->status === 'delivered' ? ($purchase->updated_at ?? $purchase->created_at) : null,
        ]);
    }
}
