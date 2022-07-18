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
                'form.pud_first_name' => 'required',
                'form.pud_last_name' => 'required',
                Rule::unique('users')->ignore($username)
            ];
        } else {
            return [
                'form.pud_first_name' => 'required',
                'form.pud_last_name' => 'required'
            ];
        }
    }
}
