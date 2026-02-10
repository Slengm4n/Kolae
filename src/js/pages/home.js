import Swiper from 'swiper/bundle';
import 'swiper/css/bundle';

export function initHomeCarousel() {
    const carouselEl = document.querySelector('.intro-carousel');
    if (!carouselEl) return;

    new Swiper('.intro-carousel', {
        slidesPerView: 1,
        spaceBetween: 30,
        slidesPerGroup: 1,
        loop: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
        },
        breakpoints: {
            768: { slidesPerView: 2, slidesPerGroup: 2 },
            1024: { slidesPerView: 3, slidesPerGroup: 3 },
        },
    });
}
