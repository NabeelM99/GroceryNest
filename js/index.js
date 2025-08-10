// AOS Animation Init
    AOS.init();

    // Loading screen
    window.addEventListener('load', () => {
        document.getElementById('loadingScreen').style.opacity = '0';
        setTimeout(() => {
            document.getElementById('loadingScreen').style.display = 'none';
        }, 500);
    });

    // Scroll progress bar
    window.onscroll = () => {
        const progressBar = document.getElementById("progressBar");
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        progressBar.style.width = (scrollTop / scrollHeight) * 100 + "%";

        const navbar = document.querySelector('.navbar');
        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    };

    // Counter animation
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 10);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });

  let currentSlide = 0;
  const slides = document.querySelectorAll('.carousel-slide');
  const indicators = document.querySelectorAll('.carousel-indicator');
  const totalSlides = slides.length;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove('active');
      indicators[i].classList.remove('active');
    });
    slides[index].classList.add('active');
    indicators[index].classList.add('active');
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    showSlide(currentSlide);
  }

  setInterval(nextSlide, 3000); // changes every 5 seconds

  // Optional: allow clicking indicators
  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
      currentSlide = index;
      showSlide(currentSlide);
    });
  });







AOS.init({
    duration: 800,
    once: false,
    mirror: true,
    easing: 'ease-in-out',
    offset: 120
});

// Infinite carousel functionality
let currentIndex = 0;
const carousel = document.getElementById('categoryCarousel');
const originalCards = carousel.querySelectorAll('.category-card');
const cardWidth = 220; // card width (200px) + gap (20px)
let autoSlideInterval;
let isUserInteracting = false;

// Clone cards for infinite loop
function setupInfiniteCarousel() {
    // Clone all cards and append them to create seamless loop
    originalCards.forEach(card => {
        const clone = card.cloneNode(true);
        carousel.appendChild(clone);
    });
    
    // Clone again for smooth transition
    originalCards.forEach(card => {
        const clone = card.cloneNode(true);
        carousel.appendChild(clone);
    });
}

function updateCarousel() {
    const translateX = -currentIndex * cardWidth;
    carousel.style.transform = `translateX(${translateX}px)`;
}

function moveCarousel(direction) {
    isUserInteracting = true;
    
    if (direction === 'next') {
        currentIndex++;
        updateCarousel();
        
        // Reset position when reaching the end of first set
        if (currentIndex >= originalCards.length) {
            setTimeout(() => {
                carousel.style.transition = 'none';
                currentIndex = 0;
                updateCarousel();
                setTimeout(() => {
                    carousel.style.transition = 'transform 0.5s ease-in-out';
                }, 50);
            }, 500);
        }
    } else {
        if (currentIndex <= 0) {
            // Jump to the end of first set instantly
            carousel.style.transition = 'none';
            currentIndex = originalCards.length;
            updateCarousel();
            setTimeout(() => {
                carousel.style.transition = 'transform 0.5s ease-in-out';
                currentIndex--;
                updateCarousel();
            }, 50);
        } else {
            currentIndex--;
            updateCarousel();
        }
    }
    
    // Reset auto-slide timer
    clearInterval(autoSlideInterval);
    startAutoSlide();
    
    // Reset user interaction flag after 5 seconds
    setTimeout(() => {
        isUserInteracting = false;
    }, 3000);
}

function autoMove() {
    currentIndex++;
    updateCarousel();
    
    // Reset position when reaching the end of first set
    if (currentIndex >= originalCards.length) {
        setTimeout(() => {
            carousel.style.transition = 'none';
            currentIndex = 0;
            updateCarousel();
            setTimeout(() => {
                carousel.style.transition = 'transform 0.5s ease-in-out';
            }, 50);
        }, 500);
    }
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        if (!isUserInteracting) {
            autoMove();
        }
    }, 3000); // Move every 3 seconds
}

// Pause auto-slide on hover
carousel.addEventListener('mouseenter', () => {
    clearInterval(autoSlideInterval);
});

carousel.addEventListener('mouseleave', () => {
    if (!isUserInteracting) {
        startAutoSlide();
    }
});

// Initialize carousel
document.addEventListener('DOMContentLoaded', () => {
    setupInfiniteCarousel();
    startAutoSlide();
});

// Handle window resize
window.addEventListener('resize', () => {
    updateCarousel();
});