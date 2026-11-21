<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateIncidentReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'incident_type' => 'sometimes|in:observasi,konflik,perambahan,device_alert,system',
            'animal_code' => 'sometimes|nullable|string|max:50',
            'location' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:10|max:5000',
            'photo_url' => 'sometimes|nullable|url|max:500',
            'sync_status' => 'sometimes|in:pending_sync,synced,failed',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data' => null,
            'errors' => $validator->errors(),
        ], 422));
    }
}