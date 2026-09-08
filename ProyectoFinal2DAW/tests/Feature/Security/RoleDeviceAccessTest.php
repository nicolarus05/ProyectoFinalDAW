<?php

use App\Http\Middleware\CheckRoleDevice;
use App\Models\User;
use Illuminate\Http\Request;

function roleDeviceResponse(string $role, string $userAgent, bool $expectsJson = false)
{
    $request = Request::create('/dashboard', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => $userAgent,
    ]);
    $request->setUserResolver(fn () => User::make(['rol' => $role]));

    if ($expectsJson) {
        $request->headers->set('Accept', 'application/json');
    }

    return (new CheckRoleDevice)->handle(
        $request,
        fn () => response('allowed')
    );
}

$desktopUserAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36';
$phoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1';
$tabletUserAgent = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1';

it('allows an employee to access from a desktop computer', function () use ($desktopUserAgent) {
    $response = roleDeviceResponse('empleado', $desktopUserAgent);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('allowed');
});

it('blocks an employee from a phone', function () use ($phoneUserAgent) {
    $response = roleDeviceResponse('empleado', $phoneUserAgent);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('ordenador');
});

it('blocks an employee from a tablet', function () use ($tabletUserAgent) {
    $response = roleDeviceResponse('empleado', $tabletUserAgent);

    expect($response->getStatusCode())->toBe(403);
});

it('allows a manager from a phone', function () use ($phoneUserAgent) {
    $response = roleDeviceResponse('gerente', $phoneUserAgent);

    expect($response->getStatusCode())->toBe(200);
});

it('allows an administrator from a phone', function () use ($phoneUserAgent) {
    $response = roleDeviceResponse('admin', $phoneUserAgent);

    expect($response->getStatusCode())->toBe(200);
});

it('allows a client from a phone', function () use ($phoneUserAgent) {
    $response = roleDeviceResponse('cliente', $phoneUserAgent);

    expect($response->getStatusCode())->toBe(200);
});

it('returns a JSON error for an employee AJAX request from a phone', function () use ($phoneUserAgent) {
    $response = roleDeviceResponse('empleado', $phoneUserAgent, true);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->headers->get('Content-Type'))->toContain('application/json')
        ->and($response->getContent())->toContain('solo puede acceder');
});
