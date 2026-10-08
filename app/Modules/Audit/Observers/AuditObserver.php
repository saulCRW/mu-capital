<?php

namespace App\Modules\Audit\Observers;

use App\Modules\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Observer de Eloquent: Laravel llama a estos métodos automáticamente
 * después de crear, actualizar o borrar un modelo que lo tenga registrado
 * con #[ObservedBy(AuditObserver::class)].
 *
 * Guarda en audit_log quién hizo qué, sobre qué registro, y los valores
 * antes/después (solo las columnas que cambiaron).
 */
class AuditObserver
{
    /** Columnas que nunca se guardan en claro. */
    private const OCULTAS = ['password', 'remember_token', 'dos_factores_secreto', 'dos_factores_recuperacion'];

    /** Columnas que no aportan a la auditoría (el último paso del doble factor cambia en cada inicio de sesión). */
    private const IGNORADAS = ['created_at', 'updated_at', 'dos_factores_ultimo_paso'];

    public function created(Model $model): void
    {
        $this->registrar('alta', $model, null, $this->limpiar($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $despues = $this->limpiar($model->getChanges());

        if ($despues === []) {
            return;
        }

        $antes = $this->limpiar(array_intersect_key($model->getRawOriginal(), $despues));

        $this->registrar('cambio', $model, $antes, $despues);
    }

    public function deleted(Model $model): void
    {
        $this->registrar('baja', $model, $this->limpiar($model->getRawOriginal()), null);
    }

    private function registrar(string $accion, Model $model, ?array $antes, ?array $despues): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'entidad' => class_basename($model),
            'entidad_id' => $model->getKey(),
            'antes' => $antes,
            'despues' => $despues,
            'fecha' => now(),
            'ip' => request()?->ip(),
        ]);
    }

    private function limpiar(array $valores): array
    {
        $valores = array_diff_key($valores, array_flip(self::IGNORADAS));

        foreach (self::OCULTAS as $columna) {
            if (array_key_exists($columna, $valores)) {
                $valores[$columna] = '********';
            }
        }

        return $valores;
    }
}
