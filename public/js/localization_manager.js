/**
 * RE7LA Localization & Currency Manager
 * Handles real-time price conversion and language-specific UI tweaks.
 */
class LocalizationManager {
    constructor() {
        this.apiBase = 'https://api.frankfurter.app/latest';
        this.currentCurrency = this.getCookie('USER_CURRENCY') || 'TND';
        this.currentLocale = this.getCookie('USER_LOCALE') || 'fr';
        this.rates = null;
        
        this.init();
    }

    async init() {
        console.log(`[Localization] Init with ${this.currentLocale} / ${this.currentCurrency}`);
        
        if (this.currentCurrency !== 'TND') {
            await this.fetchRates();
            this.convertAllPrices();
        }

        this.applyRTLLogic();
    }

    async fetchRates() {
        const cacheKey = 'currency_rates_cache';
        const cached = localStorage.getItem(cacheKey);
        const now = new Date().getTime();

        if (cached) {
            const data = JSON.parse(cached);
            if (now - data.timestamp < 3600000) { // 1h cache
                this.rates = data.rates;
                return;
            }
        }

        try {
            const response = await fetch(`${this.apiBase}?from=EUR`);
            const data = await response.json();
            this.rates = data.rates;
            
            localStorage.setItem(cacheKey, JSON.stringify({
                rates: this.rates,
                timestamp: now
            }));
        } catch (e) {
            console.error('[Localization] Failed to fetch rates', e);
        }
    }

    convertAllPrices() {
        const priceElements = document.querySelectorAll('.price-convert');
        
        priceElements.forEach(el => {
            const tndValue = parseFloat(el.getAttribute('data-value'));
            if (isNaN(tndValue)) return;

            const convertedValue = this.convert(tndValue, 'TND', this.currentCurrency);
            const symbol = this.getSymbol(this.currentCurrency);

            el.innerHTML = `${convertedValue} <span class="currency-symbol">${symbol}</span>`;
            el.classList.add('converted');
        });
    }

    convert(amount, from, to) {
        if (!this.rates) return amount;
        
        // Logic: TND -> EUR -> Target
        // Note: Frankfurter rates are relative to EUR
        const eurValue = amount / (this.rates['TND'] || 3.3); // Fallback TND/EUR ~3.3
        const finalValue = to === 'EUR' ? eurValue : eurValue * (this.rates[to] || 1);
        
        return finalValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    getSymbol(currency) {
        const symbols = { 'EUR': '€', 'USD': '$', 'TND': 'DT', 'GBP': '£' };
        return symbols[currency] || currency;
    }

    applyRTLLogic() {
        if (this.currentLocale === 'ar') {
            document.documentElement.setAttribute('dir', 'rtl');
            document.body.classList.add('rtl-mode');
        }
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
}

// Global instance
window.localizationManager = new LocalizationManager();
