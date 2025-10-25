// Theme Management - Crystal Theme Handler

class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.language = localStorage.getItem('language') || 'ar';
        this.init();
    }

    init() {
        this.applyTheme();
        this.applyLanguage();
        this.setupThemeToggle();
        this.setupLanguageToggle();
    }

    applyTheme() {
        const root = document.documentElement;
        const body = document.body;
        
        // Remove existing theme classes
        root.classList.remove('dark');
        body.classList.remove('dark');
        
        if (this.theme === 'dark') {
            root.classList.add('dark');
            body.classList.add('dark');
        }

        // Update CSS custom properties
        this.updateCSSVariables();
        
        // Update theme icon
        this.updateThemeIcon();
    }

    updateCSSVariables() {
        const root = document.documentElement;
        
        if (this.theme === 'dark') {
            root.style.setProperty('--background', '#0f172a');
            root.style.setProperty('--foreground', '#f8fafc');
            root.style.setProperty('--card', '#1e293b');
            root.style.setProperty('--card-foreground', '#f8fafc');
            root.style.setProperty('--popover', '#1e293b');
            root.style.setProperty('--popover-foreground', '#f8fafc');
            root.style.setProperty('--secondary', '#334155');
            root.style.setProperty('--secondary-foreground', '#cbd5e1');
            root.style.setProperty('--muted', '#475569');
            root.style.setProperty('--muted-foreground', '#94a3b8');
            root.style.setProperty('--accent', '#475569');
            root.style.setProperty('--accent-foreground', '#f8fafc');
            root.style.setProperty('--border', 'rgba(255, 255, 255, 0.1)');
            root.style.setProperty('--input', 'rgba(255, 255, 255, 0.15)');
            root.style.setProperty('--ring', '#fbbf24');
        } else {
            root.style.setProperty('--background', '#ffffff');
            root.style.setProperty('--foreground', '#0f172a');
            root.style.setProperty('--card', '#ffffff');
            root.style.setProperty('--card-foreground', '#0f172a');
            root.style.setProperty('--popover', '#ffffff');
            root.style.setProperty('--popover-foreground', '#0f172a');
            root.style.setProperty('--secondary', '#f8fafc');
            root.style.setProperty('--secondary-foreground', '#475569');
            root.style.setProperty('--muted', '#f1f5f9');
            root.style.setProperty('--muted-foreground', '#64748b');
            root.style.setProperty('--accent', '#f1f5f9');
            root.style.setProperty('--accent-foreground', '#0f172a');
            root.style.setProperty('--border', '#e2e8f0');
            root.style.setProperty('--input', '#e2e8f0');
            root.style.setProperty('--ring', '#f97316');
        }
    }

    updateThemeIcon() {
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) {
            if (this.theme === 'dark') {
                themeIcon.setAttribute('data-lucide', 'sun');
            } else {
                themeIcon.setAttribute('data-lucide', 'moon');
            }
            
            // Re-initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }

    applyLanguage() {
        const root = document.documentElement;
        const dir = this.language === 'ar' ? 'rtl' : 'ltr';
        root.dir = dir;
        root.lang = this.language;
        
        // Update language-specific content
        this.updateLanguageContent();
    }

    updateLanguageContent() {
        // Update text content based on language
        const elements = document.querySelectorAll('[data-lang-ar], [data-lang-en]');
        elements.forEach(element => {
            const arText = element.getAttribute('data-lang-ar');
            const enText = element.getAttribute('data-lang-en');
            
            if (this.language === 'ar' && arText) {
                element.textContent = arText;
            } else if (this.language === 'en' && enText) {
                element.textContent = enText;
            }
        });

        // Update placeholder text
        const inputs = document.querySelectorAll('input[data-placeholder-ar], input[data-placeholder-en]');
        inputs.forEach(input => {
            const arPlaceholder = input.getAttribute('data-placeholder-ar');
            const enPlaceholder = input.getAttribute('data-placeholder-en');
            
            if (this.language === 'ar' && arPlaceholder) {
                input.placeholder = arPlaceholder;
            } else if (this.language === 'en' && enPlaceholder) {
                input.placeholder = enPlaceholder;
            }
        });
    }

    setupThemeToggle() {
        const themeButtons = document.querySelectorAll('[data-theme-toggle]');
        themeButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.toggleTheme();
            });
        });
    }

    setupLanguageToggle() {
        const langButtons = document.querySelectorAll('[data-lang-toggle]');
        langButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.toggleLanguage();
            });
        });
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.theme);
        this.applyTheme();
        
        // Trigger custom event
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: this.theme }
        }));
    }

    toggleLanguage() {
        this.language = this.language === 'ar' ? 'en' : 'ar';
        localStorage.setItem('language', this.language);
        this.applyLanguage();
        
        // Trigger custom event
        window.dispatchEvent(new CustomEvent('languageChanged', {
            detail: { language: this.language }
        }));
    }

    // Get current theme
    getCurrentTheme() {
        return this.theme;
    }

    // Get current language
    getCurrentLanguage() {
        return this.language;
    }

    // Set theme programmatically
    setTheme(theme) {
        if (['light', 'dark'].includes(theme)) {
            this.theme = theme;
            localStorage.setItem('theme', this.theme);
            this.applyTheme();
        }
    }

    // Set language programmatically
    setLanguage(language) {
        if (['ar', 'en'].includes(language)) {
            this.language = language;
            localStorage.setItem('language', this.language);
            this.applyLanguage();
        }
    }
}

// Initialize theme manager
let themeManager;

document.addEventListener('DOMContentLoaded', () => {
    themeManager = new ThemeManager();
    
    // Make it globally available
    window.themeManager = themeManager;
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
