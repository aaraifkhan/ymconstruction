<?php

namespace App\Enums;

enum AssetAccountingStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
