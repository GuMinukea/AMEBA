export type Currency = 'EUR' | 'USD';

// Demo conversion rate, deliberately kept in one place for easy updates.
export const EUR_TO_USD = 1.08;

export function formatMoney(eurAmount: number): string {
  const currency: Currency = localStorage.getItem('ameba-currency') === 'USD' ? 'USD' : 'EUR';
  const amount = currency === 'USD' ? Number(eurAmount) * EUR_TO_USD : Number(eurAmount);
  return new Intl.NumberFormat(undefined, { style: 'currency', currency, minimumFractionDigits: 2 }).format(amount);
}
