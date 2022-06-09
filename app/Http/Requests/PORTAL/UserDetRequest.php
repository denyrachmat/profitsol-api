<?php

namespace App\Http\Requests\PORTAL;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserDetRequest extends FormRequest
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
                'firstName' => 'required',
                'lastName' => 'required',
                Rule::unique('users')->ignore($username)
            ];
        } else {
            return [
                'firstName' => 'required',
                'lastName' => 'required'
            ];
        }
    }
}
