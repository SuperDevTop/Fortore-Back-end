<?php

namespace App\Repositories;

use App\Models\AdminNotification;

class AdminNotificationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AdminNotification());
    }


    public function persistNotification(array $data)
    {
        return $this->model->forceFill($data);
    }
}
