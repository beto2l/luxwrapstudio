/* ===== LuxWrap Studio - Main JavaScript ===== */
/* Version 2.0 - Restructured */

/* ===== PORTFOLIO DATA LOADER ===== */
let portfolioData = null;

async function loadPortfolio() {
    try {
        const response = await fetch('scripts/get-portfolio.php');
        if (response.ok) {
            portfolioData = await response.json();
            renderPortfolio(portfolioData.projects);
        }
    } catch (e) {
        // If PHP is not available, use embedded fallback data
        console.log('PHP backend not available, using static portfolio data.');
    }
}

function renderPortfolio(projects) {
    const container = document.getElementById('portfolioDynamic');
    if (!container || !projects || projects.length === 0) return;

    container.innerHTML = '';
    const allImages = [];

    projects.forEach(project => {
        const div = document.createElement('div');
        div.className = 'portfolio-project reveal active';

        const nameKey = currentLang === 'es' ? 'name_es' : 'name_en';
        const descKey = currentLang === 'es' ? 'description_es' : 'description_en';

        let imagesHTML = '';
        project.images.forEach(img => {
            const idx = allImages.length;
            allImages.push(img.path);
            imagesHTML += `
                <div class="portfolio-item" onclick="openLightbox(${idx})">
                    <img src="${img.path}" alt="${project[nameKey]} - LuxWrap Studio" loading="lazy">
                    <div class="portfolio-overlay">
                        <span class="tag">${project[nameKey]}</span>
                    </div>
                </div>`;
        });

        div.innerHTML = `
            <h3 class="portfolio-project-title">${project[nameKey]}</h3>
            <p class="portfolio-project-desc">${project[descKey]}</p>
            <div class="portfolio-grid">${imagesHTML}</div>`;

        container.appendChild(div);
    });

    // Update lightbox images with dynamic data
    if (allImages.length > 0) {
        lightboxImages.length = 0;
        allImages.forEach(img => lightboxImages.push(img));
    }
}

/* ===== LIGHTBOX ===== */
const lightboxImages = [
    'assets/portfolio/angels-roofing/portfolio-01-angels-roofing-truck-side.jpg',
    'assets/portfolio/angels-roofing/portfolio-03-angels-roofing-truck-detail.jpg',
    'assets/portfolio/angels-roofing/portfolio-04-angels-roofing-truck-hood.jpg',
    'assets/portfolio/doctor-electric/portfolio-11-doctor-electric-van-side.jpg',
    'assets/portfolio/doctor-electric/portfolio-13-doctor-electric-van-full.jpg',
    'assets/portfolio/doctor-electric/portfolio-14-doctor-electric-van-angle.jpg',
    'assets/portfolio/dulce-salado/portfolio-16-dulce-salado-van-front.jpg',
    'assets/portfolio/dulce-salado/portfolio-17-dulce-salado-van-rear.jpg',
    'assets/portfolio/perez-concrete/portfolio-18-perez-concrete-trailer-back.jpg',
    'assets/portfolio/perez-concrete/portfolio-20-perez-concrete-trailer-side.jpg',
    'assets/portfolio/perez-concrete/portfolio-21-perez-concrete-trailer-angle.jpg',
    'assets/portfolio/golden-restoration/portfolio-golden-cards.jpeg',
    'assets/portfolio/golden-restoration/portfolio-golden-polo.jpeg',
    'assets/portfolio/panchitos/portfolio-panchitos-model.jpg'
];
let currentLightboxIndex = 0;

function openLightbox(index) {
    currentLightboxIndex = index;
    const lightbox = document.getElementById('lightbox');
    const img = document.getElementById('lightboxImg');
    img.src = lightboxImages[index];
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
function navigateLightbox(dir) {
    currentLightboxIndex = (currentLightboxIndex + dir + lightboxImages.length) % lightboxImages.length;
    document.getElementById('lightboxImg').src = lightboxImages[currentLightboxIndex];
}
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});
document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('lightbox');
    if (!lb.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navigateLightbox(-1);
    if (e.key === 'ArrowRight') navigateLightbox(1);
});

/* ===== LANGUAGE TOGGLE ===== */
let currentLang = 'en';

