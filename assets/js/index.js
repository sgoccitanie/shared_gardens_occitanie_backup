// Carrousel d'articles : défilement horizontal piloté par les flèches
document.querySelectorAll('[data-articles-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    if (!track || !prev || !next) {
        return;
    }

    // Avance d'un article (largeur d'une carte + espacement)
    const step = () => {
        const item = track.querySelector('.articles-carousel__item');
        if (!item) {
            return track.clientWidth;
        }
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        return item.getBoundingClientRect().width + gap;
    };

    const EDGE_TOLERANCE = 8;

    // Masque la flèche sans libérer sa place (les cartes ne bougent pas)
    const setArrowHidden = (arrow, hidden) => {
        arrow.classList.toggle('is-hidden', hidden);
        arrow.disabled = hidden;
    };

    const update = () => {
        const maxScroll = track.scrollWidth - track.clientWidth;
        // Tous les articles tiennent à l'écran : pas de flèches du tout
        carousel.classList.toggle('is-static', maxScroll <= 1);
        // Une flèche disparaît quand on est au bout de son côté
        // (tolérance de quelques pixels : l'aimantation ne s'arrête pas toujours pile à 0)
        setArrowHidden(prev, track.scrollLeft <= EDGE_TOLERANCE);
        setArrowHidden(next, track.scrollLeft >= maxScroll - EDGE_TOLERANCE);
    };

    prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
});
