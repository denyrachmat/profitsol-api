<?php

namespace App\Http\Requests\PORTAL;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        if (in_array($this->method(), ['PUT', 'PATCH'])) {
            $username = $this->route()->parameter('username');

            return [
                'rm_role_name' => 'required',
                'rm_role_desc' => 'required',
                Rule::unique('users')->ignore($username)
            ];
        } else {
            return [
                'rm_role_name' => 'required|unique:portal_role_mstr',
                'rm_role_desc' => 'required',
            ];
        }
    }
}
