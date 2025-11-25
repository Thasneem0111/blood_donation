// Placeholder for future interactive features (mobile menu, auth modals)
// Keeping file minimal per current requirements.

// Example: Smooth scroll for internal anchor links
document.addEventListener('click', (e) => {
	const target = e.target.closest('a[href^="#"]');
	if (!target) return;
	// If link is for opening modals, handle separately
	if (target.classList.contains('open-signin')) {
		e.preventDefault();
		openModal('modal-signin');
		return;
	}
	if (target.classList.contains('open-signup')) {
		e.preventDefault();
		openModal('modal-signup');
		return;
	}
	const id = target.getAttribute('href').substring(1);
	const el = document.getElementById(id);
	if (el) {
		e.preventDefault();
		el.scrollIntoView({ behavior: 'smooth' });
	}
});

// Typing animation for hero heading
function typeHeading() {
	const h1 = document.getElementById('hero-heading');
	if (!h1) return;
	const span = h1.querySelector('.typed');
	const fullText = h1.getAttribute('data-text') || '';
	let i = 0;
	function type() {
		if (i <= fullText.length) {
			span.textContent = fullText.slice(0, i);
			i++;
			setTimeout(type, 70); // typing speed
		} else {
			// when typing finishes, mark heading to reveal underline animation
			h1.classList.add('in-view');
		}
	}
	type();
}
window.addEventListener('DOMContentLoaded', typeHeading);

// Reveal About blocks when they enter the viewport
function setupSectionObserver() {
	const items = document.querySelectorAll('.info-inner');
	if (!items || items.length === 0) return;
	const obs = new IntersectionObserver((entries, observer) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				entry.target.classList.add('in-view');
				observer.unobserve(entry.target);
			}
		});
	}, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
	items.forEach(i => obs.observe(i));
}
window.addEventListener('DOMContentLoaded', setupSectionObserver);

// Modal helpers
function openModal(id) {
	const modal = document.getElementById(id);
	if (!modal) return;
	modal.hidden = false;
	modal.setAttribute('aria-hidden', 'false');
	document.body.classList.add('no-scroll');
	// focus the first form control inside the modal for accessibility
	setTimeout(() => {
		const first = modal.querySelector('input, select, textarea, button');
		if (first) first.focus();
	}, 80);
}
function closeModal(el) {
	const modal = el.closest('.modal');
	if (!modal) return;
	modal.hidden = true;
	modal.setAttribute('aria-hidden', 'true');
	if (!document.querySelector('.modal:not([hidden])')) {
		document.body.classList.remove('no-scroll');
	}
}

document.addEventListener('click', (e) => {
	if (e.target.matches('[data-close]')) {
		e.preventDefault();
		closeModal(e.target);
	}
});

// Switch between sign-in and sign-up
document.addEventListener('click', (e) => {
	const a = e.target.closest('.switch-to-signup, .switch-to-signin');
	if (!a) return;
	e.preventDefault();
	// If user clicks "Don't have an account? Sign Up" inside sign-in modal,
	// close the sign-in modal and navigate to the interactive register section.
	if (a.classList.contains('switch-to-signup')) {
		const signinModal = document.getElementById('modal-signin');
		if (signinModal) {
			signinModal.hidden = true;
			signinModal.setAttribute('aria-hidden', 'true');
		}
		// ensure scrolling is restored when leaving a modal to the page
		document.body.classList.remove('no-scroll');
		// Scroll to interactive register section and focus first input
		const section = document.getElementById('interactive-register');
		if (section) {
			section.scrollIntoView({ behavior: 'smooth', block: 'start' });
			// optionally open donor form after scrolling for quicker registration
			const donorForm = document.getElementById('form-donor');
			if (donorForm) {
				// show the donor form so user can register immediately
				donorForm.hidden = false; donorForm.setAttribute('aria-hidden', 'false');
				const first = donorForm.querySelector('input, select, textarea');
				if (first) first.focus();
			}
		}
		return;
	}

	// If clicking "Already have an account? Sign In" inside sign-up modal, show signin modal
	if (a.classList.contains('switch-to-signin')) {
		const from = document.getElementById('modal-signup');
		const to = document.getElementById('modal-signin');
		if (from) { from.hidden = true; from.setAttribute('aria-hidden', 'true'); }
		if (to) { to.hidden = false; to.setAttribute('aria-hidden', 'false'); }
		// ensure page remains locked (modal visible)
		document.body.classList.add('no-scroll');
		return;
	}
});

// Password toggle
document.addEventListener('click', (e) => {
	const btn = e.target.closest('.toggle-password');
	if (!btn) return;
	const id = btn.getAttribute('data-target');
	const input = document.getElementById(id);
	if (!input) return;
	const showing = input.getAttribute('type') === 'text';
	input.setAttribute('type', showing ? 'password' : 'text');
	btn.classList.toggle('showing', !showing);
});

