/* ===================================================
   HopeHands Foundation — Main JavaScript
   Navbar, Animations, Counters, Form Validation
   =================================================== */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Mobile Nav Toggle ── */
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      navToggle.classList.toggle('active');
      navLinks.classList.toggle('open');
    });
    // Close menu when a link is clicked
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navToggle.classList.remove('active');
        navLinks.classList.remove('open');
      });
    });
  }

  /* ── Navbar Scroll Effect ── */
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 60);
    });
    // Set initial state
    if (window.scrollY > 60) navbar.classList.add('scrolled');
  }

  /* ── Scroll Reveal (Intersection Observer) ── */
  const revealElements = document.querySelectorAll('.reveal');
  if (revealElements.length > 0) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    revealElements.forEach(el => revealObserver.observe(el));
  }

  /* ── Animated Stats Counter ── */
  const statNumbers = document.querySelectorAll('.stat-number[data-count]');
  if (statNumbers.length > 0) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    statNumbers.forEach(el => counterObserver.observe(el));
  }

  function animateCounter(el) {
    const target = parseInt(el.dataset.count, 10);
    const duration = 2000;
    const start = performance.now();

    function updateCount(timestamp) {
      const elapsed = timestamp - start;
      const progress = Math.min(elapsed / duration, 1);
      // Ease-out quad
      const eased = 1 - (1 - progress) * (1 - progress);
      const current = Math.floor(eased * target);
      el.textContent = current.toLocaleString() + (target >= 1000 ? '+' : '+');
      if (progress < 1) {
        requestAnimationFrame(updateCount);
      } else {
        el.textContent = target.toLocaleString() + '+';
      }
    }
    requestAnimationFrame(updateCount);
  }

  /* ── Donation Amount Buttons ── */
  const amountBtns = document.querySelectorAll('.amount-btn');
  const amountInput = document.getElementById('donationAmount');

  if (amountBtns.length > 0 && amountInput) {
    amountBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        amountBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        amountInput.value = btn.dataset.amount;
      });
    });
    // Deselect presets when typing custom amount
    amountInput.addEventListener('input', () => {
      amountBtns.forEach(b => b.classList.remove('active'));
    });
  }

  /* ── Form Validation ── */

  // Donation Form
  const donationForm = document.getElementById('donationForm');
  if (donationForm) {
    donationForm.addEventListener('submit', (e) => {
      let valid = true;
      clearErrors(donationForm);

      const name   = donationForm.querySelector('#donorName');
      const email  = donationForm.querySelector('#donorEmail');
      const amount = donationForm.querySelector('#donationAmount');

      if (!name.value.trim()) { showError(name); valid = false; }
      if (!isValidEmail(email.value)) { showError(email); valid = false; }
      if (!amount.value || parseInt(amount.value) < 1) { showError(amount); valid = false; }

      if (!valid) e.preventDefault();
    });
  }

  // Contact Form
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      let valid = true;
      clearErrors(contactForm);

      const name    = contactForm.querySelector('#contactName');
      const email   = contactForm.querySelector('#contactEmail');
      const subject = contactForm.querySelector('#contactSubject');
      const message = contactForm.querySelector('#contactMessage');

      if (!name.value.trim()) { showError(name); valid = false; }
      if (!isValidEmail(email.value)) { showError(email); valid = false; }
      if (!subject.value.trim()) { showError(subject); valid = false; }
      if (!message.value.trim()) { showError(message); valid = false; }

      if (!valid) e.preventDefault();
    });
  }

  function showError(input) {
    input.closest('.form-group').classList.add('has-error');
  }

  function clearErrors(form) {
    form.querySelectorAll('.form-group').forEach(g => g.classList.remove('has-error'));
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  /* ── Smooth Scroll for anchor links ── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const id = anchor.getAttribute('href');
      if (id !== '#') {
        e.preventDefault();
        const target = document.querySelector(id);
        if (target) target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

});
