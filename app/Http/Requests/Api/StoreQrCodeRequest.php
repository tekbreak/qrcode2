<?php

namespace App\Http\Requests\Api;

use App\Enums\QrCodeType;
use App\Rules\SafeDestinationUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQrCodeRequest extends FormRequest
{
    /**
     * Design keys the API is allowed to write. logo_path is deliberately absent:
     * a logo reference may only ever be produced by an upload or by selecting a
     * bundled icon, never named directly by a client.
     */
    public const DESIGN_KEYS = [
        'fg_color', 'bg_color', 'gradient', 'dot_style', 'eye_style',
        'eye_frame_style', 'eye_ball_style', 'frame_style', 'frame_text',
        'logo_match_fg_color', 'template_id',
    ];

    public function authorize(): bool
    {
        return true;   // route middleware and policies handle authorization
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column(QrCodeType::cases(), 'value'))],
            'is_dynamic' => ['boolean'],

            'content_data' => ['required', 'array'],
            'content_data.url' => ['nullable', 'string', 'max:2048', new SafeDestinationUrl],
            'content_data.file_url' => ['nullable', 'string', 'max:2048', new SafeDestinationUrl],
            'content_data.networks' => ['nullable', 'array', 'max:20'],
            'content_data.networks.*.platform' => ['required_with:content_data.networks', 'string', 'max:50'],
            'content_data.networks.*.identifier' => ['nullable', 'string', 'max:300'],
            'content_data.networks.*.url' => ['required_with:content_data.networks', 'string', 'max:2048', new SafeDestinationUrl],

            'design' => ['nullable', 'array'],
            'design.fg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'design.bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'design.dot_style' => ['nullable', 'string', Rule::in(array_keys(config('qr_shapes.body', [])))],
            'design.eye_style' => ['nullable', 'string', Rule::in(array_keys(config('qr_shapes.eye_frame', [])))],
            'design.eye_frame_style' => ['nullable', 'string', Rule::in(array_keys(config('qr_shapes.eye_frame', [])))],
            'design.eye_ball_style' => ['nullable', 'string', Rule::in(array_keys(config('qr_shapes.eye_ball', [])))],
            'design.frame_style' => ['nullable', 'string', 'max:50'],
            'design.frame_text' => ['nullable', 'string', 'max:255'],
            'design.logo_match_fg_color' => ['nullable', 'boolean'],
            'design.template_id' => ['nullable', 'string', 'max:50'],
            'design.logo_path' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function designAttributes(): array
    {
        return array_intersect_key(
            $this->validated()['design'] ?? [],
            array_flip(self::DESIGN_KEYS),
        );
    }
}