// Blood group show/hide based on user type
function setupUserType() {
	const typeSel = document.getElementById('userType');
	const groupRow = document.getElementById('bloodGroupRow');
	if (!typeSel || !groupRow) return;
	const update = () => {
		const show = typeSel.value === 'donor';
		groupRow.style.display = show ? '' : 'none';
	};
	typeSel.addEventListener('change', update);
	update();
}
window.addEventListener('DOMContentLoaded', setupUserType);

// Inline registration form toggle (Donor / Seeker)
function setupInlineRegistration() {
	const showDonor = document.getElementById('show-donor-form');
	const showSeeker = document.getElementById('show-seeker-form');
	const formDonor = document.getElementById('form-donor');
	const formSeeker = document.getElementById('form-seeker');

	// Ensure forms are hidden by default on load
	if (formDonor) { formDonor.hidden = true; formDonor.setAttribute('aria-hidden', 'true'); }
	if (formSeeker) { formSeeker.hidden = true; formSeeker.setAttribute('aria-hidden', 'true'); }

	function openForm(form) {
		if (!form) return;
		// hide both first
		[formDonor, formSeeker].forEach(f => {
			if (f && f !== form) {
				f.hidden = true; f.setAttribute('aria-hidden', 'true');
			}
		});
		form.hidden = false; form.setAttribute('aria-hidden', 'false');
		const first = form.querySelector('input, select, textarea');
		if (first) first.focus();
		// scroll into view smoothly
		form.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	if (showDonor) showDonor.addEventListener('click', (e) => { e.preventDefault(); openForm(formDonor); });
	if (showSeeker) showSeeker.addEventListener('click', (e) => { e.preventDefault(); openForm(formSeeker); });

	// close inline handlers (buttons with data-close-inline)
	document.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-close-inline]');
		if (!btn) return;
		const id = btn.getAttribute('data-close-inline');
		const f = document.getElementById(id);
		if (f) { f.hidden = true; f.setAttribute('aria-hidden', 'true'); }
	});

	// When inline forms submit, POST to register.php and handle response
	[formDonor, formSeeker].forEach(f => {
		if (!f) return;
		f.addEventListener('submit', async (ev) => {
			ev.preventDefault();
			const data = new FormData(f);
			try {
				const res = await fetch('register.php', { method: 'POST', body: data });
				const json = await res.json();
				// show bootstrap alert near the form
				showFormAlert(f, json.success ? 'success' : 'danger', json.message || 'Unexpected response');
				if (json.success && json.redirect) {
					// short delay then redirect and replace history so back doesn't return to the form
					setTimeout(() => { window.location.replace(json.redirect); }, 1400);
				}
				if (!json.success) {
					// keep form open for correction
				} else {
					// hide form on success to avoid duplicate submissions
					f.hidden = true; f.setAttribute('aria-hidden', 'true');
				}
			} catch (err) {
				showFormAlert(f, 'danger', 'Network error. Please try again.');
				console.error(err);
			}
		});
	});
}
window.addEventListener('DOMContentLoaded', setupInlineRegistration);

// Sign-up modal submission (the main sign up form)
document.addEventListener('submit', async (e) => {
	const form = e.target.closest('#form-signup');
	if (!form) return;
	e.preventDefault();
	const data = new FormData(form);
	try {
		const res = await fetch('register.php', { method: 'POST', body: data });
		const json = await res.json();
		// show an alert inside the modal
		const modal = document.getElementById('modal-signup');
		showFormAlert(modal ? modal.querySelector('.modal-content') : form, json.success ? 'success' : 'danger', json.message || 'Unexpected response');
		if (json.success && json.redirect) {
			setTimeout(() => { window.location.replace(json.redirect); }, 1400);
		}
	} catch (err) {
		showFormAlert(form, 'danger', 'Network error. Please try again.');
		console.error(err);
	}
});

// Sign-in modal submission: send email to login.php which responds with redirect
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('#form-signin');
    if (!form) return;
    e.preventDefault();
    const data = new FormData(form);
    try {
        const res = await fetch('login.php', { method: 'POST', body: data });
        const json = await res.json();
        const modal = document.getElementById('modal-signin');
        showFormAlert(modal ? modal.querySelector('.modal-content') : form, json.success ? 'success' : 'danger', json.message || 'Unexpected response');
        if (json.success && json.redirect) {
			setTimeout(() => { window.location.replace(json.redirect); }, 1000);
        }
    } catch (err) {
        showFormAlert(form, 'danger', 'Network error. Please try again.');
        console.error(err);
    }
});

function showFormAlert(container, type, message) {
	if (!container) container = document.body;
	// remove any previous alerts in this container
	const prev = container.querySelector('.form-alert');
	if (prev) prev.remove();
	const div = document.createElement('div');
	div.className = `form-alert alert alert-${type} alert-dismissible fade show`;
	div.role = 'alert';
	div.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
	container.insertBefore(div, container.firstChild);
	// auto remove after 4s
	setTimeout(() => { div.classList.remove('show'); div.remove(); }, 5000);
}


