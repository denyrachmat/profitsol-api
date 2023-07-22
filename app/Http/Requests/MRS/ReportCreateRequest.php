<?php

namespace App\Http\Requests\MRS;

use Illuminate\Foundation\Http\FormRequest;

class ReportCreateRequest extends FormRequest
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
    
    public function messages() {
        return [
            'header.p_u_username.required' => 'Username creator required!',
            'header.mdm_id.required' => 'Connection ID required!',
            'header.mrm_name.required' => 'Report Name required!',
            'header.mrm_db.required' => 'DB Name required!',
            'header.mrm_table.required' => 'table Name required!',
            'header.mrm_query.required' => 'Query required!',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'header.p_u_username' => 'required',
            'header.mdm_id' => 'required',
            'header.mrm_name' => 'required',
            'header.mrm_db' => 'required',
            'header.mrm_table' => 'required',
            'header.mrm_query' => 'required',
        ];
    }
}
