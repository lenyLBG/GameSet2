import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Custom modal + registration JS
document.addEventListener('DOMContentLoaded', function () {
	try {
		// Pills styling: keep active pill white
		const loginBtn  = document.getElementById('tab-login');
		const signupBtn = document.getElementById('tab-signup');

		[loginBtn, signupBtn].forEach(b => b && b.setAttribute('type', 'button'));
		document.querySelectorAll('[data-bs-toggle="pill"]').forEach(btn => {
			btn.addEventListener('shown.bs.tab', function (e) {
				if (!loginBtn || !signupBtn) return;
				loginBtn.classList.remove('bg-white','text-dark');
				signupBtn.classList.remove('bg-white','text-dark');
				loginBtn.classList.add('text-secondary');
				signupBtn.classList.add('text-secondary');
				const activeBtn = e.target;
				activeBtn.classList.add('bg-white','text-dark');
				activeBtn.classList.remove('text-secondary');
			});
		});

		// AJAX registration form submit inside modal
		document.addEventListener('submit', function (e) {
			const form = e.target;
			if (form && form.id === 'form-register') {
				e.preventDefault();

				const url = form.getAttribute('action') || window.location.pathname;
				const data = new FormData(form);

				fetch(url, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: data
				}).then(async resp => {
					if (resp.ok) {
						const json = await resp.json();
						if (json.redirect) {
							window.location.href = json.redirect;
						} else {
							window.location.reload();
						}
						return;
					}

					// On validation error, server returns HTML fragment to inject
					if (resp.status === 400) {
						const json = await resp.json().catch(() => null);
						if (json && json.html) {
							const container = document.querySelector('#pane-signup');
							if (container) {
								container.innerHTML = json.html;
								// re-open the signup pill
								const signupTab = document.querySelector('#tab-signup');
								if (signupTab) signupTab.click();
							}
						}
					}
				}).catch(err => {
					console.error('Registration request failed', err);
				});
			}
		});
	} catch (err) {
		console.warn('Modal registration JS failed to init', err);
	}
});
