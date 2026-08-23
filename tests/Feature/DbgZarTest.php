<?php

use App\Services\Payment\ZarinpalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('dbg1: errors envelope via gateway class', function () {
    Http::fake([
        'sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
            'data' => [],
            'errors' => ['code' => -9, 'message' => 'خطای اعتبارسنجی'],
        ]),
    ]);
    $gw = new ZarinpalGateway('test-merchant-id', 'sandbox');
    try {
        $r = $gw->start(1000, 'http://localhost/cb', 'd');
        file_put_contents('/tmp/opencode/z.txt', 'NO THROW: '.json_encode($r)."\n", FILE_APPEND);
    } catch (Throwable $e) {
        file_put_contents('/tmp/opencode/z.txt', 'THREW: '.$e->getMessage()."\n", FILE_APPEND);
    }
    expect(true)->toBeTrue();
});

it('dbg2: preventStrayRequests scope', function () {
    Http::preventStrayRequests();
    Http::fake([
        'sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
            'data' => ['authority' => 'AX', 'code' => 100],
        ]),
    ]);
    Http::get('https://sandbox.zarinpal.com/pg/v4/payment/request.json');
    expect(true)->toBeTrue();
});
