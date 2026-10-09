const PALETTE = [
  '#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4',
  '#009688', '#4CAF50', '#8BC34A', '#FF9800', '#795548', '#607D8B', '#FF5722', '#FFC107',
];

/** Color estable por usuario (mismo id -> mismo color siempre), para distinguir de un vistazo quién hizo qué en listas con varios miembros (ej. Actividad reciente). */
export function userColor(userId: number): string {
  return PALETTE[userId % PALETTE.length];
}

export function userInitial(userName: string): string {
  return userName.trim().charAt(0).toUpperCase() || '?';
}
