<?php

namespace App\Http\Requests\PORTAL;

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
            'username' => 'required|string|max:255|unique:psi_portal_user,username',
            'email' => 'required|string|max:255|unique:psi_portal_user,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
