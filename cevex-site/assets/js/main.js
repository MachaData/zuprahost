/* =========================================================
   CEVEX International — interactions
   ========================================================= */
(function () {
  "use strict";

  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---- Year ---- */
  var yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---- Sticky header ---- */
  var header = document.getElementById("header");
  function onScroll() {
    if (window.scrollY > 24) header.classList.add("scrolled");
    else header.classList.remove("scrolled");
  }
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  /* ---- Mobile nav ---- */
  var toggle = document.getElementById("navToggle");
  var links = document.getElementById("navLinks");
  if (toggle && links) {
    toggle.addEventListener("click", function () {
      var open = links.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.setAttribute("aria-label", open ? "Cerrar menú" : "Abrir menú");
    });
    links.addEventListener("click", function (e) {
      if (e.target.closest("a")) {
        links.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* ---- Reveal on scroll ---- */
  var revealEls = document.querySelectorAll(".reveal");
  if (prefersReduced || !("IntersectionObserver" in window)) {
    revealEls.forEach(function (el) { el.classList.add("in"); });
  } else {
    var ro = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry, i) {
        if (entry.isIntersecting) {
          var el = entry.target;
          // gentle stagger for siblings revealing together
          var delay = Math.min(i * 60, 240);
          setTimeout(function () { el.classList.add("in"); }, delay);
          ro.unobserve(el);
        }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
    revealEls.forEach(function (el) { ro.observe(el); });
  }

  /* ---- Animated counters ---- */
  function animateCount(el) {
    var target = parseFloat(el.getAttribute("data-count"));
    var suffix = el.getAttribute("data-suffix") || "";
    if (isNaN(target)) return;
    if (prefersReduced) { el.textContent = format(target) + suffix; return; }
    var dur = 1500, start = null;
    function format(n) { return Math.round(n).toLocaleString("es-PE"); }
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = format(target * eased) + suffix;
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll("[data-count]");
  if ("IntersectionObserver" in window) {
    var co = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { animateCount(entry.target); co.unobserve(entry.target); }
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { co.observe(el); });
  } else {
    counters.forEach(animateCount);
  }

  /* ---- Countdown to next event (21 November) ---- */
  var cd = document.getElementById("countdown");
  if (cd) {
    var now = new Date();
    var year = now.getFullYear();
    var target = new Date(year, 10, 21, 9, 0, 0); // month 10 = November
    if (target.getTime() < now.getTime()) target = new Date(year + 1, 10, 21, 9, 0, 0);

    var fields = {
      days: cd.querySelector('[data-cd="days"]'),
      hours: cd.querySelector('[data-cd="hours"]'),
      minutes: cd.querySelector('[data-cd="minutes"]'),
      seconds: cd.querySelector('[data-cd="seconds"]')
    };
    function pad(n) { return n < 10 ? "0" + n : "" + n; }
    function tick() {
      var diff = Math.max(0, target.getTime() - Date.now());
      var s = Math.floor(diff / 1000);
      var d = Math.floor(s / 86400); s -= d * 86400;
      var h = Math.floor(s / 3600); s -= h * 3600;
      var m = Math.floor(s / 60); s -= m * 60;
      if (fields.days) fields.days.textContent = pad(d);
      if (fields.hours) fields.hours.textContent = pad(h);
      if (fields.minutes) fields.minutes.textContent = pad(m);
      if (fields.seconds) fields.seconds.textContent = pad(s);
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ---- Contact form (front-end validation + mailto fallback) ---- */
  var form = document.getElementById("contactForm");
  var note = document.getElementById("formNote");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var name = form.name, email = form.email, message = form.message;
      var valid = true;
      [name, email, message].forEach(function (f) { f.classList.remove("invalid"); });

      if (!name.value.trim()) { name.classList.add("invalid"); valid = false; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) { email.classList.add("invalid"); valid = false; }
      if (!message.value.trim()) { message.classList.add("invalid"); valid = false; }

      if (!valid) {
        note.textContent = "Por favor completa los campos requeridos.";
        note.className = "form-note err";
        return;
      }

      // No backend yet: open the user's mail client with a prefilled message.
      var subject = encodeURIComponent("Contacto web — " + (form.subject ? form.subject.value : "CEVEX"));
      var body = encodeURIComponent(
        "Nombre: " + name.value.trim() + "\n" +
        "Correo: " + email.value.trim() + "\n" +
        "Teléfono: " + (form.phone ? form.phone.value.trim() : "") + "\n" +
        "Asunto: " + (form.subject ? form.subject.value : "") + "\n\n" +
        message.value.trim()
      );
      window.location.href = "mailto:info@cevex.org?subject=" + subject + "&body=" + body;

      note.textContent = "Gracias por escribirnos. Abrimos tu correo para enviar el mensaje.";
      note.className = "form-note ok";
      form.reset();
    });
  }
})();
