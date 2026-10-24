<?php

namespace App\Http\Controllers;

class OrderController
{
    public function store(array $data)
    {
        return $this->repository->save($data);
    }
}
