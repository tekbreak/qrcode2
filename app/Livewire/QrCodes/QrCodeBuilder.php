<?php

namespace App\Livewire\QrCodes;

use App\Enums\Feature;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Services\PaidActionService;
use App\Services\QrCodeGeneratorService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class QrCodeBuilder extends Component
{
    use WithFileUploads;

    public ?QrCode $qrCode = null;
    public bool $editing = false;

    /** Base64-encoded JSON of form state for shareable URLs */
    #[Url(as: 's', history: true)]
    public string $state = '';

    // Step tracking
    public int $step = 1;

    // QR Code fields
    public string $name = '';
    public string $type = 'url';
    public bool $isDynamic = false;

    // Content data (varies by type)
    public string $url = '';
    public string $text = '';
    // vCard
    public string $firstName = '';
    public string $lastName = '';
    public string $org = '';
    public string $title = '';
    public string $vcardPhone = '';
    public string $vcardEmail = '';
    public string $vcardUrl = '';
    public string $vcardAddress = '';
    // WiFi
    public string $ssid = '';
    public string $wifiPassword = '';
    public string $encryption = 'WPA';
    public bool $hidden = false;
    // Email
    public string $emailAddress = '';
    public string $emailSubject = '';
    public string $emailBody = '';
    // Phone
    public string $phone = '';
    // SMS
    public string $smsPhone = '';
    public string $smsMessage = '';
    // Geo
    public string $latitude = '';
    public string $longitude = '';
    // Event
    public string $eventTitle = '';
    public string $eventStart = '';
    public string $eventEnd = '';
    public string $eventLocation = '';
    public string $eventDescription = '';
    // Crypto
    public string $cryptoCurrency = 'bitcoin';
    public string $cryptoAddress = '';
    public string $cryptoAmount = '';
    // Social / AppStore / Menu
    public string $socialUrl = '';
    // PDF
    public $pdfFile = null;
    public ?string $existingFileUrl = null;

    // Link features
    public string $customSlug = '';
    public ?string $expiresAt = null;
    public ?int $maxScans = null;
    public string $linkPassword = '';

    // Design
    public string $fgColor = '#000000';
    public string $bgColor = '#FFFFFF';
    public string $dotStyle = 'square';
    public string $eyeStyle = 'square';
    public string $eyeFrameStyle = 'square';
    public string $eyeBallStyle = 'square';
    public bool $useGradient = false;
    public string $gradientColor1 = '#000000';
    public string $gradientColor2 = '#333333';
    public string $gradientType = 'linear';
    public string $frameStyle = '';
    public string $frameText = '';
    public $logo = null;
    public ?string $existingLogo = null;
    public ?string $selectedIcon = null;

    // Preview
    public ?string $preview = null;

    public function selectType(string $value): void
    {
        $this->type = $value;
    }

    public function selectIcon(?string $icon): void
    {
        $this->selectedIcon = $icon;
        $this->logo = null;
        $this->refreshPreview();
    }

    public function clearLogo(): void
    {
        $this->logo = null;
        $this->refreshPreview();
    }

    public function getAvailableIconsProperty(): array
    {
        return cache()->remember('qr_center_icon_list', 3600, function () {
            $path = public_path('icons/qr-center-icons');
            if (!is_dir($path)) {
                return config('icons.qr_center', []);
            }
            $icons = [];
            foreach (glob($path . '/*.svg') ?: [] as $file) {
                $icons[] = pathinfo($file, PATHINFO_FILENAME);
            }
            sort($icons);
            return $icons;
        });
    }

    public function mount(?QrCode $qrCode = null)
    {
        if ($qrCode?->exists) {
            $this->qrCode = $qrCode;
            $this->editing = true;
        }

        // Hydrate from URL state when present (shared link); otherwise from DB when editing
        if ($this->state !== '') {
            $this->hydrateFromState();
        } elseif ($qrCode?->exists) {
            $this->fillFromExisting($qrCode);
        }
    }

    /** Serialize form state to base64 JSON for URL sharing */
    protected function stateToPayload(): array
    {
        $truncate = fn (?string $s, int $max = 500) => $s === null || $s === '' ? '' : mb_substr($s, 0, $max);

        return [
            'step' => $this->step,
            'type' => $this->type,
            'name' => $this->name,
            'url' => $truncate($this->url, 800),
            'text' => $truncate($this->text, 1000),
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'org' => $this->org,
            'title' => $this->title,
            'vcardPhone' => $this->vcardPhone,
            'vcardEmail' => $this->vcardEmail,
            'vcardUrl' => $truncate($this->vcardUrl, 800),
            'vcardAddress' => $this->vcardAddress,
            'ssid' => $this->ssid,
            'wifiPassword' => $this->wifiPassword,
            'encryption' => $this->encryption,
            'hidden' => $this->hidden,
            'emailAddress' => $this->emailAddress,
            'emailSubject' => $truncate($this->emailSubject, 200),
            'emailBody' => $truncate($this->emailBody, 500),
            'phone' => $this->phone,
            'smsPhone' => $this->smsPhone,
            'smsMessage' => $truncate($this->smsMessage, 500),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'eventTitle' => $this->eventTitle,
            'eventStart' => $this->eventStart,
            'eventEnd' => $this->eventEnd,
            'eventLocation' => $this->eventLocation,
            'eventDescription' => $truncate($this->eventDescription, 500),
            'cryptoCurrency' => $this->cryptoCurrency,
            'cryptoAddress' => $this->cryptoAddress,
            'cryptoAmount' => $this->cryptoAmount,
            'socialUrl' => $truncate($this->socialUrl, 800),
            'customSlug' => $this->customSlug,
            'expiresAt' => $this->expiresAt,
            'maxScans' => $this->maxScans,
            'linkPassword' => $this->linkPassword,
            'fgColor' => $this->fgColor,
            'bgColor' => $this->bgColor,
            'dotStyle' => $this->dotStyle,
            'eyeStyle' => $this->eyeStyle,
            'eyeFrameStyle' => $this->eyeFrameStyle,
            'eyeBallStyle' => $this->eyeBallStyle,
            'useGradient' => $this->useGradient,
            'gradientColor1' => $this->gradientColor1,
            'gradientColor2' => $this->gradientColor2,
            'gradientType' => $this->gradientType,
            'frameStyle' => $this->frameStyle,
            'frameText' => $this->frameText,
            'selectedIcon' => $this->selectedIcon,
            'existingFileUrl' => $this->existingFileUrl,
            'existingLogo' => $this->existingLogo,
        ];
    }

    /** Hydrate component from base64 JSON state (shared URL) */
    protected function hydrateFromState(): void
    {
        try {
            $json = base64_decode($this->state, true);
            if ($json === false) {
                return;
            }
            $payload = json_decode($json, true);
            if (! is_array($payload)) {
                return;
            }

            $this->step = (int) ($payload['step'] ?? 1);
            $this->type = $payload['type'] ?? 'url';
            $this->name = $payload['name'] ?? '';
            $this->url = $payload['url'] ?? '';
            $this->text = $payload['text'] ?? '';
            $this->firstName = $payload['firstName'] ?? '';
            $this->lastName = $payload['lastName'] ?? '';
            $this->org = $payload['org'] ?? '';
            $this->title = $payload['title'] ?? '';
            $this->vcardPhone = $payload['vcardPhone'] ?? '';
            $this->vcardEmail = $payload['vcardEmail'] ?? '';
            $this->vcardUrl = $payload['vcardUrl'] ?? '';
            $this->vcardAddress = $payload['vcardAddress'] ?? '';
            $this->ssid = $payload['ssid'] ?? '';
            $this->wifiPassword = $payload['wifiPassword'] ?? '';
            $this->encryption = $payload['encryption'] ?? 'WPA';
            $this->hidden = (bool) ($payload['hidden'] ?? false);
            $this->emailAddress = $payload['emailAddress'] ?? '';
            $this->emailSubject = $payload['emailSubject'] ?? '';
            $this->emailBody = $payload['emailBody'] ?? '';
            $this->phone = $payload['phone'] ?? '';
            $this->smsPhone = $payload['smsPhone'] ?? '';
            $this->smsMessage = $payload['smsMessage'] ?? '';
            $this->latitude = $payload['latitude'] ?? '';
            $this->longitude = $payload['longitude'] ?? '';
            $this->eventTitle = $payload['eventTitle'] ?? '';
            $this->eventStart = $payload['eventStart'] ?? '';
            $this->eventEnd = $payload['eventEnd'] ?? '';
            $this->eventLocation = $payload['eventLocation'] ?? '';
            $this->eventDescription = $payload['eventDescription'] ?? '';
            $this->cryptoCurrency = $payload['cryptoCurrency'] ?? 'bitcoin';
            $this->cryptoAddress = $payload['cryptoAddress'] ?? '';
            $this->cryptoAmount = $payload['cryptoAmount'] ?? '';
            $this->socialUrl = $payload['socialUrl'] ?? '';
            $this->customSlug = $payload['customSlug'] ?? '';
            $this->expiresAt = $payload['expiresAt'] ?? null;
            $this->maxScans = isset($payload['maxScans']) ? (int) $payload['maxScans'] : null;
            $this->linkPassword = $payload['linkPassword'] ?? '';
            $this->fgColor = $payload['fgColor'] ?? '#000000';
            $this->bgColor = $payload['bgColor'] ?? '#FFFFFF';
            $bodyKeys = array_keys(config('qr_shapes.body', ['square' => []]));
            $frameKeys = array_keys(config('qr_shapes.eye_frame', ['square' => []]));
            $ballKeys = array_keys(config('qr_shapes.eye_ball', ['square' => []]));
            $this->dotStyle = in_array($payload['dotStyle'] ?? '', $bodyKeys, true) ? $payload['dotStyle'] : 'square';
            $this->eyeStyle = in_array($payload['eyeStyle'] ?? '', $frameKeys, true) ? $payload['eyeStyle'] : 'square';
            $this->eyeFrameStyle = in_array($payload['eyeFrameStyle'] ?? '', $frameKeys, true) ? $payload['eyeFrameStyle'] : 'square';
            $this->eyeBallStyle = in_array($payload['eyeBallStyle'] ?? '', $ballKeys, true) ? $payload['eyeBallStyle'] : 'square';
            $this->useGradient = (bool) ($payload['useGradient'] ?? false);
            $this->gradientColor1 = $payload['gradientColor1'] ?? '#000000';
            $this->gradientColor2 = $payload['gradientColor2'] ?? '#333333';
            $this->gradientType = $payload['gradientType'] ?? 'linear';
            $this->frameStyle = $payload['frameStyle'] ?? '';
            $this->frameText = $payload['frameText'] ?? '';
            $this->selectedIcon = $payload['selectedIcon'] ?? null;
            $this->existingFileUrl = $payload['existingFileUrl'] ?? null;
            $this->existingLogo = $payload['existingLogo'] ?? null;

            if ($this->step >= 2) {
                $this->generatePreview();
            }
        } catch (\Throwable $e) {
            logger()->warning('QR builder state hydration failed: ' . $e->getMessage());
        }
    }

    /** Sync current state to URL (called on form updates) */
    protected function syncStateToUrl(): void
    {
        $payload = $this->stateToPayload();
        $encoded = base64_encode(json_encode($payload));
        if ($encoded !== $this->state) {
            $this->state = $encoded;
        }
    }

    public function updated($propertyName): void
    {
        if ($propertyName === 'state') {
            return;
        }
        $this->syncStateToUrl();
    }

    protected function fillFromExisting(QrCode $qrCode): void
    {
        $this->name = $qrCode->name;
        $this->type = $qrCode->type->value;
        $this->isDynamic = $qrCode->is_dynamic;

        $data = $qrCode->content_data;
        match ($qrCode->type) {
            QrCodeType::Url => $this->url = $data['url'] ?? '',
            QrCodeType::Text => $this->text = $data['text'] ?? '',
            QrCodeType::VCard => $this->fillVCard($data),
            QrCodeType::Wifi => $this->fillWifi($data),
            QrCodeType::Email => $this->fillEmail($data),
            QrCodeType::Phone => $this->phone = $data['phone'] ?? '',
            QrCodeType::Sms => $this->fillSms($data),
            default => null,
        };

        if ($shortLink = $qrCode->shortLink) {
            $this->customSlug = $shortLink->slug;
            $this->expiresAt = $shortLink->expires_at?->format('Y-m-d\TH:i');
            $this->maxScans = $shortLink->max_scans;
        }

        if ($design = $qrCode->design) {
            $this->fgColor = $design->fg_color;
            $this->bgColor = $design->bg_color;
            $this->dotStyle = $design->dot_style;
            $this->eyeStyle = $design->eye_style ?? 'square';
            $this->eyeFrameStyle = $design->eye_frame_style ?? 'square';
            $this->eyeBallStyle = $design->eye_ball_style ?? 'square';
            $this->frameStyle = $design->frame_style ?? '';
            $this->frameText = $design->frame_text ?? '';
            $this->existingLogo = $design->logo_path;
            $this->selectedIcon = str_starts_with($design->logo_path ?? '', 'icons/')
                ? pathinfo($design->logo_path, PATHINFO_FILENAME)
                : null;
            if ($design->gradient) {
                $this->useGradient = true;
                $this->gradientColor1 = $design->gradient['color1'] ?? '#000000';
                $this->gradientColor2 = $design->gradient['color2'] ?? '#333333';
                $this->gradientType = $design->gradient['type'] ?? 'linear';
            }
        }
    }

    protected function fillVCard(array $d): void
    {
        $this->firstName = $d['first_name'] ?? '';
        $this->lastName = $d['last_name'] ?? '';
        $this->org = $d['org'] ?? '';
        $this->title = $d['title'] ?? '';
        $this->vcardPhone = $d['phone'] ?? '';
        $this->vcardEmail = $d['email'] ?? '';
        $this->vcardUrl = $d['url'] ?? '';
        $this->vcardAddress = $d['address'] ?? '';
    }

    protected function fillWifi(array $d): void
    {
        $this->ssid = $d['ssid'] ?? '';
        $this->wifiPassword = $d['password'] ?? '';
        $this->encryption = $d['encryption'] ?? 'WPA';
        $this->hidden = $d['hidden'] ?? false;
    }

    protected function fillEmail(array $d): void
    {
        $this->emailAddress = $d['email'] ?? '';
        $this->emailSubject = $d['subject'] ?? '';
        $this->emailBody = $d['body'] ?? '';
    }

    protected function fillSms(array $d): void
    {
        $this->smsPhone = $d['phone'] ?? '';
        $this->smsMessage = $d['message'] ?? '';
    }

    public function nextStep(): void
    {
        $this->validateStep();
        $this->step = min($this->step + 1, 3);

        if ($this->step >= 2) {
            $this->generatePreview();
        }

        $this->syncStateToUrl();
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
        $this->syncStateToUrl();
    }

    protected function validateStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string',
            ]);
            $this->validateContentByType();
        }
    }

    protected function validateContentByType(): void
    {
        $rules = match (QrCodeType::from($this->type)) {
            QrCodeType::Url => ['url' => 'required|url:http,https'],
            QrCodeType::Text => ['text' => 'required|string|max:2000'],
            QrCodeType::VCard => ['firstName' => 'required|string', 'lastName' => 'required|string'],
            QrCodeType::Wifi => ['ssid' => 'required|string'],
            QrCodeType::Email => ['emailAddress' => 'required|email'],
            QrCodeType::Phone => ['phone' => 'required|string'],
            QrCodeType::Sms => ['smsPhone' => 'required|string'],
            QrCodeType::Geo => ['latitude' => 'required|numeric', 'longitude' => 'required|numeric'],
            QrCodeType::Event => ['eventTitle' => 'required|string', 'eventStart' => 'required|string'],
            QrCodeType::Crypto => ['cryptoAddress' => 'required|string'],
            QrCodeType::AppStore, QrCodeType::Social, QrCodeType::Menu => ['socialUrl' => 'required|url:http,https'],
            QrCodeType::Pdf => $this->editing ? [] : ['pdfFile' => 'required|file|max:10240'],
            default => [],
        };

        $this->validate($rules);
    }

    protected function getContentData(): array
    {
        return match (QrCodeType::from($this->type)) {
            QrCodeType::Url => ['url' => $this->url],
            QrCodeType::Text => ['text' => $this->text],
            QrCodeType::VCard => [
                'first_name' => $this->firstName, 'last_name' => $this->lastName,
                'org' => $this->org, 'title' => $this->title,
                'phone' => $this->vcardPhone, 'email' => $this->vcardEmail,
                'url' => $this->vcardUrl, 'address' => $this->vcardAddress,
            ],
            QrCodeType::Wifi => [
                'ssid' => $this->ssid, 'password' => $this->wifiPassword,
                'encryption' => $this->encryption, 'hidden' => $this->hidden,
            ],
            QrCodeType::Email => [
                'email' => $this->emailAddress, 'subject' => $this->emailSubject, 'body' => $this->emailBody,
            ],
            QrCodeType::Phone => ['phone' => $this->phone],
            QrCodeType::Sms => ['phone' => $this->smsPhone, 'message' => $this->smsMessage],
            QrCodeType::Geo => ['latitude' => $this->latitude, 'longitude' => $this->longitude],
            QrCodeType::Event => [
                'title' => $this->eventTitle, 'start' => $this->eventStart,
                'end' => $this->eventEnd, 'location' => $this->eventLocation,
                'description' => $this->eventDescription,
            ],
            QrCodeType::Crypto => [
                'currency' => $this->cryptoCurrency, 'address' => $this->cryptoAddress,
                'amount' => $this->cryptoAmount,
            ],
            QrCodeType::AppStore, QrCodeType::Social, QrCodeType::Menu => ['url' => $this->socialUrl],
            QrCodeType::Pdf => ['file_url' => $this->existingFileUrl ?? ''],
            default => [],
        };
    }

    public function setDesign(string $property, mixed $value): void
    {
        if (property_exists($this, $property) && $this->{$property} !== $value) {
            $this->{$property} = $value;
            $this->refreshPreview();
        }
    }

    public function updatedFgColor(): void { $this->refreshPreview(); }
    public function updatedBgColor(): void { $this->refreshPreview(); }
    public function updatedUseGradient(): void { $this->refreshPreview(); }
    public function updatedGradientColor1(): void { $this->refreshPreview(); }
    public function updatedGradientColor2(): void { $this->refreshPreview(); }
    public function updatedGradientType(): void { $this->refreshPreview(); }
    public function updatedLogo(): void
    {
        $this->selectedIcon = null;
        $this->refreshPreview();
    }
    public function updatedFrameText(): void { $this->refreshPreview(); }
    public function updatedFrameStyle(): void { $this->refreshPreview(); }

    protected function refreshPreview(): void
    {
        if ($this->step >= 2) {
            $this->generatePreview();
        }
    }

    public function generatePreview(): void
    {
        try {
            $logoPath = null;
            if ($this->logo && method_exists($this->logo, 'getRealPath')) {
                $logoPath = $this->logo->getRealPath();
            } elseif ($this->selectedIcon) {
                $logoPath = 'icons/qr-center-icons/' . $this->selectedIcon . '.svg';
            } elseif ($this->existingLogo) {
                $logoPath = $this->existingLogo;
            }

            $tempQr = new QrCode([
                'type' => $this->type,
                'is_dynamic' => false,
                'content_data' => $this->getContentData(),
            ]);
            $tempQr->setRelation('design', new \App\Models\QrDesign([
                'fg_color' => $this->fgColor,
                'bg_color' => $this->bgColor,
                'dot_style' => $this->dotStyle,
                'eye_style' => $this->eyeStyle,
                'eye_frame_style' => $this->eyeFrameStyle,
                'eye_ball_style' => $this->eyeBallStyle,
                'frame_style' => $this->frameStyle ?: null,
                'frame_text' => $this->frameText ?: null,
                'gradient' => $this->useGradient ? [
                    'color1' => $this->gradientColor1,
                    'color2' => $this->gradientColor2,
                    'type' => $this->gradientType,
                ] : null,
                'logo_path' => $logoPath,
            ]));

            $generator = app(QrCodeGeneratorService::class);
            $this->preview = $generator->generateBase64Preview($tempQr, 300);
        } catch (\Throwable $e) {
            logger()->error('QR preview generation failed: ' . $e->getMessage());
            $this->preview = null;
        }
    }

    public function save()
    {
        $this->validateStep();

        $user = auth()->user();
        $paidActionService = app(PaidActionService::class);
        $qrType = QrCodeType::from($this->type);
        $isDynamicCapable = $qrType->isDynamic();

        if (! $this->editing && ! $user->canCreateQrCode(isDynamic: false)) {
            $this->addError('name', __('qr.plan_limit_reached'));

            return;
        }

        if ($this->editing && $isDynamicCapable && ! $this->qrCode->shortLink) {
            if (! $user->canCreateQrCode(isDynamic: true)) {
                $this->addError('name', __('qr.dynamic_plan_limit_reached'));

                return;
            }
        }

        if ($this->customSlug && ShortLink::where('slug', $this->customSlug)
            ->when($this->qrCode?->shortLink, fn ($q) => $q->where('id', '!=', $this->qrCode->shortLink->id))
            ->exists()) {
            $this->addError('customSlug', 'This slug is already taken.');

            return;
        }

        $pendingData = $this->buildPendingData();

        if ($this->editing && $isDynamicCapable && $this->qrCode->shortLink) {
            $actionType = $paidActionService->detectActionType($this->qrCode, $pendingData);

            if ($actionType && $paidActionService->requiresPayment($user, $this->qrCode, $pendingData)) {
                return $paidActionService->createCheckout($user, $this->qrCode, $actionType, $pendingData);
            }
        }

        $this->applySave($pendingData);

        session()->flash('status', $this->editing ? __('qr.updated') : __('qr.created'));

        return redirect()->route('qr-codes.index');
    }

    protected function buildPendingData(): array
    {
        $logoPath = $this->existingLogo;
        if ($this->selectedIcon) {
            $logoPath = 'icons/qr-center-icons/' . $this->selectedIcon . '.svg';
        } elseif ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
        }

        if ($this->pdfFile) {
            $filePath = $this->pdfFile->store('uploads', 'public');
            $this->existingFileUrl = asset('storage/' . $filePath);
        }

        $contentData = $this->getContentData();

        return [
            'name' => $this->name,
            'type' => $this->type,
            'content_data' => $contentData,
            'destination_url' => $contentData['url'] ?? '',
            'custom_slug' => $this->customSlug ?: null,
            'link_password' => $this->linkPassword,
            'expires_at' => $this->expiresAt,
            'max_scans' => $this->maxScans,
            'is_active' => true,
            'design' => [
                'fg_color' => $this->fgColor,
                'bg_color' => $this->bgColor,
                'dot_style' => $this->dotStyle,
                'eye_style' => $this->eyeStyle,
                'eye_frame_style' => $this->eyeFrameStyle,
                'eye_ball_style' => $this->eyeBallStyle,
                'frame_style' => $this->frameStyle ?: null,
                'frame_text' => $this->frameText ?: null,
                'gradient' => $this->useGradient ? [
                    'color1' => $this->gradientColor1,
                    'color2' => $this->gradientColor2,
                    'type' => $this->gradientType,
                ] : null,
                'logo_path' => $logoPath,
            ],
        ];
    }

    protected function applySave(array $pendingData): void
    {
        $user = auth()->user();
        $qrType = QrCodeType::from($this->type);
        $isDynamicCapable = $qrType->isDynamic();

        $qrData = [
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'name' => $pendingData['name'],
            'type' => $pendingData['type'],
            'is_dynamic' => $this->editing && $isDynamicCapable ? true : false,
            'content_data' => $pendingData['content_data'],
        ];

        if ($this->editing) {
            $this->qrCode->update($qrData);
            $qr = $this->qrCode->fresh();
        } else {
            $qr = QrCode::create($qrData);
        }

        $qr->design()->updateOrCreate(
            ['qr_code_id' => $qr->id],
            $pendingData['design']
        );

        if ($this->editing && $isDynamicCapable) {
            $destinationUrl = $pendingData['destination_url'];

            if ($qr->shortLink) {
                $update = [
                    'destination_url' => $destinationUrl,
                    'expires_at' => $pendingData['expires_at'] ? \Carbon\Carbon::parse($pendingData['expires_at']) : $qr->shortLink->expires_at,
                    'max_scans' => $pendingData['max_scans'],
                ];

                if (filled($pendingData['link_password'])) {
                    $update['password_hash'] = bcrypt($pendingData['link_password']);
                }

                $qr->shortLink->update($update);
            } else {
                ShortLink::create([
                    'qr_code_id' => $qr->id,
                    'slug' => $pendingData['custom_slug'] ?: ShortLink::generateSlug(),
                    'destination_url' => $destinationUrl,
                    'password_hash' => $pendingData['link_password'] ? bcrypt($pendingData['link_password']) : null,
                    'expires_at' => $pendingData['expires_at'] ? \Carbon\Carbon::parse($pendingData['expires_at']) : null,
                    'max_scans' => $pendingData['max_scans'],
                    'is_active' => true,
                ]);
            }
        }
    }

    public function canUseFullCustomization(): bool
    {
        return auth()->user()->hasFeature(Feature::FullCustomization);
    }

    public function downloadPng()
    {
        if (! $this->qrCode) {
            $this->save();
            return;
        }

        $generator = app(QrCodeGeneratorService::class);
        $png = $generator->generatePng($this->qrCode, 1000);

        return response()->streamDownload(function () use ($png) {
            echo $png;
        }, str($this->name)->slug() . '.png', ['Content-Type' => 'image/png']);
    }

    public function getAvailableTypesProperty(): array
    {
        return QrCodeType::allTypes();
    }

    public function render()
    {
        return view('livewire.qr-codes.qr-code-builder')
            ->layout('layouts.app', ['title' => $this->editing ? __('qr.edit') : __('qr.create')]);
    }
}
