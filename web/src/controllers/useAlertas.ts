import { useState, useEffect } from 'react';
import { alertaService } from '../services/alertaService';
import type { Alerta } from '../models/types';

export function useAlertas() {
  const [alertas, setAlertas] = useState<Alerta[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    try {
      setLoading(true);
      setError(null);
      setAlertas(await alertaService.getAll());
    } catch {
      setError('Error al cargar alertas');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  const create = async (data: Partial<Alerta>) => {
    const nueva = await alertaService.create(data);
    setAlertas(prev => [...prev, nueva]);
    return nueva;
  };

  const toggleActiva = async (id: number, activa: boolean) => {
    const updated = await alertaService.update(id, { activa });
    setAlertas(prev => prev.map(a => a.id === id ? updated : a));
    return updated;
  };

  const remove = async (id: number) => {
    await alertaService.remove(id);
    setAlertas(prev => prev.filter(a => a.id !== id));
  };

  return { alertas, loading, error, create, toggleActiva, remove, refresh };
}
