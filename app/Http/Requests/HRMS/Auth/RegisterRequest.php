<?php

namespace App\Http\Requests\HRMS\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|string|max:255|unique:sqlsrv_hrms.hrms_user_mstr',
            'email' => 'required|string|max:255|unique:sqlsrv_hrms.hrms_user_mstr',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
