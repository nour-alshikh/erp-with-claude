/** Format integer cents to a display string: 9999 → "99.99" */
export function formatCents(cents: number, currency = ''): string {
  const formatted = (cents / 100).toFixed(2);
  return currency ? `${currency} ${formatted}` : formatted;
}

/** Convert a user-typed value (e.g. "99.99") to integer cents: → 9999 */
export function toCents(value: string | number): number {
  return Math.round(parseFloat(String(value)) * 100);
}
