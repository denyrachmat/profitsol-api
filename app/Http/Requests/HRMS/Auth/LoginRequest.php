<?php

namespace App\Http\Requests\HRMS\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'username' => 'required|exists:sqlsrv_hrms.hrms_user_mstr,username,status,1',
            'password' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'username.exists' => 'Your username or password is wrong, or your account has not activated yet!!'
        ];
    }
}
