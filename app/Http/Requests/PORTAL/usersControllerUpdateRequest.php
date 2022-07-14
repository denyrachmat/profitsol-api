<?php

namespace App\Http\Requests\PORTAL;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class usersControllerUpdateRequest extends FormRequest
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
        $username = $this->route()->parameter('username');

        return [
            'pud_first_name' => 'required',
            'pud_last_name' => 'required',
            Rule::unique('users')->ignore($username)
        ];
    }
}
