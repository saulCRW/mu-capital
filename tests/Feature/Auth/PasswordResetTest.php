<?php

namespace Tests\Feature\Auth;

use App\Modules\Users\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_el_enlace_de_recuperacion(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_correo_inexistente_recibe_el_mismo_mensaje(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nadie@example.com'])
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_cambia_la_contrasena_con_un_token_valido(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.$user->email)->assertOk();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('NuevaClave2026', $user->fresh()->password));
    }

    public function test_rechaza_un_token_invalido(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'token-falso',
            'email' => $user->email,
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasErrors('email');
    }
}
