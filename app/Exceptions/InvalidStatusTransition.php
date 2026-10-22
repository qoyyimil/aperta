<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use DomainException;

class InvalidStatusTransition extends DomainException
{
    public function __construct(BookingStatus $from, BookingStatus $to)
    {
        parent::__construct("Status tidak bisa diubah dari {$from->getLabel()} ke {$to->getLabel()}.");
    }
}
