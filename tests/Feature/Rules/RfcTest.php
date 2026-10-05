<?php

use App\Rules\Rfc;
use Illuminate\Support\Facades\Validator;

it('accepts a valid RFC', function (string $rfc) {
    $validator = Validator::make(['rfc' => $rfc], ['rfc' => [new Rfc]]);

    expect($validator->passes())->toBeTrue();
})->with([
    'moral person' => 'ABC123456XY9',
    'physical person' => 'ABCD123456XY1',
    'with ampersand' => 'A&B123456XY9',
    'with eñe' => 'AÑC123456XY9',
]);

it('rejects an invalid RFC', function (string $rfc) {
    $validator = Validator::make(['rfc' => $rfc], ['rfc' => [new Rfc]]);

    expect($validator->fails())->toBeTrue();
})->with([
    'too long' => 'ABCDE123456XY9',
    'too short' => 'ABC123',
    'missing date' => 'ABCDXY1234XYZ',
    'lowercase' => 'abc123456xy9',
    'six digits' => 'ABC12345XY9',
    'with spaces' => 'ABC 123456 XY9',
]);

it('translates the failure message', function () {
    app()->setLocale('es');

    $validator = Validator::make(['rfc' => 'nope'], ['rfc' => [new Rfc]]);

    expect($validator->errors()->first('rfc'))->toBe('El campo RFC no es un RFC válido.');
});
