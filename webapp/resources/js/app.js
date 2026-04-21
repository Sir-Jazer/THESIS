import './bootstrap';

import Alpine from 'alpinejs';

const THEME_STORAGE_KEY = 'portal_theme_preference';

window.portalShell = (initialTheme = 'light', themeEndpoint = null) => ({
	sidebarOpen: false,
	sidebarCollapsed: false,
	profileOpen: false,
	theme: initialTheme === 'dark' ? 'dark' : 'light',
	themeEndpoint,

	init() {
		const savedTheme = window.localStorage.getItem(THEME_STORAGE_KEY);

		if (savedTheme === 'light' || savedTheme === 'dark') {
			this.theme = savedTheme;
		}

		this.applyTheme(this.theme);
	},

	applyTheme(theme) {
		const resolvedTheme = theme === 'dark' ? 'dark' : 'light';

		this.theme = resolvedTheme;
		document.documentElement.classList.toggle('dark', resolvedTheme === 'dark');
		window.localStorage.setItem(THEME_STORAGE_KEY, resolvedTheme);
	},

	async toggleTheme() {
		const nextTheme = this.theme === 'dark' ? 'light' : 'dark';

		this.applyTheme(nextTheme);

		if (!this.themeEndpoint) {
			return;
		}

		const csrfToken = document
			.querySelector('meta[name="csrf-token"]')
			?.getAttribute('content');

		try {
			const response = await fetch(this.themeEndpoint, {
				method: 'PATCH',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'X-CSRF-TOKEN': csrfToken ?? '',
				},
				body: JSON.stringify({
					theme_preference: nextTheme,
				}),
			});

			if (!response.ok) {
				throw new Error('Failed to persist theme preference.');
			}

			const data = await response.json();

			if (data?.theme === 'light' || data?.theme === 'dark') {
				this.applyTheme(data.theme);
			}
		} catch (error) {
			console.warn('Failed to persist theme preference to backend:', error);
		}
	},
});

window.Alpine = Alpine;

Alpine.start();
