<?php

namespace Fjord\Form\Requests;

use Fjord\Form\Requests\Traits\FormHasPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class FormReadRequest extends FormRequest
{
    use Traits\AuthorizeController;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return $this->authorizeController($request, 'read');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
