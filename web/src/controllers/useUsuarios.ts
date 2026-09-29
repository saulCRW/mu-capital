import { useState, useEffect } from 'react';
import { usuarioService } from '../services/usuarioService';
import type { UsuarioResumen } from '../models/types';

export function useUsuarios() {
  const [usuarios, setUsuarios] = useState<UsuarioResumen[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    try {
      setLoading(true);
      setError(null);
      setUsuarios(await usuarioService.getAll());
    } catch (e: any) {
      setError(
        e?.response?.status === 403
          ? 'Necesitas permisos de administrador para ver los usuarios.'
          : 'Error al cargar usuarios'
      );
      setUsuarios([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  const create = async (data: Parameters<typeof usuarioService.register>[0]) => {
    const res = await usuarioService.register(data);
    await refresh();
    return res;
  };

  return { usuarios, loading, error, create, refresh };
}
