<?php

namespace App\Livewire\QrCodes;

use App\Enums\Feature;
use App\Jobs\BulkGenerateQrCodesJob;
use Livewire\Component;
use Livewire\WithFileUploads;

class BulkGenerator extends Component
{
    use WithFileUploads;

    public $csvFile = null;
    public array $parsedItems = [];
    public bool $processing = false;

    public function mount(): void
    {
        if (! auth()->user()->hasFeature(Feature::BulkOperations)) {
            abort(403, __('qr.bulk_not_available'));
        }
    }

    public function parseCsv()
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:5120']);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        $this->parsedItems = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2) {
                $this->parsedItems[] = [
                    'name' => $row[0] ?? '',
                    'url' => $row[1] ?? '',
                ];
            }
        }
        fclose($handle);

        if (count($this->parsedItems) > 500) {
            $this->parsedItems = array_slice($this->parsedItems, 0, 500);
            session()->flash('warning', 'Limited to 500 items per batch.');
        }
    }

    public function generate()
    {
        if (empty($this->parsedItems)) {
            return;
        }

        $items = array_map(fn ($item) => [
            'name' => $item['name'],
            'type' => 'url',
            'is_dynamic' => true,
            'content_data' => ['url' => $item['url']],
        ], $this->parsedItems);

        BulkGenerateQrCodesJob::dispatch(auth()->id(), $items);

        $this->processing = true;
        session()->flash('status', 'Bulk generation started! Your QR codes will be ready shortly.');
    }

    public function render()
    {
        return view('livewire.qr-codes.bulk-generator')
            ->layout('layouts.app', ['title' => 'Bulk Generate']);
    }
}
