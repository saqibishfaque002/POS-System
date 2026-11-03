import { ref } from 'vue';

// Shared module-level singleton state across all components
const isDark = ref(false);
let isInitialized = false;

/**
 * Apply the theme to documentElement and persist in localStorage
 */
export const applyTheme = (dark) => {
    isDark.value = Boolean(dark);
    if (typeof document !== 'undefined') {
        if (dark) {
            document.documentElement.classList.add('dark');
            try {
                localStorage.setItem('theme', 'dark');
            } catch (e) {}
        } else {
            document.documentElement.classList.remove('dark');
            try {
                localStorage.setItem('theme', 'light');
            } catch (e) {}
        }
    }
};

/**
 * Initialize theme from localStorage or system preference
 */
export const initTheme = () => {
    if (typeof window === 'undefined') return;

    try {
        const savedTheme = localStorage.getItem('theme');
        let shouldBeDark = false;

        if (savedTheme === 'dark') {
            shouldBeDark = true;
        } else if (savedTheme === 'light') {
            shouldBeDark = false;
        } else {
            // No explicit user selection saved: fallback to system OS preference
            shouldBeDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        applyTheme(shouldBeDark);

        // Setup storage listener for multi-tab synchronization (once)
        if (!isInitialized) {
            isInitialized = true;
            window.addEventListener('storage', (e) => {
                if (e.key === 'theme') {
                    if (e.newValue === 'dark') {
                        applyTheme(true);
                    } else if (e.newValue === 'light') {
                        applyTheme(false);
                    }
                }
            });

            // Listen for system preference changes only if user hasn't chosen an explicit preference
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    const explicitTheme = localStorage.getItem('theme');
                    if (!explicitTheme) {
                        applyTheme(e.matches);
                    }
                });
            }
        }
    } catch (e) {
        // Handle private browsing or restricted localStorage
        if (typeof document !== 'undefined') {
            isDark.value = document.documentElement.classList.contains('dark');
        }
    }
};

/**
 * Toggle between light and dark modes
 */
export const toggleTheme = () => {
    applyTheme(!isDark.value);
};

export function useTheme() {
    return {
        isDark,
        initTheme,
        applyTheme,
        toggleTheme,
    };
}
