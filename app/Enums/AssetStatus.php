<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Active = 'active';
    case Disposed = 'disposed';
    case Rejected = 'rejected';
}
