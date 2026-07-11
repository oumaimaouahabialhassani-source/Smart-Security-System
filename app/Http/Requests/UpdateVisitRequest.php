<?php

namespace App\Http\Requests;

class UpdateVisitRequest extends StoreVisitRequest
{
    // Same rules and authorization as registration; kept as a separate
    // class so update-specific rules can diverge later.
}
