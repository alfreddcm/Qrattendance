<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'A file is required for import.',
            'file.file' => 'The uploaded file must be a valid file.',
            'file.mimes' => 'The file must be a CSV or Excel file.',
            'file.max' => 'The file size must not exceed 2MB.',
        ];
    }
}