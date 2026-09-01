const loader = document.querySelector('.page-loader');
const header = document.querySelector('[data-header]');
const progress = document.querySelector('.scroll-progress');
const menuButton = document.querySelector('.menu-toggle');
const mobileMenu = document.querySelector('.mobile-menu');
const menuLinks = mobileMenu.querySelectorAll('a');
const heroMedia = document.querySelector('.hero-media');
const animatedMark = document.querySelector('[data-animated-mark]');

const finishLoading = () => {
  window.setTimeout(() => loader.classList.add('is-hidden'), 80);
};

finishLoading();

const updateScrollState = () => {
  const scrollTop = window.scrollY;
  const scrollRange = document.documentElement.scrollHeight - window.innerHeight;
  header.classList.toggle('is-scrolled', scrollTop > 40);
  progress.style.width = `${scrollRange > 0 ? (scrollTop / scrollRange) * 100 : 0}%`;

  if (heroMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    heroMedia.style.removeProperty('transform');
  } else if (heroMedia && scrollTop < window.innerHeight) {
    heroMedia.style.transform = `translate3d(0, ${scrollTop * -0.12}px, 0) scale(1.06)`;
  }
};

window.addEventListener('scroll', updateScrollState, { passive: true });
updateScrollState();

if (animatedMark) {
  const markImage = animatedMark.querySelector('img');
  const staticSource = markImage.getAttribute('src');
  const animatedSource = staticSource.split('#')[0];
  let animationRun = 0;

  const playMarkAnimation = () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    animationRun += 1;
    markImage.src = `${animatedSource}?run=${animationRun}`;
  };

  const resetMarkAnimation = () => {
    markImage.src = staticSource;
  };

  animatedMark.addEventListener('mouseenter', playMarkAnimation);
  animatedMark.addEventListener('mouseleave', resetMarkAnimation);
  animatedMark.addEventListener('pointerdown', (event) => {
    if (event.pointerType !== 'mouse') playMarkAnimation();
  });
}

const revealObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach((entry) => {
    if (!entry.isIntersecting) return;
    entry.target.classList.add('is-visible');
    observer.unobserve(entry.target);
  });
}, { threshold: 0.14, rootMargin: '0px 0px -50px' });

document.querySelectorAll('.reveal').forEach((item) => revealObserver.observe(item));

const setMenu = (open) => {
  menuButton.setAttribute('aria-expanded', String(open));
  mobileMenu.setAttribute('aria-hidden', String(!open));
  mobileMenu.classList.toggle('is-open', open);
  document.body.classList.toggle('menu-open', open);
};

menuButton.addEventListener('click', () => setMenu(menuButton.getAttribute('aria-expanded') !== 'true'));
menuLinks.forEach((link) => link.addEventListener('click', () => setMenu(false)));
window.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') setMenu(false);
});

document.querySelectorAll('[data-year]').forEach((year) => {
  year.textContent = new Date().getFullYear();
});

const gallery = document.querySelector('[data-photo-gallery]');

