/**
 * Fuerza la descarga de un Blob ya recibido del backend (ej. export XLSX) sin
 * navegar fuera de la SPA. Crea un <a> temporal con un object URL, dispara el
 * click, y libera el URL enseguida - el propio navegador ya copió el contenido
 * al iniciar la descarga, no hace falta mantenerlo vivo más tiempo.
 */
export function downloadBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}
