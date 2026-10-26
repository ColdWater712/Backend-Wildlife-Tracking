<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGpsReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'       => 'required|exists:tracking_devices,device_id',
            'latitude'        => 'required|numeric|between:-90,90',
            'longitude'       => 'required|numeric|between:-180,180',
            'recorded_at'     => 'required|date_format:Y-m-d H:i:s',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|numeric',
        ];
    }
}