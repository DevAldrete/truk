<?php

use App\Contracts\BillingGateway;
use App\Contracts\Data\StampResult;
use App\Contracts\Fakes\FakePacProvider;
use App\Contracts\PacProvider;
use App\Contracts\RoutingProvider;
use App\Contracts\TelematicsProvider;
use App\Contracts\TollCatalog;
use App\Enums\DocumentStatus;

test('every external provider resolves to its fake', function () {
    expect(app(PacProvider::class))->toBeInstanceOf(FakePacProvider::class);
    expect(app(TelematicsProvider::class))->not->toBeNull();
    expect(app(RoutingProvider::class))->not->toBeNull();
    expect(app(TollCatalog::class))->not->toBeNull();
    expect(app(BillingGateway::class))->not->toBeNull();
});

test('the fake PAC stamps a payload into a stamped result', function () {
    $result = app(PacProvider::class)->stamp(['emisor' => 'ABC010101AAA']);

    expect($result)->toBeInstanceOf(StampResult::class)
        ->and($result->status)->toBe(DocumentStatus::Stamped)
        ->and($result->uuid)->not->toBeEmpty()
        ->and($result->stampedAt)->not->toBeNull();
});
