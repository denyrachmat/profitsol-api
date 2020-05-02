<?php

namespace App\Http\Requests\DMS\Core;

use Illuminate\Foundation\Http\FormRequest;

class CreateNewFolderRequest extends FormRequest
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
            'folder_name' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'folder_name.required' => 'Folder name must not empty !!'
        ];
    }
}
