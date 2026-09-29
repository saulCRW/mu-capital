import { useState, useEffect } from 'react';
import { transaccionService } from '../services/transaccionService';
import type { Transaccion } from '../models/types';

export function useTransacciones() {
  const [transacciones, setTransacciones] = useState<Transaccion[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    try {
      setLoading(true);
      setError(null);
      setTransacciones(await transaccionService.getAll());
    } catch {
      setError('Error al cargar transacciones');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  const create = async (data: Partial<Transaccion>) => {
    const nueva = await transaccionService.create(data);
    setTransacciones(prev => [nueva, ...prev]);
    return nueva;
  };

  const remove = async (id: number) => {
    await transaccionService.remove(id);
    setTransacciones(prev => prev.filter(t => t.id !== id));
  };

  return { transacciones, loading, error, create, remove, refresh };
}
