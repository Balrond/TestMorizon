<?php

namespace App\Http;

interface PhoenixUsersClientInterface
{
    public function list(array $query = []): array;

    public function get(int $id): array;

    public function create(array $payload): array;

    public function update(int $id, array $payload): array;

    public function delete(int $id): array;
}
