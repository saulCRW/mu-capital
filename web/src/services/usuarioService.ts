import api from './api';
import type { Usuario, UsuarioDetalle, UsuarioResumen } from '../models/types';

export const usuarioService = {
  me: () => api.get<Usuario>('/me').then(r => r.data),
  update: (data: Partial<Usuario> & { password?: string; password_confirmation?: string }) =>
    api.put<Usuario>('/me', data).then(r => r.data),
  destroy: () => api.delete('/me').then(r => r.data),
  register: (data: {
    nombre: string;
    apellido: string;
    email: string;
    password: string;
    password_confirmation: string;
    telefono?: string;
    pais?: string;
  }) => api.post('/register', data).then(r => r.data),
  getAll: () => api.get<UsuarioResumen[]>('/usuarios').then(r => r.data),
  getById: (id: number) => api.get<UsuarioDetalle>(`/usuarios/${id}`).then(r => r.data),
};
