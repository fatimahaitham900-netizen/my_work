<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to login and renders executive dashboard for authenticated users', function (): void {
    $this->get('/')->assertRedirect(route('login'));

    $user = User::factory()->executiveViewer()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('المكتب التنفيذي')
        ->assertSee('هيثم نبيل')
        ->assertSee('مؤشرات إدارة المعدات')
        ->assertSee(now()->format('d/m/Y'));
});
