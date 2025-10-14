import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Modal helper: centralize modal/pills behavior previously inline in modal partial
document.addEventListener('DOMContentLoaded', function () {
	try {
		const modalEl = document.getElementById('modal-login');
		if (!modalEl) return;

		const loginBtn = document.getElementById('tab-login');
		const signupBtn = document.getElementById('tab-signup');

		// ensure pills have type=button
		[loginBtn, signupBtn].forEach(b => b && b.setAttribute('type', 'button'));

		document.querySelectorAll('[data-bs-toggle="pill"]').forEach(btn => {
			btn.addEventListener('shown.bs.tab', function (e) {
				if (loginBtn) {
					loginBtn.classList.remove('bg-white','text-dark');
					loginBtn.classList.add('text-secondary');
				}
				if (signupBtn) {
					signupBtn.classList.remove('bg-white','text-dark');
					signupBtn.classList.add('text-secondary');
				}

				const activeBtn = e.target;
				if (activeBtn) {
					activeBtn.classList.add('bg-white','text-dark');
					activeBtn.classList.remove('text-secondary');
				}
			});
		});

		const content = modalEl.querySelector('.modal-content');
		if (content) {
			['click', 'mousedown', 'mouseup'].forEach(evt =>
				content.addEventListener(evt, e => e.stopPropagation())
			);
		}

		modalEl.addEventListener('hide.bs.modal', function (e) {
			const active = document.activeElement;
			if (active && active.closest('[data-bs-dismiss="modal"]')) return;
			if (active && (active === loginBtn || active === signupBtn)) {
				e.preventDefault();
				return;
			}
			if (active && modalEl.contains(active) && (
					active.matches('input, select, textarea, button, label, [role="tab"]') ||
					active.closest('form')
				)) {
				e.preventDefault();
			}
		});
	} catch (err) {
		// Avoid breaking other scripts — log silently in dev console
		if (typeof console !== 'undefined' && console.error) console.error('Modal init error', err);
	}
});
