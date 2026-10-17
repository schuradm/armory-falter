<?php

namespace App\Services\Vouchers;

use RuntimeException;

/** Code nicht einlösbar. Die Nachricht ist für Kunden bestimmt (deutsch, konkret). */
class VoucherException extends RuntimeException {}
