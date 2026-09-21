<?php

namespace App\Repositories\Eloquent;

use App\Models\SupportTicketMessage;
use App\Repositories\Contracts\SupportTicketMessageRepositoryInterface;

class SupportTicketMessageRepository extends BaseRepository implements SupportTicketMessageRepositoryInterface
{
    public function __construct(SupportTicketMessage $model)
    {
        parent::__construct($model);
    }
}
