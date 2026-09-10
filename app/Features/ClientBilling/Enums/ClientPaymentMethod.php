<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Enums;

enum ClientPaymentMethod: string
{
    case Manual = 'manual';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Other = 'other';
    case Bkash = 'bkash';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
