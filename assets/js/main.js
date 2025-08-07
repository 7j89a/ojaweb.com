document.addEventListener('DOMContentLoaded', () => {
    const lightBulb = document.getElementById('light-bulb');
    if (lightBulb) {
        const pullChain = lightBulb.querySelector('.pull-chain');

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        }

        let isDragging = false;
        let wasDragged = false;
        const dragThreshold = 5;
        let startPos = { x: 0, y: 0 };

        lightBulb.addEventListener('mousedown', (e) => {
            e.preventDefault();
            isDragging = true;
            wasDragged = false;
            startPos = { x: e.clientX, y: e.clientY };
            pullChain.style.transition = 'none';
        });

let isMobile = window.matchMedia("(max-width: 768px)").matches;

document.addEventListener('mousemove', (e) => {
    if (isMobile) return; // Disable mousemove animation on mobile to reduce lag
    if (!isDragging) return;

    const deltaX = e.clientX - startPos.x;
    const deltaY = e.clientY - startPos.y;
    if (!wasDragged && (Math.abs(deltaX) > dragThreshold || Math.abs(deltaY) > dragThreshold)) {
        wasDragged = true;
    }

    const rect = lightBulb.getBoundingClientRect();
    const anchorX = rect.left + rect.width / 2;
    const anchorY = rect.top + 44;

    const mouseX = e.clientX;
    const mouseY = e.clientY;

    const angleRad = Math.atan2(mouseY - anchorY, mouseX - anchorX);
    let angleDeg = angleRad * (180 / Math.PI) - 90;
    angleDeg = Math.max(-80, Math.min(80, angleDeg));

    const distance = Math.sqrt(Math.pow(mouseX - anchorX, 2) + Math.pow(mouseY - anchorY, 2));
    const baseHeight = 40;
    const scaleY = Math.min(1.8, Math.max(0.5, distance / baseHeight));
    pullChain.style.transform = `rotate(${angleDeg}deg) scaleY(${scaleY})`;
});

        document.addEventListener('mouseup', () => {
            if (!isDragging) return;
            isDragging = false;
            pullChain.style.transition = 'transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55)';
            pullChain.style.transform = '';
            toggleTheme();
            pullChain.classList.add('pulled');
            setTimeout(() => pullChain.classList.remove('pulled'), 300);
        });

        // Load initial theme
        applyTheme(localStorage.getItem('theme') || 'dark');
    }


    // --- Hamburger Menu Control ---
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (navToggle) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            navToggle.classList.toggle('open');
        });
    }

    // --- Form Submission Experience ---
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading"></span>';
            }
        });
    });
    
    // --- Flash Message Auto-hide ---
    setTimeout(() => {
        const messages = document.querySelectorAll('.message');
        messages.forEach(message => {
            message.style.transition = 'opacity 0.5s, transform 0.5s';
            message.style.opacity = '0';
            message.style.transform = 'translateY(-20px)';
            setTimeout(() => message.remove(), 500);
        });
    }, 6000);

    // --- Intersection Observer for scroll animations ---
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                const targetValue = parseInt(entry.target.dataset.target, 10);
                if (!isNaN(targetValue)) {
                    animateCounter(entry.target, targetValue);
                }
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal-on-scroll, .profile-stat-item .stat-value').forEach(el => {
        observer.observe(el);
    });
    
    // --- Animated Number Counter Function ---
    function animateCounter(element, target) {
        let current = 0;
        const duration = 2000;
        const increment = target / (duration / 16);
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.ceil(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };
        requestAnimationFrame(updateCounter);
    }

    // --- Aurora Effect for Course Cards ---
    document.querySelectorAll('.course-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
    
    // --- Course Search and Filter Logic ---
    const searchInput = document.getElementById('search-course');
    const difficultyFilter = document.getElementById('filter-difficulty');
    const categoryFilter = document.getElementById('filter-category');
    const coursesGrid = document.getElementById('courses-grid');
    const courseCards = coursesGrid ? Array.from(coursesGrid.querySelectorAll('.course-card')) : [];
    const noResultsMessage = document.getElementById('no-results-message');

    function filterCourses() {
        if (!searchInput) return;
        const searchTerm = searchInput.value.toLowerCase();
        const selectedDifficulty = difficultyFilter.value;
        const selectedCategory = categoryFilter.value;
        let visibleCount = 0;

        courseCards.forEach(card => {
            const title = card.dataset.title.toLowerCase();
            const difficulty = card.dataset.difficulty;
            const category = card.dataset.category;

            const titleMatch = title.includes(searchTerm);
            const difficultyMatch = (selectedDifficulty === 'all' || difficulty === selectedDifficulty);
            const categoryMatch = (selectedCategory === 'all' || category === selectedCategory);

            if (titleMatch && difficultyMatch && categoryMatch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResultsMessage) {
            noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput && difficultyFilter && categoryFilter) {
        searchInput.addEventListener('input', filterCourses);
        difficultyFilter.addEventListener('change', filterCourses);
        categoryFilter.addEventListener('change', filterCourses);
    }
    
    // --- Back to Top Button Logic ---
    const backToTopButton = document.querySelector('.back-to-top');
    if (backToTopButton) {
let scrollTimeout;
window.addEventListener('scroll', () => {
    if (isMobile) {
        if (scrollTimeout) clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (window.scrollY > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        }, 100); // Throttle scroll event handling on mobile
    } else {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    }
});
        backToTopButton.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Security enhancements have been removed as requested.
});
