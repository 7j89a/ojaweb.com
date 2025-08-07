document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const elementsToAnimate = document.querySelectorAll('.subject-category-wrapper, .course-card');
    elementsToAnimate.forEach(el => {
        observer.observe(el);
    });

    // Add some initial animation delay to cards
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach((card, index) => {
        card.style.transitionDelay = `${index * 50}ms`;
    });
});

// Add a subtle parallax effect to the background
window.addEventListener('scroll', () => {
    const section = document.querySelector('.courses-v2-section');
    if (section) {
        const yPos = window.pageYOffset;
        section.style.backgroundPosition = `50% ${yPos * 0.1}px`;
    }
});
