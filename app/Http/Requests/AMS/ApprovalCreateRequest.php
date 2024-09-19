<?php

namespace App\Http\Requests\AMS;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'det.*.amsmd_username' => 'required',
            'det.*.amsmd_order' => 'required',
            'det.*.amsmd_reqaprv' => 'required',
        ];
    }
}
