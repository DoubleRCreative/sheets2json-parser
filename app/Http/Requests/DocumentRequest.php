<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class DocumentRequest extends FormRequest
{
    protected $urlRegex = "regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w .:!$-]*)*\/?(\?[\w\-._~%!$&'()*+,;=:@\/?]*)?(#[\w\-._~%!$&'()*+,;=:@\/?]*)?$/";
    protected $rangeRegex = "regex:/^\d+,\d+$/";

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => 'required|string|' . $this->urlRegex,
            'headers' => 'boolean|nullable',
            'sheet' => 'string|nullable',
            'range' => 'string|nullable|' . $this->rangeRegex,
            'skip_empty' => 'boolean',
            'columns' => 'string|nullable',
            'offset' => 'integer|min:0|nullable',
            'steam' => 'boolean|nullable'
            //'sort' => 'string|nullable'
        ];
    }

    public function messages()
    {
        $messages = parent::messages();
        $messages['range.regex'] = "Invalid range value";
        return $messages;
    }

    public function getSizeLimit()
    {
        return $this->header('x-document-size-limit');
    }

    public function getRecordLimit()
    {
        return $this->header('x-document-record-limit');
    }
}