if (gallery) {
  const track = gallery.querySelector('[data-gallery-track]');
  const previousButton = gallery.querySelector('[data-gallery-prev]');
  const nextButton = gallery.querySelector('[data-gallery-next]');
  const lightbox = document.querySelector('[data-gallery-lightbox]');
  const lightboxImage = lightbox?.querySelector('[data-lightbox-image]');
  const lightboxTransitionImage = lightbox?.querySelector('[data-lightbox-transition-image]');
  const lightboxClose = lightbox?.querySelector('[data-lightbox-close]');
  const lightboxPrevious = lightbox?.querySelector('[data-lightbox-prev]');
  const lightboxNext = lightbox?.querySelector('[data-lightbox-next]');
  const lightboxCurrent = lightbox?.querySelector('[data-lightbox-current]');
  const lightboxTotal = lightbox?.querySelector('[data-lightbox-total]');
  const originalSlides = Array.from(track.querySelectorAll('[data-gallery-slide]'));

  if (originalSlides.length > 1) {
    const prepareClone = (slide) => {
      const clone = slide.cloneNode(true);
      clone.classList.add('is-clone');
      clone.setAttribute('aria-hidden', 'true');
      clone.querySelectorAll('button').forEach((button) => {
        button.tabIndex = -1;
        button.disabled = true;
      });
      return clone;
    };

    track.prepend(prepareClone(originalSlides.at(-1)));
    track.append(prepareClone(originalSlides[0]));

    const slides = Array.from(track.querySelectorAll('[data-gallery-slide]'));
    let currentIndex = 1;
    let isAnimating = false;
    let transitionTimer = 0;
    let dragStartX = 0;
    let dragDelta = 0;
    let isDragging = false;
    let suppressClick = false;
    let currentOffset = 0;
    let resizeFrame = 0;
    let lightboxIndex = 0;
    let lightboxTransitionTimer = 0;
    let lightboxDialogTransitionTimer = 0;
    let lightboxImageRequestId = 0;
    let renderedLightboxIndex = 0;
    let lightboxTransitionTargetIndex = null;
    let lightboxOpener = null;
    let lightboxPointerId = null;
    let lightboxPointerStartX = 0;
    let lightboxPointerStartY = 0;

    if (lightboxTotal) lightboxTotal.textContent = String(originalSlides.length);

    const updateSlideState = () => {
      slides.forEach((slide, index) => slide.classList.toggle('is-active', index === currentIndex));
      const activeRealIndex = (currentIndex - 1 + originalSlides.length) % originalSlides.length;
      originalSlides.forEach((slide, index) => {
        const active = index === activeRealIndex;
        slide.setAttribute('aria-hidden', String(!active));
        slide.querySelector('[data-gallery-open]')?.setAttribute('tabindex', active ? '0' : '-1');
      });
    };

    const getOffset = () => {
      const slideWidth = slides[0].getBoundingClientRect().width;
      return (gallery.clientWidth / 2) - (currentIndex * slideWidth) - (slideWidth / 2);
    };

    const positionGallery = (animate = true, offsetAdjustment = 0) => {
      currentOffset = getOffset();
      track.style.transition = animate ? '' : 'none';
      track.style.transform = `translate3d(${currentOffset + offsetAdjustment}px, 0, 0)`;
      if (!animate) {
        void track.offsetWidth;
        requestAnimationFrame(() => track.style.removeProperty('transition'));
      }
    };

    const settleLoop = () => {
      window.clearTimeout(transitionTimer);
      if (currentIndex === 0) {
        currentIndex = originalSlides.length;
        positionGallery(false);
      } else if (currentIndex === slides.length - 1) {
        currentIndex = 1;
        positionGallery(false);
      }
      updateSlideState();
      isAnimating = false;
    };

    const moveGallery = (direction) => {
      if (isAnimating) return;
      currentIndex += direction;
      isAnimating = true;
      updateSlideState();
      positionGallery(true);
      transitionTimer = window.setTimeout(settleLoop, 620);
    };

    const lightboxReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const lightboxAnimationClasses = [
      'is-sliding-out-next',
      'is-sliding-in-next',
      'is-sliding-out-prev',
      'is-sliding-in-prev',
    ];

    const clearLightboxImageTransition = (useIncomingImage) => {
      window.clearTimeout(lightboxTransitionTimer);
      lightboxTransitionTimer = 0;
      lightboxImage?.classList.remove(...lightboxAnimationClasses);

      if (!lightboxTransitionImage) return;

      lightboxTransitionImage.onload = null;
      lightboxTransitionImage.onerror = null;

      if (
        useIncomingImage &&
        !lightboxTransitionImage.hidden &&
        lightboxTransitionImage.complete &&
        lightboxTransitionImage.naturalWidth > 0 &&
        lightboxTransitionImage.getAttribute('src') &&
        lightboxImage
      ) {
        lightboxImage.src = lightboxTransitionImage.src;
        lightboxImage.alt = lightboxTransitionImage.alt;
        if (Number.isInteger(lightboxTransitionTargetIndex)) {
          renderedLightboxIndex = lightboxTransitionTargetIndex;
        }
      }

      lightboxTransitionImage.classList.remove(...lightboxAnimationClasses);
      lightboxTransitionImage.hidden = true;
      lightboxTransitionImage.removeAttribute('src');
      lightboxTransitionImage.alt = '';
      lightboxTransitionTargetIndex = null;
    };

    const finishLightboxImageTransition = () => clearLightboxImageTransition(true);

    const updateLightboxCounter = () => {
      if (lightboxCurrent) lightboxCurrent.textContent = String(lightboxIndex + 1);
    };

    const preloadLightboxNeighbors = () => {
      [
        (lightboxIndex - 1 + originalSlides.length) % originalSlides.length,
        (lightboxIndex + 1) % originalSlides.length,
      ].forEach((index) => {
        const neighborImage = originalSlides[index]?.querySelector('img');
        if (!neighborImage) return;
        const preloadImage = new Image();
        preloadImage.src = neighborImage.currentSrc || neighborImage.src;
      });
    };

    const renderLightbox = (animate = true, direction = 0) => {
      if (!lightbox || !lightboxImage) return;

      const imageRequestId = ++lightboxImageRequestId;
      clearLightboxImageTransition(true);

      const sourceImage = originalSlides[lightboxIndex]?.querySelector('img');
      if (!sourceImage) return;

      const source = sourceImage.currentSrc || sourceImage.src;
      const alt = sourceImage.alt;
      const currentSource = lightboxImage.currentSrc || lightboxImage.src;
      const shouldAnimate = Boolean(
        animate &&
        direction &&
        lightbox.open &&
        lightboxTransitionImage &&
        lightboxImage.getAttribute('src') &&
        source &&
        source !== currentSource &&
        !lightboxReducedMotion.matches
      );

      updateLightboxCounter();
      preloadLightboxNeighbors();

      if (!shouldAnimate) {
        lightboxImage.src = source;
        lightboxImage.alt = alt;
        renderedLightboxIndex = lightboxIndex;
        return;
      }

      lightboxTransitionImage.src = source;
      lightboxTransitionImage.alt = alt;
      lightboxTransitionImage.hidden = true;
      lightboxTransitionTargetIndex = lightboxIndex;

      const failTransition = () => {
        if (imageRequestId !== lightboxImageRequestId) return;
        lightboxIndex = renderedLightboxIndex;
        updateLightboxCounter();
        clearLightboxImageTransition(false);
      };

      const startTransition = () => {
        if (
          imageRequestId !== lightboxImageRequestId ||
          !lightboxTransitionImage.complete ||
          lightboxTransitionImage.naturalWidth === 0
        ) return;

        lightboxTransitionImage.onload = null;
        lightboxTransitionImage.onerror = null;
        lightboxTransitionImage.hidden = false;

        void lightboxImage.offsetWidth;
        lightboxImage.classList.add(direction < 0 ? 'is-sliding-out-prev' : 'is-sliding-out-next');
        lightboxTransitionImage.classList.add(direction < 0 ? 'is-sliding-in-prev' : 'is-sliding-in-next');
        lightboxTransitionTimer = window.setTimeout(finishLightboxImageTransition, 600);
      };

      const decodeAndStartTransition = () => {
        if (imageRequestId !== lightboxImageRequestId) return;
        if (typeof lightboxTransitionImage.decode === 'function') {
          lightboxTransitionImage.decode().then(startTransition).catch(() => {
            if (lightboxTransitionImage.naturalWidth > 0) startTransition();
            else failTransition();
          });
        } else {
          startTransition();
        }
      };

      lightboxTransitionImage.onerror = failTransition;
      if (lightboxTransitionImage.complete && lightboxTransitionImage.naturalWidth > 0) {
        decodeAndStartTransition();
      } else {
        lightboxTransitionImage.onload = decodeAndStartTransition;
      }
    };

    const moveLightbox = (direction) => {
      lightboxIndex = (lightboxIndex + direction + originalSlides.length) % originalSlides.length;
      renderLightbox(true, direction);
    };

    const setLightboxTransitionOrigin = (sourceRect) => {
      if (!lightbox || !sourceRect) return false;
      const targetRect = lightbox.getBoundingClientRect();
      if (!targetRect.width || !targetRect.height || !sourceRect.width || !sourceRect.height) return false;

      const scaleX = Math.max(.06, Math.min(sourceRect.width / targetRect.width, 1.4));
      const scaleY = Math.max(.06, Math.min(sourceRect.height / targetRect.height, 1.4));
      lightbox.style.setProperty('--lightbox-origin-x', `${sourceRect.left - targetRect.left}px`);
      lightbox.style.setProperty('--lightbox-origin-y', `${sourceRect.top - targetRect.top}px`);
      lightbox.style.setProperty('--lightbox-origin-scale-x', String(scaleX));
      lightbox.style.setProperty('--lightbox-origin-scale-y', String(scaleY));
      return true;
    };

    const syncGalleryToLightbox = () => {
      currentIndex = lightboxIndex + 1;
      updateSlideState();
      positionGallery(false);
    };

    const finishLightboxClose = () => {
      if (lightbox?.open) lightbox.close();
    };

    const closeLightbox = () => {
      if (!lightbox?.open || lightbox.classList.contains('is-closing')) return;

      window.clearTimeout(lightboxDialogTransitionTimer);
      finishLightboxImageTransition();
      syncGalleryToLightbox();

      const targetOpener = originalSlides[lightboxIndex]?.querySelector('[data-gallery-open]');
      const hasOrigin = setLightboxTransitionOrigin(targetOpener?.getBoundingClientRect());

      if (lightboxReducedMotion.matches || !hasOrigin) {
        finishLightboxClose();
        return;
      }

      lightbox.classList.remove('is-opening');
      void lightbox.offsetWidth;
      lightbox.classList.add('is-closing');
      lightboxDialogTransitionTimer = window.setTimeout(finishLightboxClose, 560);
    };

    const openLightbox = (index, opener) => {
      if (!lightbox || !lightboxImage || suppressClick) return;
      const sourceRect = opener?.getBoundingClientRect();
      lightboxOpener = opener || null;
      lightboxIndex = index;
      renderLightbox(false);
      lightbox.classList.remove('is-opening', 'is-closing');
      lightbox.classList.add('is-measuring');
      lightbox.showModal();
      const hasOrigin = setLightboxTransitionOrigin(sourceRect);
      lightbox.classList.remove('is-measuring');

      if (!lightboxReducedMotion.matches && hasOrigin) {
        void lightbox.offsetWidth;
        lightbox.classList.add('is-opening');
        lightboxDialogTransitionTimer = window.setTimeout(() => {
          lightbox.classList.remove('is-opening');
          lightboxDialogTransitionTimer = 0;
        }, 640);
      }

      lightboxClose?.focus({ preventScroll: true });
    };

    originalSlides.forEach((slide, index) => {
      const button = slide.querySelector('[data-gallery-open]');
      if (button) button.addEventListener('click', () => openLightbox(index, button));
    });

    previousButton.addEventListener('click', () => moveGallery(-1));
    nextButton.addEventListener('click', () => moveGallery(1));

    gallery.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        moveGallery(-1);
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        moveGallery(1);
      }
    });

    track.addEventListener('transitionend', (event) => {
      if (event.propertyName === 'transform') settleLoop();
    });

    gallery.addEventListener('pointerdown', (event) => {
      if (isAnimating || event.pointerType === 'mouse' || event.target.closest('.photo-gallery-edge')) return;
      dragStartX = event.clientX;
      dragDelta = 0;
      isDragging = false;
      track.style.transition = 'none';
      gallery.setPointerCapture(event.pointerId);
    });

    gallery.addEventListener('pointermove', (event) => {
      if (!gallery.hasPointerCapture(event.pointerId)) return;
      dragDelta = event.clientX - dragStartX;
      if (Math.abs(dragDelta) > 6) {
        isDragging = true;
        gallery.classList.add('is-dragging');
      }
      track.style.transform = `translate3d(${currentOffset + dragDelta}px, 0, 0)`;
    });

    const finishDrag = (event) => {
      if (!gallery.hasPointerCapture(event.pointerId)) return;
      gallery.releasePointerCapture(event.pointerId);
      const slideWidth = slides[0].getBoundingClientRect().width;
      const threshold = Math.min(90, Math.max(48, slideWidth * .12));
      suppressClick = isDragging;
      gallery.classList.remove('is-dragging');
      track.style.removeProperty('transition');

      if (Math.abs(dragDelta) >= threshold) {
        moveGallery(dragDelta < 0 ? 1 : -1);
      } else {
        positionGallery(true);
      }

      dragDelta = 0;
      isDragging = false;
      window.setTimeout(() => { suppressClick = false; }, 0);
    };

    gallery.addEventListener('pointerup', finishDrag);
    gallery.addEventListener('pointercancel', finishDrag);

    window.addEventListener('resize', () => {
      cancelAnimationFrame(resizeFrame);
      resizeFrame = requestAnimationFrame(() => positionGallery(false));
    });

    if (lightbox) {
      lightboxPrevious?.addEventListener('click', () => moveLightbox(-1));
      lightboxNext?.addEventListener('click', () => moveLightbox(1));
      lightboxClose?.addEventListener('click', closeLightbox);
      lightbox.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          closeLightbox();
          return;
        }
        if (event.key === 'ArrowLeft') {
          event.preventDefault();
          moveLightbox(-1);
        }
        if (event.key === 'ArrowRight') {
          event.preventDefault();
          moveLightbox(1);
        }
      });
      lightbox.addEventListener('pointerdown', (event) => {
        if (!event.isPrimary || event.pointerType === 'mouse' || event.target !== lightboxImage) return;
        lightboxPointerId = event.pointerId;
        lightboxPointerStartX = event.clientX;
        lightboxPointerStartY = event.clientY;
        lightbox.setPointerCapture(event.pointerId);
      });
      lightbox.addEventListener('pointerup', (event) => {
        if (event.pointerId !== lightboxPointerId) return;
        const deltaX = event.clientX - lightboxPointerStartX;
        const deltaY = event.clientY - lightboxPointerStartY;
        if (lightbox.hasPointerCapture(event.pointerId)) lightbox.releasePointerCapture(event.pointerId);
        lightboxPointerId = null;
        if (Math.abs(deltaX) >= 52 && Math.abs(deltaX) > Math.abs(deltaY) * 1.15) {
          moveLightbox(deltaX < 0 ? 1 : -1);
        }
      });
      lightbox.addEventListener('pointercancel', (event) => {
        if (event.pointerId !== lightboxPointerId) return;
        if (lightbox.hasPointerCapture(event.pointerId)) lightbox.releasePointerCapture(event.pointerId);
        lightboxPointerId = null;
      });
      lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox) closeLightbox();
      });
      lightbox.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeLightbox();
      });
      lightbox.addEventListener('close', () => {
        window.clearTimeout(lightboxDialogTransitionTimer);
        window.clearTimeout(lightboxTransitionTimer);
        window.clearTimeout(transitionTimer);
        lightboxImageRequestId += 1;
        clearLightboxImageTransition(false);
        lightbox.classList.remove('is-measuring', 'is-opening', 'is-closing');
        lightbox.style.removeProperty('--lightbox-origin-x');
        lightbox.style.removeProperty('--lightbox-origin-y');
        lightbox.style.removeProperty('--lightbox-origin-scale-x');
        lightbox.style.removeProperty('--lightbox-origin-scale-y');
        lightboxImage?.removeAttribute('src');
        lightboxPointerId = null;
        isAnimating = false;
        renderedLightboxIndex = lightboxIndex;
        syncGalleryToLightbox();

        const focusTarget = originalSlides[lightboxIndex]?.querySelector('[data-gallery-open]') || lightboxOpener;
        lightboxOpener = null;
        focusTarget?.focus({ preventScroll: true });
      });
    }

    updateSlideState();
    positionGallery(false);
  }
}

