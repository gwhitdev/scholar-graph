<?php

namespace App\Enums;

enum PaperStatus: string
{
    case Unread = 'unread';
    case Reading = 'reading';
    case Read = 'read';
    case Excluded = 'excluded';
}
