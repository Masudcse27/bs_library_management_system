<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingsResource;
use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private function getSetting()
    {
        return Settings::firstOrFail(); // There should always be one setting
    }

    /**
     * @OA\Get(
     *     path="/api/settings/get-settings",
     *     summary="Get system settings",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Settings")
     *         )
     *     )
     * )
     */
    public function get_setting()
    {
        $settings = Settings::firstOrFail();
        return response()->json(new SettingsResource($settings));
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/borrow-day-limit",
     *     summary="Update borrow day limit",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"max_borrow_duration"},
     *             @OA\Property(property="max_borrow_duration", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Borrow duration updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Settings")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function borrow_day_limit(Request $request)
    {
        $request->validate(['value' => 'required|integer|min:1']);
        $setting = $this->getSetting();
        $setting->update(['max_borrow_duration' => $request->value]);

        return response()->json([
            'message' => 'Borrow duration updated successfully',
            'data' => new SettingsResource($setting)
        ], 202);
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/borrow-extend-limit",
     *     summary="Update borrow extension limit",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"max_extension_limit"},
     *             @OA\Property(property="max_extension_limit", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Borrow extension count updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Settings")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function borrow_extend_limit(Request $request)
    {
        $request->validate(['value' => 'required|integer|min:0']);
        $setting = $this->getSetting();
        $setting->update(['max_extension_limit' => $request->value]);

        return response()->json([
            'message' => 'Borrow extension count updated successfully',
            'data' => new SettingsResource($setting)
        ], 202);
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/borrow-limit",
     *     summary="Update max borrow limit",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"max_borrow_limit"},
     *             @OA\Property(property="max_borrow_limit", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Borrow limit updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Settings")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function borrow_limit(Request $request)
    {
        $request->validate(['value' => 'required|integer|min:1']);
        $setting = $this->getSetting();
        $setting->update(['max_borrow_limit' => $request->value]);

        return response()->json([
            'message' => 'Borrow limit updated successfully',
            'data' => new SettingsResource($setting)
        ], 202);
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/booking-duration",
     *     summary="Update max booking duration",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"max_booking_duration"},
     *             @OA\Property(property="max_booking_duration", type="integer", example=7)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Booking duration updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Settings")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function booking_duration(Request $request)
    {
        $request->validate(['value' => 'required|integer|min:1']);
        $setting = $this->getSetting();
        $setting->update(['max_booking_duration' => $request->value]);

        return response()->json([
            'message' => 'Booking duration updated successfully',
            'data' => new SettingsResource($setting)
        ], 202);
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/booking-days-limit",
     *     summary="Update max booking limit",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"max_booking_limit"},
     *             @OA\Property(property="max_booking_limit", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Booking limit updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Settings")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function booking_days_limit(Request $request)
    {
        $request->validate(['value' => 'required|integer|min:1']);
        $setting = $this->getSetting();
        $setting->update(['max_booking_limit' => $request->value]);

        return response()->json([
            'message' => 'Booking limit updated successfully',
            'data' => $setting
        ], 202);
    }
}
