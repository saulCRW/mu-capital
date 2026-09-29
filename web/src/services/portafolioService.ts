import api from './api';
import type { Portafolio } from '../models/types';

export const portafolioService = {
  getAll: () => api.get<Portafolio[]>('/portafolios').then(r => r.data),
  getById: (id: number) => api.get<Portafolio>(`/portafolios/${id}`).then(r => r.data),
  create: (data: Partial<Portafolio>) => api.post<Portafolio>('/portafolios', data).then(r => r.data),
  update: (id: number, data: Partial<Portafolio>) => api.put<Portafolio>(`/portafolios/${id}`, data).then(r => r.data),
  remove: (id: number) => api.delete(`/portafolios/${id}`).then(r => r.data),
};
