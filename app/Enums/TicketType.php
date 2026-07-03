<?php

namespace App\Enums;

enum TicketType: string
{
    case Bug = 'bug';
    case Feature = 'feature';
    case Support = 'support';
    case Billing = 'billing';
}
