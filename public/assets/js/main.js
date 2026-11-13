/**
 * HousePet — main.js
 * UI interactions: menu, flash, upload preview, validation, dropdowns, smooth scroll
 */

(function () {
    'use strict';

    /* ──────────────────────────────────────────
       1. Hamburger menu toggle
    ────────────────────────────────────────── */
    const hamburger = document.getElementById('hamburger');
    const navLinks  = document.getElementById('navLinks');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            const isOpen = navLinks.classList.toggle('open');
            hamburger.classList.toggle('open', isOpen);
            hamburger.setAttribute('aria-expanded', String(isOpen));
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('open');
                hamburger.classList.remove('open');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Esc
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                navLinks.classList.remove('open');
                hamburger.classList.remove('open');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ──────────────────────────────────────────
       2. Navbar scroll shadow
    ────────────────────────────────────────── */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 10);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ──────────────────────────────────────────
       3. Flash messages — auto-dismiss & close button
    ────────────────────────────────────────── */
    function dismissAlert(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        el.style.transition = 'opacity .3s, transform .3s';
        setTimeout(() => el.remove(), 350);
    }

    const flashMsg = document.getElementById('flashMsg');
    if (flashMsg) {
        // Auto-dismiss after 5 s
        setTimeout(() => dismissAlert(flashMsg), 5000);

        // Close button
        const closeBtn = flashMsg.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => dismissAlert(flashMsg));
        }
    }

    /* ──────────────────────────────────────────
       4. Dropdown toggle (user menu)
    ────────────────────────────────────────── */
    const userDropdown     = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdown && userDropdownMenu) {
        const wrapper = userDropdown.closest('.nav-dropdown');

        userDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.toggle('open');
            userDropdown.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', () => {
            wrapper.classList.remove('open');
            userDropdown.setAttribute('aria-expanded', 'false');
        });

        userDropdownMenu.addEventListener('click', (e) => e.stopPropagation());
    }

    /* ──────────────────────────────────────────
       5. Image upload preview
    ────────────────────────────────────────── */
    document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
        const previewId = input.dataset.preview;
        const preview   = document.getElementById(previewId);
        if (!preview) return;

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                showInputError(input, 'Apenas imagens são permitidas (JPEG, PNG, GIF, WEBP).');
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showInputError(input, 'Imagem muito grande. Tamanho máximo: 5 MB.');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    });

    /* ──────────────────────────────────────────
       6. Front-end form validation
    ────────────────────────────────────────── */
    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach((field) => {
                clearInputError(field);
                if (!field.value.trim()) {
                    showInputError(field, 'Este campo é obrigatório.');
                    valid = false;
                }
            });

            // Email validation
            form.querySelectorAll('input[type="email"]').forEach((field) => {
                if (field.value && !isValidEmail(field.value)) {
                    showInputError(field, 'Informe um e-mail válido.');
                    valid = false;
                }
            });

            // Password match
            const pass1 = form.querySelector('[data-match-password]');
            const pass2 = pass1 ? form.querySelector('[data-match-confirm]') : null;
            if (pass1 && pass2 && pass1.value !== pass2.value) {
                showInputError(pass2, 'As senhas não coincidem.');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    });

    function showInputError(input, message) {
        input.classList.add('is-invalid');
        let err = input.nextElementSibling;
        if (!err || !err.classList.contains('form-error')) {
            err = document.createElement('span');
            err.className = 'form-error';
            input.parentNode.insertBefore(err, input.nextSibling);
        }
        err.textContent = message;
    }

    function clearInputError(input) {
        input.classList.remove('is-invalid', 'is-valid');
        const err = input.nextElementSibling;
        if (err && err.classList.contains('form-error')) err.remove();
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /* ──────────────────────────────────────────
       7. Confirm before delete
    ────────────────────────────────────────── */
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (e) => {
            const msg = el.dataset.confirm || 'Tem certeza que deseja continuar?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const msg = form.dataset.confirm || 'Tem certeza que deseja continuar?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    /* ──────────────────────────────────────────
       8. Smooth scroll for anchor links
    ────────────────────────────────────────── */
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', (e) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* ──────────────────────────────────────────
       9. Upload area drag-and-drop visual feedback
    ────────────────────────────────────────── */
    document.querySelectorAll('.upload-area').forEach((area) => {
        const input = area.querySelector('input[type="file"]');
        if (!input) return;

        area.addEventListener('click', () => input.click());
        area.addEventListener('dragover', (e) => { e.preventDefault(); area.classList.add('drag-over'); });
        area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    /* ──────────────────────────────────────────
       10. Character count for textareas
    ────────────────────────────────────────── */
    document.querySelectorAll('textarea[data-maxlength]').forEach((ta) => {
        const max     = parseInt(ta.dataset.maxlength, 10);
        const counter = document.createElement('span');
        counter.className   = 'form-hint text-sm';
        counter.style.float = 'right';
        ta.parentNode.insertBefore(counter, ta.nextSibling);

        const update = () => {
            const remaining = max - ta.value.length;
            counter.textContent = `${remaining} caracteres restantes`;
            counter.style.color = remaining < 20 ? 'var(--color-danger)' : '';
        };
        ta.addEventListener('input', update);
        update();
    });

})();
