<?php

namespace App\Http\Requests\DMS;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRootStoreRequest extends FormRequest
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
            'ddrm_name' => 'required',
            "ddrm_driver" => 'required',
            "ddrm_root" => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'ddrm_name.required' => 'Driver name is required !',
            'ddrm_driver.required' => 'Configuration name is required !',
            'ddrm_root.unique' => 'Configuration value is required !',
        ];
    }
}
