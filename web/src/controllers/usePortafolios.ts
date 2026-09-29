import { useState, useEffect } from 'react';
import { portafolioService } from '../services/portafolioService';
import type { Portafolio } from '../models/types';

export function usePortafolios() {
  const [portafolios, setPortafolios] = useState<Portafolio[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    try {
      setLoading(true);
      setError(null);
      setPortafolios(await portafolioService.getAll());
    } catch {
      setError('Error al cargar portafolios');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  const create = async (data: Partial<Portafolio>) => {
    const nuevo = await portafolioService.create(data);
    setPortafolios(prev => [...prev, nuevo]);
    return nuevo;
  };

  const update = async (id: number, data: Partial<Portafolio>) => {
    const updated = await portafolioService.update(id, data);
    setPortafolios(prev => prev.map(p => p.id === id ? updated : p));
    return updated;
  };

  const remove = async (id: number) => {
    await portafolioService.remove(id);
    setPortafolios(prev => prev.filter(p => p.id !== id));
  };

  return { portafolios, loading, error, create, update, remove, refresh };
}
