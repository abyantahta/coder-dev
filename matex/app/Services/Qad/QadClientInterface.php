<?php

namespace App\Services\Qad;

use App\Models\Receiving;

interface QadClientInterface
{
    /**
     * Push receiving data to QAD.
     *
     * @return array{success: bool, payload: array, response: array}
     */
    public function receive(Receiving $receiving): array;
}
