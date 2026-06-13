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

    public function downloadPng(int $id): mixed
    {
        $qr = auth()->user()->qrCodes()->with('design', 'shortLink')->findOrFail($id);
        $generator = app(QrCodeGeneratorService::class);
        $png = $generator->generatePng($qr, 1000);

        return response()->streamDownload(function () use ($png) {
            echo $png;
        }, str($qr->name)->slug() . '.png', ['Content-Type' => 'image/png']);
    }

    public function downloadSvg(int $id): mixed
    {
        $user = auth()->user();

        if (! $user->hasFeature(Feature::ExportSvg)) {
            session()->flash('error', __('qr.svg_not_available'));

            return null;
        }

        $qr = $user->qrCodes()->with('design', 'shortLink')->findOrFail($id);
        $generator = app(QrCodeGeneratorService::class);
        $svg = $generator->generateSvg($qr);

        return response()->streamDownload(function () use ($svg) {
            echo $svg;
        }, str($qr->name)->slug() . '.svg', ['Content-Type' => 'image/svg+xml']);
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
        ])->layout('layouts.app', ['title' => __('qr.my_qr_codes')]);
    }
}
