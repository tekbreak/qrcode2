<?php

namespace App\Livewire\QrCodes;

use App\Enums\Feature;
use App\Jobs\BulkGenerateQrCodesJob;
use App\Support\Url;
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
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $name = trim((string) ($row[0] ?? ''));
            $url = trim((string) ($row[1] ?? ''));

            // Every row becomes a public short link, so the same destination
            // rules apply here as in the builder.
            if ($name === '' || mb_strlen($name) > 255 || ! Url::isSafe($url)) {
                $skipped++;

                continue;
            }

            $this->parsedItems[] = ['name' => $name, 'url' => $url];
        }
        fclose($handle);

        if (count($this->parsedItems) > 500) {
            $this->parsedItems = array_slice($this->parsedItems, 0, 500);
            session()->flash('warning', 'Limited to 500 items per batch.');
        }

        if ($skipped > 0) {
            session()->flash('warning', trim(
                ($skipped === 1 ? '1 row was skipped' : "{$skipped} rows were skipped")
                . ' because the name was missing or the URL was not a valid http/https address.'
            ));
        }
    }

    public function generate()
    {
        if (empty($this->parsedItems)) {
            return;
        }

        // parsedItems is a public property, so re-check it here rather than
        // trusting whatever survived the round trip from parseCsv().
        $this->parsedItems = array_values(array_filter(
            $this->parsedItems,
            fn ($item) => is_array($item)
                && filled($item['name'] ?? null)
                && Url::isSafe($item['url'] ?? null),
        ));

        if (empty($this->parsedItems)) {
            $this->addError('csvFile', __('qr.invalid_destination'));

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
