<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreQrCodeRequest;
use App\Http\Requests\Api\UpdateQrCodeRequest;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Services\QrCodeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QrCodeApiController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->hasFeature(Feature::ApiAccess)) {
            return response()->json(['error' => 'API access is not available on your plan.'], 403);
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $qrCodes = $request->user()->qrCodes()
            ->with('design', 'shortLink')
            ->latest()
            ->paginate($perPage);

        return response()->json($qrCodes);
    }

    public function show(Request $request, QrCode $qrCode)
    {
        if (! $request->user()->hasFeature(Feature::ApiAccess)) {
            return response()->json(['error' => 'API access is not available on your plan.'], 403);
        }

        $this->authorize('view', $qrCode);

        return response()->json($qrCode->load('design', 'shortLink'));
    }

    public function store(StoreQrCodeRequest $request)
    {
        if (! $request->user()->hasFeature(Feature::ApiAccess)) {
            return response()->json(['error' => 'API access is not available on your plan.'], 403);
        }

        $user = $request->user();
        $type = $request->input('type');
        $isDynamic = $request->boolean('is_dynamic', true);

        if ($type === 'pdf') {
            $isDynamic = true;
        }

        if (! $user->canCreateQrCode(isDynamic: $isDynamic)) {
            return response()->json(['error' => 'Plan QR code limit reached.'], 403);
        }

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'is_dynamic' => $isDynamic,
            'content_data' => $request->input('content_data'),
        ]);

        $designData = $request->designAttributes();
        $qrCode->design()->create($designData + [
            'fg_color' => '#000000',
            'bg_color' => '#FFFFFF',
            'dot_style' => 'square',
            'eye_style' => 'square',
            'eye_frame_style' => 'square',
            'eye_ball_style' => 'square',
        ]);

        if ($isDynamic) {
            $contentData = $qrCode->content_data ?? [];

            ShortLink::create([
                'qr_code_id' => $qrCode->id,
                'slug' => ShortLink::generateSlug(),
                'destination_url' => $contentData['url'] ?? $contentData['file_url'] ?? '',
                'is_active' => true,
            ]);
        }

        return response()->json($qrCode->load('design', 'shortLink'), 201);
    }

    public function update(UpdateQrCodeRequest $request, QrCode $qrCode)
    {
        if (! $request->user()->hasFeature(Feature::ApiAccess)) {
            return response()->json(['error' => 'API access is not available on your plan.'], 403);
        }

        $this->authorize('update', $qrCode);

        $validated = $request->validated();

        $qrCode->update(array_intersect_key($validated, array_flip(['name', 'content_data'])));

        if ($request->has('design')) {
            $qrCode->design()->updateOrCreate(
                ['qr_code_id' => $qrCode->id],
                $request->designAttributes()
            );
        }

        if ($qrCode->is_dynamic && $qrCode->shortLink) {
            $contentData = $validated['content_data'] ?? [];
            $destinationUrl = $contentData['url'] ?? $contentData['file_url'] ?? null;

            if ($destinationUrl !== null) {
                $qrCode->shortLink->update(['destination_url' => $destinationUrl]);
            }
        }

        return response()->json($qrCode->fresh()->load('design', 'shortLink'));
    }

    public function destroy(Request $request, QrCode $qrCode)
    {
        if (! $request->user()->hasFeature(Feature::ApiAccess)) {
            return response()->json(['error' => 'API access is not available on your plan.'], 403);
        }

        $this->authorize('delete', $qrCode);

        $qrCode->delete();

        return response()->json(['message' => 'QR code deleted.']);
    }

    public function download(Request $request, QrCode $qrCode)
    {
        if (! $request->user()->hasFeature(Feature::ApiAccess)) {
            return response()->json(['error' => 'API access is not available on your plan.'], 403);
        }

        $this->authorize('download', $qrCode);

        $validated = $request->validate([
            'format' => ['nullable', Rule::in(['png', 'svg'])],
            'size' => ['nullable', 'integer', 'min:100', 'max:2000'],
        ]);

        $format = $validated['format'] ?? 'png';
        $generator = app(QrCodeGeneratorService::class);

        if ($format === 'svg') {
            if (! $request->user()->hasFeature(Feature::ExportSvg)) {
                return response()->json(['error' => 'SVG export is not available on your plan.'], 403);
            }

            $svg = $generator->generateSvg($qrCode);

            return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
        }

        $png = $generator->generatePng($qrCode, (int) ($validated['size'] ?? 1000));

        return response($png, 200, ['Content-Type' => 'image/png']);
    }
}
