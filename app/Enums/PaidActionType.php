<?php

namespace App\Enums;

enum PaidActionType: string
{
    case EditDynamicQr = 'edit_dynamic_qr';
    case ChangePassword = 'change_password';
    case SetExpiration = 'set_expiration';
    case UpdateScanLimit = 'update_scan_limit';
    case ReactivateQr = 'reactivate_qr';

    public function label(): string
    {
        return match ($this) {
            self::EditDynamicQr => 'Edit dynamic QR destination',
            self::ChangePassword => 'Change password protection',
            self::SetExpiration => 'Set or extend expiration',
            self::UpdateScanLimit => 'Update scan limit',
            self::ReactivateQr => 'Re-activate dynamic QR',
        };
    }
}
