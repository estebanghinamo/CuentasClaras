import { ApiError } from '../../core/models/api.models';

/** Primer mensaje de validación para un campo, si `error.details[field]` existe (ver ESPECIFICACION_TECNICA.md §0.4/D.6). */
export function fieldError(error: ApiError | null, field: string): string | null {
  return error?.error?.details?.[field]?.[0] ?? null;
}

/** Mensaje general para el banner del formulario y para el modal global de error.
 * Si hay errores de campo (422), muestra el primer mensaje puntual en vez de un
 * texto genérico - también aparece bajo el input correspondiente (fieldError),
 * pero repetirlo acá es preferible a un "revisá los campos" que no dice nada. */
export function formErrorMessage(error: ApiError | null): string | null {
  if (!error) {
    return null;
  }
  if (error.error?.code === 'VALIDATION_ERROR' && error.error.details) {
    const firstField = Object.values(error.error.details)[0];
    return firstField?.[0] ?? 'Revisá los campos marcados.';
  }
  return error.error?.message ?? 'Ocurrió un error inesperado.';
}
