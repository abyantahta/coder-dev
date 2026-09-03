<?php

namespace App\Enums;

/**
 * Tracks whether a master data record was entered manually or synced from QAD.
 */
enum SyncSource: string
{
    case Manual = 'manual';
    case Qad = 'qad';
}