function setLang(lang) {
    currentLang = lang;
    document.documentElement.lang = lang;

    document.getElementById('btnEN').classList.toggle('active', lang === 'en');
    document.getElementById('btnES').classList.toggle('active', lang === 'es');

    document.querySelectorAll('[data-en]').forEach(el => {
        const text = el.getAttribute('data-' + lang);
        if (text) {
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                // skip — handled by placeholder
            } else {
                el.innerHTML = text;
            }
        }
    });

    document.querySelectorAll('[data-placeholder-en]').forEach(el => {
        const ph = el.getAttribute('data-placeholder-' + lang);
        if (ph) el.placeholder = ph;
    });

    document.querySelectorAll('select option[data-en]').forEach(opt => {
        const text = opt.getAttribute('data-' + lang);
        if (text) opt.textContent = text;
    });

    // Re-render dynamic portfolio if loaded
    if (portfolioData) {
        renderPortfolio(portfolioData.projects);
    }
}

/* ===== MOBILE MENU ===== */
function toggleMenu() {
    const nav = document.getElementById('navLinks');
    const hamburger = document.getElementById('hamburger');
    nav.classList.toggle('mobile-open');
    hamburger.classList.toggle('active');
    document.body.style.overflow = nav.classList.contains('mobile-open') ? 'hidden' : '';
}
function closeMenu() {
    document.getElementById('navLinks').classList.remove('mobile-open');
    document.getElementById('hamburger').classList.remove('active');
    document.body.style.overflow = '';
}

/* ===== NAVBAR SCROLL EFFECT ===== */
window.addEventListener('scroll', () => {
    const nav = document.getElementById('navbar');
    nav.classList.toggle('scrolled', window.scrollY > 50);
});

/* ===== SCROLL REVEAL ===== */
const revealElements = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
revealElements.forEach(el => revealObserver.observe(el));

/* ===== FORM HANDLING ===== */
const form = document.getElementById('contactForm');
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Check honeypot
        const hp = form.querySelector('[name="website_url"]');
        if (hp && hp.value) return;

        const btn = form.querySelector('.btn-submit');
        const originalHTML = btn.innerHTML;
        const errorDiv = document.getElementById('formError');

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
        if (errorDiv) errorDiv.classList.remove('show');

        // Validate email
        const emailInput = form.querySelector('#email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value)) {
            if (errorDiv) {
                errorDiv.textContent = currentLang === 'es'
                    ? 'Por favor ingresa un email válido.'
                    : 'Please enter a valid email address.';
                errorDiv.classList.add('show');
            }
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            return;
        }

        try {
            // Web3Forms free plan requires client-side submission. The key identifies the form inbox;
            // sensitive deploy/admin secrets remain in .env on the server.
            const formData = new FormData(form);

            // Add reCAPTCHA token if available
            if (typeof grecaptcha !== 'undefined' && typeof RECAPTCHA_SITE_KEY !== 'undefined') {
                try {
                    const token = await grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'contact'});
                    formData.append('g-recaptcha-response', token);
                } catch (recapErr) {
                    console.log('reCAPTCHA not available, proceeding without it.');
                }
            }

            formData.append('access_key', 'd3e22b1d-c74f-4e6b-b8c7-3696d39e20fc');
            formData.append('subject', 'New Quote Request — LuxWrap Studio Website');
            formData.append('from_name', 'LuxWrap Studio Website');

            const response = await fetch('https://api.web3forms.com/submit', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json().catch(() => ({}));

            if (result.success === true || (response.ok && result.success !== false)) {
                showContactSuccess(form);
            } else {
                throw new Error(result.message || 'Form submission failed');
            }
        } catch (err) {
            console.error('Contact form error:', err);
            if (errorDiv) {
                errorDiv.textContent = currentLang === 'es'
                    ? 'No pudimos confirmar el envío en pantalla. Si ya recibiste confirmación por correo, puedes ignorar este aviso; si no, llámanos al (859) 636-7294.'
                    : 'We could not confirm the submission on screen. If you already received email confirmation, you can ignore this notice; otherwise, call us at (859) 636-7294.';
                errorDiv.classList.add('show');
            }
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    });
}

function showContactSuccess(form) {
    form.style.display = 'none';
    const successBox = document.getElementById('formSuccess');
    if (successBox) successBox.classList.add('show');
    setLang(currentLang);
}

function openMailtoFallback(form) {
    const name = form.name.value;
    const email = form.email.value;
    const phone = form.phone.value;
    const vehicle = form.vehicle.value;
    const message = form.message.value;
    const body = `Name: ${name}%0AEmail: ${email}%0APhone: ${phone}%0AVehicle: ${vehicle}%0AMessage: ${message}`;
    window.location.href = `mailto:luxwrapstudioky@gmail.com?subject=Quote Request - LuxWrap Studio&body=${body}`;
}

/* ===== SMOOTH SCROLL (Safari fallback) ===== */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-height')) || 70;
            const y = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    });
});

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', () => {
    // Try to load dynamic portfolio
    loadPortfolio();
});