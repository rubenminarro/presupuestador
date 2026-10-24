<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;
use App\Support\UserPreferences;
use Illuminate\Support\Facades\DB;

/**
 * Preferencias visuales del usuario autenticado. Se guardan solo los valores
 * que difieren del predeterminado; la lectura siempre devuelve el juego
 * completo.
 */
class UserPreferenceService
{
    /**
     * @return array<string, string>
     */
    public function get(User $user): array
    {
        return UserPreferences::resolve($user->preference?->settings);
    }

    /**
     * @param array<string, string> $changes
     * @return array<string, string>
     */
    public function update(User $user, array $changes): array
    {
        // Dos cambios seguidos de claves distintas (dos clics rápidos) pueden llegar a la vez: con la fila bloqueada,
        // cada uno combina sobre lo que guardó el otro en lugar de pisarlo.
        $settings = DB::transaction(function () use ($user, $changes) {
            UserPreference::query()->createOrFirst(['user_id' => $user->id], ['settings' => []]);

            $preference = UserPreference::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $settings = array_merge(UserPreferences::resolve($preference->settings), array_map('strval', $changes));

            $preference->update(['settings' => array_diff_assoc($settings, UserPreferences::DEFAULTS)]);

            return $settings;
        });

        $user->unsetRelation('preference');

        return $settings;
    }

    /**
     * @return array<string, string>
     */
    public function reset(User $user): array
    {
        UserPreference::where('user_id', $user->id)->delete();

        $user->unsetRelation('preference');

        return UserPreferences::DEFAULTS;
    }
}
