<?php

namespace App\Livewire\QrCodes;

use App\Enums\Feature;
use App\Models\QrCode;
use App\Services\QrCodeGeneratorService;
use Livewire\Component;
use Livewire\WithPagination;

class QrCodeIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public string $filterCategory = '';
    public ?int $viewingQrId = null;
    public ?int $downloadingQrId = null;

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $qr = auth()->user()->qrCodes()->findOrFail($id);
        $this->authorize('delete', $qr);
        $qr->delete();
        session()->flash('status', __('qr.deleted'));
    }

    public function toggleStatus(int $id): void
    {
        $qr = auth()->user()->qrCodes()->findOrFail($id);
        $qr->update(['status' => $qr->status === 'active' ? 'paused' : 'active']);

        if ($qr->shortLink) {
            $qr->shortLink->update(['is_active' => $qr->status === 'active']);
        }
    }

    public function openDownload(int $id): void
    {
        $qr = auth()->user()->qrCodes()->findOrFail($id);
        $this->authorize('download', $qr);
        $this->downloadingQrId = $qr->id;
    }

    public function closeDownload(): void
    {
        $this->downloadingQrId = null;
    }

    public function download(int $id, string $format): mixed
    {
        $user = auth()->user();
        $qr = $user->qrCodes()->with('design', 'shortLink')->findOrFail($id);
        $this->authorize('download', $qr);

        $feature = match ($format) {
            'png' => Feature::ExportPng,
            'jpg' => Feature::ExportJpg,
            'svg' => Feature::ExportSvg,
            'eps' => Feature::ExportEps,
            default => null,
        };

        if (! $feature || ! $user->hasFeature($feature)) {
            session()->flash('error', __('qr.format_not_available', ['format' => strtoupper($format)]));

            return null;
        }

        $generator = app(QrCodeGeneratorService::class);
        $filename = str($qr->name)->slug();

        [$content, $extension, $mime] = match ($format) {
            'png' => [$generator->generatePng($qr, 1000), 'png', 'image/png'],
            'jpg' => [$generator->generateJpg($qr, 1000), 'jpg', 'image/jpeg'],
            'svg' => [$generator->generateSvg($qr), 'svg', 'image/svg+xml'],
            'eps' => [$generator->generateEps($qr, 1000), 'eps', 'application/postscript'],
            default => throw new \InvalidArgumentException("Unknown format: {$format}"),
        };

        $this->closeDownload();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, "{$filename}.{$extension}", ['Content-Type' => $mime]);
    }

    public function view(int $id): void
    {
        $qr = auth()->user()->qrCodes()->findOrFail($id);
        $this->authorize('view', $qr);
        $this->viewingQrId = $qr->id;
    }

    public function closeView(): void
    {
        $this->viewingQrId = null;
    }

    public function render()
    {
        $query = auth()->user()->qrCodes()
            ->with('design', 'shortLink', 'category')
            ->latest();

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        return view('livewire.qr-codes.qr-code-index', [
            'qrCodes' => $query->paginate(12),
            'categories' => auth()->user()->categories()->orderBy('name')->get(),
            'viewingQr' => $this->viewingQrId
                ? auth()->user()->qrCodes()->find($this->viewingQrId)
                : null,
            'downloadingQr' => $this->downloadingQrId
                ? auth()->user()->qrCodes()->find($this->downloadingQrId)
                : null,
            'downloadFormats' => [
                ['id' => 'png', 'label' => 'PNG', 'feature' => Feature::ExportPng, 'icon' => 'fa-solid fa-image'],
                ['id' => 'jpg', 'label' => 'JPG', 'feature' => Feature::ExportJpg, 'icon' => 'fa-solid fa-image'],
                ['id' => 'svg', 'label' => 'SVG', 'feature' => Feature::ExportSvg, 'icon' => 'fa-solid fa-bezier-curve'],
                ['id' => 'eps', 'label' => 'EPS', 'feature' => Feature::ExportEps, 'icon' => 'fa-solid fa-vector-square'],
            ],
        ])->layout('layouts.app', ['title' => __('qr.my_qr_codes')]);
    }
}
