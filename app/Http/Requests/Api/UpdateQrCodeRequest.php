<?php

namespace App\Http\Requests\Api;

class UpdateQrCodeRequest extends StoreQrCodeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['prohibited'],
            'content_data' => ['sometimes', 'required', 'array'],
        ]);
    }
}
