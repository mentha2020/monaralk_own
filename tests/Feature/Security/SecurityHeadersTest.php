<?php

test('storefront responses carry the security headers', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');

    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("base-uri 'self'")
        ->and($csp)->toContain("form-action 'self'")
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toContain("script-src 'self'")
        ->and($csp)->toContain('fonts.bunny.net');
});

test('error pages and admin responses get the headers too', function () {
    $this->get('/definitely-missing')
        ->assertNotFound()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get('/admin')
        ->assertRedirect()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('the csp allows the app scripts but blocks other origins', function () {
    $csp = (string) $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)->toContain("connect-src 'self'")
        ->and($csp)->toContain("img-src 'self' data: blob:")
        ->and($csp)->not->toContain("script-src 'self' https:")
        ->and($csp)->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'");
});
