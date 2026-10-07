<?php

namespace App\Modules\Audit\Observers;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\TenantContext;
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
    private const OCULTAS = ['password', 'remember_token'];

    /** Columnas que no aportan a la auditoría. */
    private const IGNORADAS = ['created_at', 'updated_at'];

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
            'organization_id' => $this->organizacionDe($model),
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

    private function organizacionDe(Model $model): ?int
    {
        if ($model instanceof Organization) {
            return $model->getKey();
        }

        // Modelos con organization_id (clientes, transacciones...) o catálogos globales (activos).
        return $model->getAttribute('organization_id') ?? TenantContext::organizationId();
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
