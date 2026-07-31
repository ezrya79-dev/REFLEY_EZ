<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('the login screen renders', function () {
    $this->get('/login')->assertOk()->assertSee('email');
});

test('the root redirects to the dashboard route', function () {
    $this->get('/')->assertRedirect('/tableau-de-bord');
});

test('guests are redirected to login', function () {
    $this->get('/tableau-de-bord')->assertRedirect('/login');
});

test('a user can authenticate and reach the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/tableau-de-bord');
    $this->assertAuthenticatedAs($user);
});

test('invalid credentials are rejected', function () {
    $user = User::factory()->create();

    $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertRedirect('/login')->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a deactivated account cannot sign in even with valid credentials', function () {
    $user = User::factory()->inactive()->create();

    $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login attempts are throttled after five failures', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    // La limite est par e-mail+IP : un autre compte n'est pas affecté.
    $other = User::factory()->create();
    $this->post('/login', ['email' => $other->email, 'password' => 'password'])
        ->assertRedirect('/tableau-de-bord');
});

test('a successful login clears the throttle counter', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    expect(RateLimiter::attempts(strtolower($user->email).'|127.0.0.1'))->toBe(0);
});

test('a user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});

test('authenticated users are redirected away from the login screen', function () {
    $this->actingAs(User::factory()->create())
        ->get('/login')
        ->assertRedirect('/tableau-de-bord');
});
