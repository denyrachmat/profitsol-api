<?php

namespace App\Http\Requests\MRS;

use Illuminate\Foundation\Http\FormRequest;

class DBConnectionCreateRequest extends FormRequest
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
            'p_u_username.required' => 'Username needed !',
            'mdm_host.required' => 'Connection Host required !',
            'mdm_host.unique' => 'This host already created !',
            'mdm_name.required' => 'Connection name required !',
            'mdm_username.required' => 'Connection Username required !',
            'mdm_password.required' => 'Connection Password required !'
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
            'p_u_username' => 'required',
            'mdm_host' => 'required|unique:sqlsrv_mrs.mrs_db_mstr',
            'mdm_name' => 'required',
            'mdm_username' => 'required',
            'mdm_password' => 'required',
        ];
    }
}
