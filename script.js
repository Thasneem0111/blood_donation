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
	document.body.classList.add('no-scroll');
}
function closeModal(el) {
	const modal = el.closest('.modal');
	if (!modal) return;
	modal.hidden = true;
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
	const toSignup = a.classList.contains('switch-to-signup');
	const from = document.getElementById(toSignup ? 'modal-signin' : 'modal-signup');
	const to = document.getElementById(toSignup ? 'modal-signup' : 'modal-signin');
	if (from) from.hidden = true;
	if (to) to.hidden = false;
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


