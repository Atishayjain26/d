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

  /* ── Real-time Cause Data from API ── */
  const causeCards = document.querySelectorAll('.cause-card[data-cause]');
  if (causeCards.length > 0) {
    fetch('php/api_causes.php')
      .then(res => res.json())
      .then(data => {
        if (!data.success) return;
        causeCards.forEach(card => {
          const cause = card.dataset.cause;
          const goal  = parseFloat(card.dataset.goal) || 0;
          const raised = data.causes[cause] ? data.causes[cause].raised : 0;
          const percent = goal > 0 ? Math.min((raised / goal) * 100, 100) : 0;

          // Update raised amount
          const raisedEl = card.querySelector('.cause-raised');
          if (raisedEl) raisedEl.textContent = '₹' + formatIndian(raised);

          // Update progress bar
          const progressFill = card.querySelector('.progress-fill');
          if (progressFill) progressFill.style.width = percent.toFixed(1) + '%';
        });
      })
      .catch(() => { /* silently fail — hardcoded fallback stays */ });
  }

  /** Format number in Indian numbering system (e.g. 18,00,000) */
  function formatIndian(num) {
    const n = Math.round(num);
    const s = n.toString();
    if (s.length <= 3) return s;
    const last3 = s.slice(-3);
    const rest  = s.slice(0, -3);
    const formatted = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',');
    return formatted + ',' + last3;
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
    const nameField   = donationForm.querySelector('#donorName');
    const emailField  = donationForm.querySelector('#donorEmail');
    const phoneField  = donationForm.querySelector('#donorPhone');
    const amountField = donationForm.querySelector('#donationAmount');

    // Real-time blur validation for email
    if (emailField) {
      emailField.addEventListener('blur', () => {
        if (emailField.value.trim() && !isValidEmail(emailField.value)) {
          showError(emailField);
        }
      });
      emailField.addEventListener('input', () => {
        if (isValidEmail(emailField.value)) {
          emailField.closest('.form-group').classList.remove('has-error');
        }
      });
    }

    // Real-time blur validation for phone
    if (phoneField) {
      phoneField.addEventListener('blur', () => {
        if (phoneField.value.trim() && !isValidPhone(phoneField.value)) {
          showError(phoneField);
        }
      });
      phoneField.addEventListener('input', () => {
        if (!phoneField.value.trim() || isValidPhone(phoneField.value)) {
          phoneField.closest('.form-group').classList.remove('has-error');
        }
      });
    }

    donationForm.addEventListener('submit', (e) => {
      let valid = true;
      clearErrors(donationForm);

      if (!nameField.value.trim()) { showError(nameField); valid = false; }
      if (!isValidEmail(emailField.value)) { showError(emailField); valid = false; }
      if (phoneField.value.trim() && !isValidPhone(phoneField.value)) { showError(phoneField); valid = false; }
      if (!amountField.value || parseInt(amountField.value) < 1) { showError(amountField); valid = false; }

      if (!valid) e.preventDefault();
    });
  }

  // Contact Form
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    const cName    = contactForm.querySelector('#contactName');
    const cEmail   = contactForm.querySelector('#contactEmail');
    const cSubject = contactForm.querySelector('#contactSubject');
    const cMessage = contactForm.querySelector('#contactMessage');

    // Real-time blur validation for email
    if (cEmail) {
      cEmail.addEventListener('blur', () => {
        if (cEmail.value.trim() && !isValidEmail(cEmail.value)) {
          showError(cEmail);
        }
      });
      cEmail.addEventListener('input', () => {
        if (isValidEmail(cEmail.value)) {
          cEmail.closest('.form-group').classList.remove('has-error');
        }
      });
    }

    contactForm.addEventListener('submit', (e) => {
      let valid = true;
      clearErrors(contactForm);

      if (!cName.value.trim()) { showError(cName); valid = false; }
      if (!isValidEmail(cEmail.value)) { showError(cEmail); valid = false; }
      if (!cSubject.value.trim()) { showError(cSubject); valid = false; }
      if (!cMessage.value.trim()) { showError(cMessage); valid = false; }

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

  function isValidPhone(phone) {
    // Strip spaces, dashes, and parentheses
    const cleaned = phone.replace(/[\s\-()]/g, '');
    // Accept: 10 digits, or +91/91/0 prefix + 10 digits
    return /^(?:\+91|91|0)?[6-9]\d{9}$/.test(cleaned);
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