const restaurantGallery = document.querySelector('[data-restaurant-gallery]');
const restaurantLightbox = document.querySelector('[data-restaurant-lightbox]');

if (restaurantGallery && restaurantLightbox) {
  const restaurantOpeners = Array.from(restaurantGallery.querySelectorAll('[data-restaurant-gallery-open]'));
  const restaurantLightboxImage = restaurantLightbox.querySelector('[data-restaurant-lightbox-image]');
  const restaurantLightboxClose = restaurantLightbox.querySelector('[data-restaurant-lightbox-close]');
  const restaurantLightboxPrevious = restaurantLightbox.querySelector('[data-restaurant-lightbox-prev]');
  const restaurantLightboxNext = restaurantLightbox.querySelector('[data-restaurant-lightbox-next]');
  const restaurantLightboxCurrent = restaurantLightbox.querySelector('[data-restaurant-lightbox-current]');
  const restaurantLightboxTotal = restaurantLightbox.querySelector('[data-restaurant-lightbox-total]');
  let restaurantLightboxIndex = 0;
  let restaurantLightboxPointerId = null;
  let restaurantLightboxPointerStartX = 0;
  let restaurantLightboxPointerStartY = 0;

  if (restaurantLightboxTotal) restaurantLightboxTotal.textContent = String(restaurantOpeners.length);

  const renderRestaurantLightbox = () => {
    const image = restaurantOpeners[restaurantLightboxIndex]?.querySelector('img');
    if (!image || !restaurantLightboxImage) return;
    restaurantLightboxImage.src = image.currentSrc || image.src;
    restaurantLightboxImage.alt = image.alt;
    if (restaurantLightboxCurrent) restaurantLightboxCurrent.textContent = String(restaurantLightboxIndex + 1);
  };

  const moveRestaurantLightbox = (direction) => {
    restaurantLightboxIndex = (restaurantLightboxIndex + direction + restaurantOpeners.length) % restaurantOpeners.length;
    renderRestaurantLightbox();
  };

  restaurantOpeners.forEach((button, index) => {
    button.addEventListener('click', () => {
      restaurantLightboxIndex = index;
      renderRestaurantLightbox();
      restaurantLightbox.showModal();
    });
  });

  restaurantLightboxPrevious?.addEventListener('click', () => moveRestaurantLightbox(-1));
  restaurantLightboxNext?.addEventListener('click', () => moveRestaurantLightbox(1));
  restaurantLightboxClose?.addEventListener('click', () => restaurantLightbox.close());

  restaurantLightbox.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      moveRestaurantLightbox(-1);
    }
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      moveRestaurantLightbox(1);
    }
  });

  restaurantLightbox.addEventListener('pointerdown', (event) => {
    if (!event.isPrimary || event.pointerType === 'mouse' || event.target !== restaurantLightboxImage) return;
    restaurantLightboxPointerId = event.pointerId;
    restaurantLightboxPointerStartX = event.clientX;
    restaurantLightboxPointerStartY = event.clientY;
    restaurantLightbox.setPointerCapture(event.pointerId);
  });

  restaurantLightbox.addEventListener('pointerup', (event) => {
    if (event.pointerId !== restaurantLightboxPointerId) return;
    const deltaX = event.clientX - restaurantLightboxPointerStartX;
    const deltaY = event.clientY - restaurantLightboxPointerStartY;
    if (restaurantLightbox.hasPointerCapture(event.pointerId)) restaurantLightbox.releasePointerCapture(event.pointerId);
    restaurantLightboxPointerId = null;
    if (Math.abs(deltaX) >= 52 && Math.abs(deltaX) > Math.abs(deltaY) * 1.15) {
      moveRestaurantLightbox(deltaX < 0 ? 1 : -1);
    }
  });

  restaurantLightbox.addEventListener('pointercancel', (event) => {
    if (event.pointerId !== restaurantLightboxPointerId) return;
    if (restaurantLightbox.hasPointerCapture(event.pointerId)) restaurantLightbox.releasePointerCapture(event.pointerId);
    restaurantLightboxPointerId = null;
  });

  restaurantLightbox.addEventListener('click', (event) => {
    if (event.target === restaurantLightbox) restaurantLightbox.close();
  });

  restaurantLightbox.addEventListener('close', () => {
    restaurantLightboxImage?.removeAttribute('src');
    restaurantLightboxPointerId = null;
    restaurantOpeners[restaurantLightboxIndex]?.focus({ preventScroll: true });
  });
}
