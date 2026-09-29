import { useState, useEffect } from 'react';
import { activoService } from '../services/activoService';
import type { Activo } from '../models/types';

export function useActivos(params?: { tipo?: string; search?: string }) {
  const [activos, setActivos] = useState<Activo[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    try {
      setLoading(true);
      setError(null);
      setActivos(await activoService.getAll(params));
    } catch {
      setError('Error al cargar activos');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  return { activos, loading, error, refresh };
}
