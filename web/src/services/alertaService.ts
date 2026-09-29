import api from './api';
import type { Alerta } from '../models/types';

export const alertaService = {
  getAll: () => api.get<Alerta[]>('/alertas').then(r => r.data),
  getById: (id: number) => api.get<Alerta>(`/alertas/${id}`).then(r => r.data),
  create: (data: Partial<Alerta>) => api.post<Alerta>('/alertas', data).then(r => r.data),
  update: (id: number, data: Partial<Alerta>) => api.put<Alerta>(`/alertas/${id}`, data).then(r => r.data),
  remove: (id: number) => api.delete(`/alertas/${id}`).then(r => r.data),
};
