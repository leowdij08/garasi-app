<?php

namespace App\Enums;

enum StatusVerifikasi: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
