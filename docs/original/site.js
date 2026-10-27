(function () {
  "use strict";

  function norm(s) {
    return (s || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");
  }

  function getSearchQuery() {
    var el = document.getElementById("siteSearch");
    return el ? norm(el.value.trim()) : "";
  }

  function listingCards() {
    var grid = document.getElementById("productGrid");
    if (!grid) return [];
    return Array.prototype.slice.call(grid.querySelectorAll(".product-card"));
  }

  function applyListingFilters() {
    var cards = listingCards();
    if (!cards.length) return;

    var q = getSearchQuery();
    var sportEls = document.querySelectorAll('input[name="sport"]:checked');
    var sports = Array.prototype.map.call(sportEls, function (i) {
      return norm(i.value);
    });
    var genderEls = document.querySelectorAll('input[name="gender"]:checked');
    var genders = Array.prototype.map.call(genderEls, function (i) {
      return norm(i.value);
    });
    var brandEls = document.querySelectorAll('input[name="brand"]:checked');
    var brands = Array.prototype.map.call(brandEls, function (i) {
      return norm(i.value);
    });

    var chipActive = document.querySelector("#categoryChips .active[data-cat]");
    var chipCat = chipActive ? norm(chipActive.getAttribute("data-cat")) : "";

    cards.forEach(function (card) {
      var name = norm(card.getAttribute("data-name") || "");
      var cat = norm(card.getAttribute("data-category") || "");
      var gen = norm(card.getAttribute("data-gender") || "");
      var brand = norm(card.getAttribute("data-brand") || "");

      var matchSearch = !q || name.indexOf(q) !== -1;
      var matchSport = sports.length === 0 || sports.indexOf(cat) !== -1;
      var matchGender = genders.length === 0 || genders.indexOf(gen) !== -1;
      var matchBrand = brands.length === 0 || brands.indexOf(brand) !== -1;
      var matchChip =
        !chipCat ||
        chipCat === "all" ||
        cat === chipCat;

      var show =
        matchSearch &&
        matchSport &&
        matchGender &&
        matchBrand &&
        matchChip;
      card.classList.toggle("d-none", !show);
    });

    var empty = document.getElementById("filterEmpty");
    if (empty) {
      var any = cards.some(function (c) {
        return !c.classList.contains("d-none");
      });
      empty.classList.toggle("d-none", any);
    }
  }

  function initSearchAndFilters() {
    var search = document.getElementById("siteSearch");
    if (search) {
      search.addEventListener("input", applyListingFilters);
    }
    document
      .querySelectorAll(
        'input[name="sport"], input[name="gender"], input[name="brand"]'
      )
      .forEach(function (el) {
        el.addEventListener("change", applyListingFilters);
      });

    var chips = document.getElementById("categoryChips");
    if (chips) {
      chips.addEventListener("click", function (e) {
        var btn = e.target.closest("button[data-cat]");
        if (!btn) return;
        chips.querySelectorAll("button[data-cat]").forEach(function (b) {
          b.classList.remove("active", "btn-dark");
          b.classList.add("btn-outline-dark");
        });
        btn.classList.add("active", "btn-dark");
        btn.classList.remove("btn-outline-dark");
        applyListingFilters();
      });
    }

    applyListingFilters();
  }

  function initHeroCarouselCaption() {
    var carousel = document.getElementById("heroCarousel");
    var titleEl = document.getElementById("heroCaptionTitle");
    var subEl = document.getElementById("heroCaptionSub");
    if (!carousel || !titleEl || !subEl) return;

    function sync() {
      var active = carousel.querySelector(".carousel-item.active");
      if (!active) return;
      titleEl.textContent = active.getAttribute("data-title") || "";
      subEl.textContent = active.getAttribute("data-sub") || "";
    }

    carousel.addEventListener("slid.bs.carousel", sync);
    sync();
  }

  function initOtherProductsScroll() {
    var row = document.getElementById("otherProductsRow");
    var prev = document.getElementById("otherProductsPrev");
    var next = document.getElementById("otherProductsNext");
    if (!row || !prev || !next) return;

    var step = 200;
    prev.addEventListener("click", function () {
      row.scrollBy({ left: -step, behavior: "smooth" });
    });
    next.addEventListener("click", function () {
      row.scrollBy({ left: step, behavior: "smooth" });
    });
  }

  function initProductSizes() {
    document.querySelectorAll(".sizes.size-select").forEach(function (wrap) {
      wrap.querySelectorAll("button[type='button']").forEach(function (btn) {
        btn.addEventListener("click", function () {
          wrap.querySelectorAll("button").forEach(function (b) {
            b.classList.remove("active");
          });
          btn.classList.add("active");
        });
      });
    });
  }

  function initCartQty() {
    var qtyInput = document.getElementById("cartQty");
    var minus = document.getElementById("cartQtyMinus");
    var plus = document.getElementById("cartQtyPlus");
    var lineTotal = document.getElementById("cartLineTotal");
    var unitEl = document.getElementById("cartUnitPrice");
    var unit = parseFloat(
      (unitEl && unitEl.getAttribute("data-price")) || "999.99",
      10
    );

    function refresh() {
      if (!qtyInput) return;
      var n = Math.max(1, parseInt(qtyInput.value, 10) || 1);
      qtyInput.value = n;
      var total = unit * n;
      var formatted =
        "R$" +
        total.toLocaleString("pt-BR", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        });
      if (lineTotal) lineTotal.textContent = formatted;
      var summary = document.getElementById("cartSummaryTotal");
      if (summary) summary.textContent = formatted;
    }

    minus &&
      minus.addEventListener("click", function () {
        qtyInput.value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) - 1);
        refresh();
      });
    plus &&
      plus.addEventListener("click", function () {
        qtyInput.value = (parseInt(qtyInput.value, 10) || 1) + 1;
        refresh();
      });
    qtyInput && qtyInput.addEventListener("input", refresh);
    refresh();
  }

  function initCepDemo() {
    var btn = document.getElementById("cepCalcBtn");
    var out = document.getElementById("cepResult");
    var cep = document.getElementById("cep");
    if (!btn || !out) return;
    btn.addEventListener("click", function () {
      var v = cep && cep.value ? cep.value.trim() : "";
      if (v.length < 8) {
        out.textContent = "Informe um CEP válido (8 dígitos).";
        out.className = "small text-danger mt-1";
        return;
      }
      out.textContent =
        "Frete estimado: R$ 24,90 · entrega em 5 a 8 dias úteis (simulação).";
      out.className = "small text-success mt-1";
    });
  }

  function initHomeProductStripSearch() {
    var row = document.getElementById("otherProductsRow");
    var search = document.getElementById("siteSearch");
    if (!row || !search) return;
    search.addEventListener("input", function () {
      var q = norm(search.value.trim());
      row.querySelectorAll(".other-card").forEach(function (card) {
        var n = norm(card.textContent || "");
        card.style.display = !q || n.indexOf(q) !== -1 ? "" : "none";
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initSearchAndFilters();
    initHeroCarouselCaption();
    initOtherProductsScroll();
    initHomeProductStripSearch();
    initProductSizes();
    initCartQty();
    initCepDemo();
  });
})();
