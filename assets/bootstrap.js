import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Custom modal + registration JS
document.addEventListener('DOMContentLoaded', () => {
	try {
		const loginModalEl = document.getElementById('modal-login');
		const loginTabBtn = document.getElementById('tab-login');
		const signupTabBtn = document.getElementById('tab-signup');

		async function loadRegistrationPaneIfNeeded() {
			try {
				const pane = document.querySelector('#pane-signup');
				if (!pane) return;
				// If already loaded, skip
				if (pane.querySelector('#form-register')) return;
				const url = loginModalEl?.dataset?.registerUrl;
				if (!url) return;
				const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
				if (!resp.ok) return;
				const html = await resp.text();
				pane.innerHTML = html;
			} catch (e) {
				console.warn('Failed loading registration pane', e);
			}
		}

		// Make sure tab buttons are real buttons
		[loginTabBtn, signupTabBtn].forEach(b => b && b.setAttribute('type', 'button'));

		// helper to style active pill
		function stylePills(activeBtn) {
			if (!loginTabBtn || !signupTabBtn) return;
			loginTabBtn.classList.remove('bg-white', 'text-dark');
			signupTabBtn.classList.remove('bg-white', 'text-dark');
			loginTabBtn.classList.add('text-secondary');
			signupTabBtn.classList.add('text-secondary');
			if (activeBtn) {
				activeBtn.classList.add('bg-white', 'text-dark');
				activeBtn.classList.remove('text-secondary');
			}
		}

		document.querySelectorAll('[data-bs-toggle="pill"]').forEach(btn => {
			btn.addEventListener('shown.bs.tab', (e) => stylePills(e.target));
		});

		// When the modal opens, ensure the active pill visual is correct and prepare signup pane
		if (loginModalEl) {
			loginModalEl.addEventListener('shown.bs.modal', async () => {
				const active = document.querySelector('.nav-pills .nav-link.active');
				stylePills(active || loginTabBtn);
				// Preload signup content in background
				loadRegistrationPaneIfNeeded();
			});
		}

		// Load signup content when user switches to signup tab
		if (signupTabBtn) {
			signupTabBtn.addEventListener('click', () => loadRegistrationPaneIfNeeded());
		}

		// Handle AJAX registration submit inside modal
		document.addEventListener('submit', (e) => {
			const form = e.target;
			if (!(form && form.id === 'form-register')) return;
			e.preventDefault();

			const url = form.getAttribute('action') || window.location.pathname;
			const data = new FormData(form);

			fetch(url, {
				method: 'POST',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				body: data,
			}).then(async (resp) => {
				if (resp.ok) {
					const json = await resp.json().catch(() => null);
					if (json && json.redirect) {
						window.location.href = json.redirect;
						return;
					}
					window.location.reload();
					return;
				}

				if (resp.status === 400) {
					const json = await resp.json().catch(() => null);
					if (json && json.html) {
						const pane = document.querySelector('#pane-signup');
						if (pane) {
							pane.innerHTML = json.html;
							// Activate signup pill
							const tab = document.querySelector('#tab-signup');
							if (tab) tab.click();
						}
					}
				}
			}).catch((err) => {
				console.error('Registration AJAX failed', err);
			});
		});
	} catch (err) {
		console.warn('Modal registration JS init error', err);
	}
});
