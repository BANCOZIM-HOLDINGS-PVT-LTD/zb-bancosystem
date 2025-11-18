// Currency exchange rates
// Last updated: Nov 17, 2025 - Source: RBZ, Trading Economics
export const EXCHANGE_RATES = {
    USD_TO_ZIG: 26.35,
} as const;

export function convertUSDtoZIG(usdAmount: number): number {
    return usdAmount * EXCHANGE_RATES.USD_TO_ZIG;
}

export function formatCurrency(amount: number, currency: 'USD' | 'ZIG' = 'USD'): string {
    if (currency === 'ZIG') {
        return `ZIG ${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    return `$${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
