<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationAndOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_personal_categories_and_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Antonio',
            'email' => 'antonio@example.com',
            'password' => 'segura2026clave',
            'password_confirmation' => 'segura2026clave',
            'device_name' => 'Prueba web',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'antonio@example.com')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $user = User::query()->where('email', 'antonio@example.com')->firstOrFail();
        $this->assertGreaterThan(0, Categoria::query()->withoutGlobalScope('user')->where('user_id', $user->id)->count());
    }

    public function test_registration_validation_messages_are_in_spanish(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Antonio',
            'email' => 'antonio@example.com',
            'password' => 'corta1',
            'password_confirmation' => 'corta1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'errors.password.0',
                'El campo contraseña debe tener al menos 10 caracteres.',
            );
    }

    public function test_user_can_change_password_and_other_sessions_are_closed(): void
    {
        $user = User::factory()->create(['password' => 'anterior2026']);
        $currentToken = $user->createToken('Dispositivo actual')->plainTextToken;
        $user->createToken('Otro dispositivo');

        $this->withToken($currentToken)->patchJson('/api/auth/password', [
            'current_password' => 'anterior2026',
            'password' => 'nueva2026segura',
            'password_confirmation' => 'nueva2026segura',
        ])->assertOk()
            ->assertJsonPath('message', 'Contraseña actualizada correctamente. Se han cerrado las demás sesiones.');

        $this->assertTrue(Hash::check('nueva2026segura', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
        $this->withToken($currentToken)->getJson('/api/auth/user')->assertOk();
    }

    public function test_account_deletion_requires_password_and_removes_owned_data(): void
    {
        $user = User::factory()->create(['password' => 'borrar2026segura']);
        $token = $user->createToken('Web')->plainTextToken;
        $category = Categoria::query()->create([
            'user_id' => $user->id,
            'nombre' => 'Temporal',
            'color' => '#111111',
            'icono' => 'otros',
        ]);
        $expense = Gasto::query()->create([
            'user_id' => $user->id,
            'titulo' => 'Se elimina con la cuenta',
            'importe' => 20,
            'fecha' => '2026-09-01',
            'categoria_id' => $category->id,
        ]);

        $this->withToken($token)->deleteJson('/api/auth/account', [
            'password' => 'incorrecta2026',
            'confirmation' => 'BORRAR MI CUENTA',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'La contraseña no es correcta.');

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->withToken($token)->deleteJson('/api/auth/account', [
            'password' => 'borrar2026segura',
            'confirmation' => 'BORRAR MI CUENTA',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('categorias', ['id' => $category->id]);
        $this->assertDatabaseMissing('gastos', ['id' => $expense->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_editing_an_expense_keeps_the_visible_title_and_updates_the_record(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Categoria::query()->create([
            'nombre' => 'Comida',
            'color' => '#84CC16',
            'icono' => 'comida',
        ]);
        $expense = Gasto::query()->create([
            'titulo' => 'Compra inicial',
            'importe' => 25.50,
            'fecha' => '2026-09-01',
            'categoria_id' => $category->id,
            'observaciones' => null,
        ]);

        $this->putJson("/api/gastos-mensuales/gastos/{$expense->id}", [
            'titulo' => 'Compra editada',
            'importe' => 31.75,
            'fecha' => '2026-09-01',
            'categoria_id' => $category->id,
            'observaciones' => 'Texto conservado',
        ])->assertOk()->assertJsonPath('gasto.titulo', 'Compra editada');

        $this->assertDatabaseHas('gastos', [
            'id' => $expense->id,
            'user_id' => $user->id,
            'titulo' => 'Compra editada',
        ]);
    }

    public function test_user_cannot_edit_another_users_expense_or_use_their_category(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $ownerCategory = Categoria::query()->create(['nombre' => 'Privada', 'color' => '#111111', 'icono' => 'otros']);
        $expense = Gasto::query()->create([
            'titulo' => 'Solo del propietario',
            'importe' => 10,
            'fecha' => '2026-09-01',
            'categoria_id' => $ownerCategory->id,
        ]);

        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->putJson("/api/gastos-mensuales/gastos/{$expense->id}", [
            'titulo' => 'Intento',
            'importe' => 10,
            'fecha' => '2026-09-01',
            'categoria_id' => $ownerCategory->id,
        ])->assertNotFound();

        $this->postJson('/api/gastos-mensuales/gastos', [
            'titulo' => 'Categoría ajena',
            'importe' => 10,
            'fecha' => '2026-09-01',
            'categoria_id' => $ownerCategory->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
    }
}
