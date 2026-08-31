(function () {
  "use strict";

  const SELECTORS = {
    gallery: "[data-room-gallery]",
    track: "[data-room-track]",
    photo: "[data-room-photo]",
    previous: "[data-room-prev]",
    next: "[data-room-next]",
    current: "[data-room-current]",
    lightbox: "[data-room-lightbox]",
    lightboxImage: "[data-room-lightbox-image]",
    lightboxClose: "[data-room-lightbox-close]",
    lightboxPrevious: "[data-room-lightbox-prev]",
    lightboxNext: "[data-room-lightbox-next]",
    lightboxCurrent: "[data-room-lightbox-current]",
    lightboxTotal: "[data-room-lightbox-total]",
  };

  const SWIPE_DISTANCE = 48;
  const galleries = [];
  const prefersReducedMotion = window.matchMedia
    ? window.matchMedia("(prefers-reduced-motion: reduce)")
    : null;

  let lightbox = null;
  let lightboxImage = null;
  let lightboxClose = null;
  let lightboxPrevious = null;
  let lightboxNext = null;
  let lightboxCurrent = null;
  let lightboxTotal = null;
  let activeLightboxGallery = null;
  let activeLightboxIndex = 0;
  let lightboxOpen = false;
  let lightboxOpener = null;

  function wrapIndex(index, length) {
    if (!length) return 0;
    return ((index % length) + length) % length;
  }

  function setControlState(control, disabled) {
    if (!control) return;

    control.disabled = disabled;
    control.setAttribute("aria-disabled", String(disabled));
  }

  function getPhotoImage(photo) {
    if (!photo) return null;
    return photo.matches("img") ? photo : photo.querySelector("img");
  }

  function getPhotoSource(photo, image) {
    if (!photo || !image) return "";

    const linkedSource = photo.matches("a") ? photo.getAttribute("href") : "";

    return (
      photo.dataset.roomFull ||
      photo.dataset.fullSrc ||
      image.dataset.roomFull ||
      image.dataset.fullSrc ||
      (linkedSource && linkedSource !== "#" ? linkedSource : "") ||
      image.currentSrc ||
      image.src ||
      ""
    );
  }

  function updateGallery(gallery, nextIndex) {
    if (!gallery || !gallery.photos.length) return;

    gallery.index = wrapIndex(nextIndex, gallery.photos.length);
    gallery.root.dataset.roomActiveIndex = String(gallery.index);

    if (gallery.track) {
      gallery.track.style.transform = `translate3d(-${gallery.index * 100}%, 0, 0)`;
    }

    gallery.photos.forEach(function (photo, index) {
      const isActive = index === gallery.index;
      photo.classList.toggle("is-active", isActive);
      photo.setAttribute("aria-hidden", String(!isActive));

      if (photo.matches("button, a, [tabindex]")) {
        photo.tabIndex = isActive ? 0 : -1;
      }
    });

    if (gallery.current) {
      gallery.current.textContent = String(gallery.index + 1).padStart(2, "0");
    }
  }

  function attachSwipe(target, onPrevious, onNext, onSwipe) {
    if (!target) return;

    let startX = 0;
    let startY = 0;
    let tracking = false;

    target.addEventListener(
      "touchstart",
      function (event) {
        if (event.touches.length !== 1) {
          tracking = false;
          return;
        }

        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
        tracking = true;
      },
      { passive: true }
    );

    target.addEventListener(
      "touchend",
      function (event) {
        if (!tracking || !event.changedTouches.length) return;

        tracking = false;
        const deltaX = event.changedTouches[0].clientX - startX;
        const deltaY = event.changedTouches[0].clientY - startY;

        if (
          Math.abs(deltaX) < SWIPE_DISTANCE ||
          Math.abs(deltaX) <= Math.abs(deltaY) * 1.15
        ) {
          return;
        }

        if (typeof onSwipe === "function") onSwipe();

        if (deltaX < 0) {
          onNext();
        } else {
          onPrevious();
        }
      },
      { passive: true }
    );

    target.addEventListener(
      "touchcancel",
      function () {
        tracking = false;
      },
      { passive: true }
    );
  }

  function showLightboxImage(index) {
    if (!activeLightboxGallery || !activeLightboxGallery.photos.length || !lightboxImage) {
      return;
    }

    activeLightboxIndex = wrapIndex(index, activeLightboxGallery.photos.length);
    const photo = activeLightboxGallery.photos[activeLightboxIndex];
    const image = getPhotoImage(photo);

    if (!image) return;

    const source = getPhotoSource(photo, image);
    if (source) lightboxImage.src = source;
    lightboxImage.alt = image.alt || "Фотография номера";

    if (lightboxCurrent) {
      lightboxCurrent.textContent = String(activeLightboxIndex + 1).padStart(2, "0");
    }

    if (lightboxTotal) {
      lightboxTotal.textContent = String(activeLightboxGallery.photos.length).padStart(2, "0");
    }

    const hasMultiplePhotos = activeLightboxGallery.photos.length > 1;
    setControlState(lightboxPrevious, !hasMultiplePhotos);
    setControlState(lightboxNext, !hasMultiplePhotos);
  }

  function openLightbox(gallery, index, opener) {
    if (!lightbox || !lightboxImage || !gallery || !gallery.photos.length) return;

    activeLightboxGallery = gallery;
    lightboxOpener = opener || null;
    showLightboxImage(index);
    lightboxOpen = true;
    lightbox.hidden = false;
    lightbox.setAttribute("aria-hidden", "false");
    document.documentElement.classList.add("is-room-lightbox-open");

    if (typeof lightbox.showModal === "function") {
      if (!lightbox.open) {
        try {
          lightbox.showModal();
        } catch (_error) {
          lightbox.setAttribute("open", "");
        }
      }
    } else {
      lightbox.setAttribute("open", "");
    }

    if (lightboxClose) lightboxClose.focus({ preventScroll: true });
  }

  function finishLightboxClose() {
    if (!lightbox) return;

    lightboxOpen = false;
    lightbox.hidden = true;
    lightbox.setAttribute("aria-hidden", "true");
    lightbox.removeAttribute("open");
    document.documentElement.classList.remove("is-room-lightbox-open");

    if (lightboxImage) {
      lightboxImage.removeAttribute("src");
      lightboxImage.alt = "";
    }

    const opener = lightboxOpener;
    activeLightboxGallery = null;
    lightboxOpener = null;

    if (opener && opener.isConnected) opener.focus({ preventScroll: true });
  }

  function closeLightbox() {
    if (!lightbox || !lightboxOpen) return;

    if (typeof lightbox.close === "function" && lightbox.open) {
      lightbox.close();
    } else {
      finishLightboxClose();
    }
  }

  function initLightbox() {
    lightbox = document.querySelector(SELECTORS.lightbox);
    if (!lightbox) return;

    lightboxImage = lightbox.querySelector(SELECTORS.lightboxImage);
    lightboxClose = lightbox.querySelector(SELECTORS.lightboxClose);
    lightboxPrevious = lightbox.querySelector(SELECTORS.lightboxPrevious);
    lightboxNext = lightbox.querySelector(SELECTORS.lightboxNext);
    lightboxCurrent = lightbox.querySelector(SELECTORS.lightboxCurrent);
    lightboxTotal = lightbox.querySelector(SELECTORS.lightboxTotal);

    if (!lightboxImage) return;

    lightbox.hidden = true;
    lightbox.setAttribute("aria-hidden", "true");

    if (lightboxClose) lightboxClose.addEventListener("click", closeLightbox);

    if (lightboxPrevious) {
      lightboxPrevious.addEventListener("click", function () {
        showLightboxImage(activeLightboxIndex - 1);
      });
    }

    if (lightboxNext) {
      lightboxNext.addEventListener("click", function () {
        showLightboxImage(activeLightboxIndex + 1);
      });
    }

    lightbox.addEventListener("click", function (event) {
      if (event.target === lightbox) closeLightbox();
    });

    lightbox.addEventListener("cancel", function (event) {
      event.preventDefault();
      closeLightbox();
    });

    lightbox.addEventListener("close", finishLightboxClose);

    attachSwipe(
      lightbox,
      function () {
        showLightboxImage(activeLightboxIndex - 1);
      },
      function () {
        showLightboxImage(activeLightboxIndex + 1);
      }
    );

    document.addEventListener("keydown", function (event) {
      if (!lightboxOpen) return;

      if (event.key === "ArrowLeft") {
        event.preventDefault();
        showLightboxImage(activeLightboxIndex - 1);
      } else if (event.key === "ArrowRight") {
        event.preventDefault();
        showLightboxImage(activeLightboxIndex + 1);
      } else if (event.key === "Escape") {
        event.preventDefault();
        closeLightbox();
      }
    });
  }

  function initGallery(root) {
    if (!root || root.dataset.roomGalleryReady === "true") return;

    const track = root.querySelector(SELECTORS.track);
    const photos = Array.from(root.querySelectorAll(SELECTORS.photo));
    if (!track || !photos.length) return;

    const gallery = {
      root: root,
      track: track,
      photos: photos,
      previous: root.querySelector(SELECTORS.previous),
      next: root.querySelector(SELECTORS.next),
      current: root.querySelector(SELECTORS.current),
      index: 0,
      suppressClickUntil: 0,
    };

    root.dataset.roomGalleryReady = "true";

    if (prefersReducedMotion && prefersReducedMotion.matches) {
      track.style.transition = "none";
    }

    if (gallery.previous) {
      gallery.previous.addEventListener("click", function () {
        updateGallery(gallery, gallery.index - 1);
      });
    }

    if (gallery.next) {
      gallery.next.addEventListener("click", function () {
        updateGallery(gallery, gallery.index + 1);
      });
    }

    photos.forEach(function (photo, index) {
      photo.addEventListener("click", function (event) {
        if (window.performance.now() < gallery.suppressClickUntil) {
          event.preventDefault();
          return;
        }

        event.preventDefault();
        updateGallery(gallery, index);
        openLightbox(gallery, index, photo);
      });
    });

    attachSwipe(
      track,
      function () {
        updateGallery(gallery, gallery.index - 1);
      },
      function () {
        updateGallery(gallery, gallery.index + 1);
      },
      function () {
        gallery.suppressClickUntil = window.performance.now() + 450;
      }
    );

    const hasMultiplePhotos = photos.length > 1;
    setControlState(gallery.previous, !hasMultiplePhotos);
    setControlState(gallery.next, !hasMultiplePhotos);
    updateGallery(gallery, 0);
    galleries.push(gallery);
  }

  function init() {
    document.querySelectorAll("[data-year]").forEach(function (year) {
      year.textContent = String(new Date().getFullYear());
    });

    initLightbox();
    document.querySelectorAll(SELECTORS.gallery).forEach(initGallery);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
})();
