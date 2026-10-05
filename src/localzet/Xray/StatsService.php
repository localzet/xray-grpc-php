<?php

/**
 * @package     Localzet xray-grpc-php
 * @link        https://github.com/localzet/xray-grpc-php
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2026 Localzet Group (Localzet contributions)
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3, or any later version.
 * This program is distributed without any warranty; see the license.
 * A copy is available at <https://www.gnu.org/licenses/>.
 * Questions: <creator@localzet.com>.
 *
 * Original copyright and license notices below remain applicable.
 */


namespace localzet\Xray;

use Grpc\ChannelCredentials;
use RuntimeException;
use Xray\App\Stats\Command\GetStatsRequest;
use Xray\App\Stats\Command\GetStatsResponse;
use Xray\App\Stats\Command\QueryStatsRequest;
use Xray\App\Stats\Command\QueryStatsResponse;
use Xray\App\Stats\Command\StatsServiceClient;
use Xray\App\Stats\Command\SysStatsRequest;
use Xray\App\Stats\Command\SysStatsResponse;
use const Grpc\STATUS_OK;

class StatsService
{
    private $client;

    public function __construct($hostname)
    {
        $this->client = new StatsServiceClient($hostname, [
            'credentials' => ChannelCredentials::createInsecure()
        ]);
    }

    public function GetStats(string $name = null, bool $reset = null): array
    {
        $input = compact('name', 'reset');
        $data = array_filter($input, fn($value) => $value !== null);
        $request = new GetStatsRequest($data);

        /** @var GetStatsResponse $response */
        [$response, $status] = $this->client->GetStats($request)->wait();

        if ($status->code !== STATUS_OK) {
            throw new RuntimeException('Ошибка получения статистики: ' . $status->details);
        }

        $stat = $response->getStat();
        return [
            'name' => $stat->getName(),
            'value' => $stat->getValue()
        ];
    }

    public function QueryStats(string $pattern = null, bool $reset = null): array
    {
        $input = compact('pattern', 'reset');
        $data = array_filter($input, fn($value) => $value !== null);
        $request = new QueryStatsRequest($data);

        /** @var QueryStatsResponse $response */
        [$response, $status] = $this->client->QueryStats($request)->wait();

        if ($status->code !== STATUS_OK) {
            throw new RuntimeException('Ошибка получения статистики: ' . $status->details);
        }

        $stats = [];
        foreach ($response->getStat() as $stat) {
            $stats[] = [
                'name' => $stat->getName(),
                'value' => $stat->getValue()
            ];
        }
        return $stats;
    }

    public function GetSysStats(): array
    {
        $request = new SysStatsRequest();

        /** @var SysStatsResponse $response */
        [$response, $status] = $this->client->GetSysStats($request)->wait();

        if ($status->code !== STATUS_OK) {
            throw new RuntimeException('Ошибка получения статистики: ' . $status->details);
        }

        return [
            'NumGoroutine' => $response->getNumGoroutine(),
            'NumGC' => $response->getNumGC(),
            'Alloc' => $response->getAlloc(),
            'TotalAlloc' => $response->getTotalAlloc(),
            'Sys' => $response->getSys(),
            'Mallocs' => $response->getMallocs(),
            'Frees' => $response->getFrees(),
            'LiveObjects' => $response->getLiveObjects(),
            'PauseTotalNs' => $response->getPauseTotalNs(),
            'Uptime' => $response->getUptime(),
        ];
    }
}