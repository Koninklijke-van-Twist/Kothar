(function () {
  var slot = document.querySelector("[data-barcode]");
  if (slot && typeof JsBarcode !== "undefined") {
    var value = slot.getAttribute("data-barcode") || "";
    if (!/^[\x00-\x7F]+$/.test(value)) {
      slot.insertAdjacentHTML("afterend", "<p class=\"hint\">Code128 kan dit nummer niet tekenen omdat er tekens buiten ASCII in staan.</p>");
    } else {
      var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
      slot.appendChild(svg);
      try {
        JsBarcode(svg, value, {
          format: "CODE128",
          displayValue: true,
          fontSize: 16,
          height: 72,
          margin: 10
        });
      } catch (error) {
        slot.insertAdjacentHTML("afterend", "<p class=\"hint\">De barcode kon niet worden gemaakt.</p>");
      }
    }
  }

  var root = document.querySelector("[data-scanner]");
  if (!root) {
    return;
  }
  var video = root.querySelector("video");
  var status = root.querySelector("[data-status]");
  var start = root.querySelector("[data-start]");
  var detectorOk = "BarcodeDetector" in window && navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
  if (!detectorOk) {
    if (status) {
      status.textContent = "Deze browser heeft geen barcodedetector. Vul het nummer hieronder in.";
    }
    if (video) {
      video.hidden = true;
    }
    if (start) {
      start.hidden = true;
    }
    return;
  }

  var detector = new BarcodeDetector({ formats: ["code_128", "code_39", "qr_code", "ean_13"] });
  var stream = null;
  var timer = null;
  start.addEventListener("click", function () {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(function (next) {
      stream = next;
      video.srcObject = next;
      video.hidden = false;
      return video.play();
    }).then(function () {
      status.textContent = "Richt de camera op de barcode.";
      timer = window.setInterval(function () {
        detector.detect(video).then(function (codes) {
          if (!codes || !codes.length) {
            return;
          }
          var raw = (codes[0].rawValue || "").trim();
          if (!raw) {
            return;
          }
          window.clearInterval(timer);
          if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
          }
          window.location = "samenstelling.php?nummer=" + encodeURIComponent(raw);
        }).catch(function () {});
      }, 400);
    }).catch(function () {
      status.textContent = "Camera niet beschikbaar. Vul het nummer handmatig in.";
    });
  });
})();

(function () {
  var form = document.querySelector("[data-builder]");
  if (!form) {
    return;
  }
  var shell = form.closest(".builder-shell") || form;
  var live = shell.querySelector("[data-code-live]");
  var progress = shell.querySelector("[data-progress]");
  var result = shell.querySelector("[data-result]");
  var suppress = false;
  var skipAdvance = false;
  var resultTimer = 0;
  var ticket = 0;
  var scrollResult = false;

  function steps() {
    return Array.prototype.slice.call(form.querySelectorAll("[data-step]"));
  }

  // Zelfde formule als kothar_segment_color() in web/lib/composition.php.
  function kotharSegmentColor(code) {
    code = String(code == null ? "" : code).trim();
    if (!code || typeof TextEncoder === "undefined") {
      return "";
    }
    var bytes = new TextEncoder().encode(code);
    var hash = 0;
    var i;
    for (i = 0; i < bytes.length; i++) {
      hash = (hash * 33 + bytes[i]) % 9973;
    }
    return "hsl(" + ((hash * 47) % 360) + " 62% 36%)";
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }

  function safeColor(color) {
    return /^hsl\(\d{1,3} 62% 36%\)$/.test(color) ? color : "";
  }

  function selectionOf(step) {
    var radio = step.querySelector('input[type="radio"]:checked');
    var fill = step.querySelector("[data-fill]");
    if (!radio) {
      return { valid: false, code: "", label: "", color: "", needsFill: false, radio: null };
    }
    var needsFill = radio.getAttribute("data-needs-fill") === "1";
    var label = radio.getAttribute("data-label") || "";
    var code = needsFill ? (fill ? fill.value.trim() : "") : (radio.getAttribute("data-code") || "");
    var color = "";
    if (code !== "") {
      color = needsFill ? kotharSegmentColor(code) : (radio.getAttribute("data-color") || kotharSegmentColor(code));
    }
    var valid = needsFill ? code !== "" && code.indexOf(".") === -1 : code !== "";
    return { valid: valid, code: code, label: label, color: safeColor(color), needsFill: needsFill, radio: radio };
  }

  function allValid() {
    var list = steps();
    if (!list.length) {
      return false;
    }
    var i;
    for (i = 0; i < list.length; i++) {
      if (!selectionOf(list[i]).valid) {
        return false;
      }
    }
    return true;
  }

  function syncStep(step) {
    var state = selectionOf(step);
    var current = step.querySelector("[data-current]");
    if (current) {
      var text = state.label || "Kies…";
      if (state.needsFill && state.code) {
        text += " · " + state.code;
      }
      current.textContent = text;
    }
    var pip = step.querySelector("[data-pip]");
    if (pip) {
      if (state.color) {
        pip.hidden = false;
        pip.style.setProperty("--seg", state.color);
      } else {
        pip.hidden = true;
        pip.style.removeProperty("--seg");
      }
    }
    step.classList.toggle("is-done", state.valid);
    var cards = step.querySelectorAll(".option-card");
    Array.prototype.forEach.call(cards, function (card) {
      var input = card.querySelector('input[type="radio"]');
      var top = card.querySelector(".option-top");
      if (!input) {
        return;
      }
      card.classList.toggle("is-selected", input.checked);
      if (!top || input.getAttribute("data-needs-fill") !== "1") {
        return;
      }
      if (input.checked && state.color) {
        top.style.setProperty("--seg", state.color);
      } else {
        top.style.removeProperty("--seg");
      }
    });
    var wrap = step.querySelector("[data-step-fill]");
    if (wrap) {
      wrap.hidden = !state.needsFill;
    }
    var fill = step.querySelector("[data-fill]");
    var error = step.querySelector("[data-fill-error]");
    var bad = state.needsFill && fill && fill.value.indexOf(".") !== -1;
    if (error) {
      error.hidden = !bad;
    }
    if (fill) {
      fill.setAttribute("aria-invalid", bad ? "true" : "false");
    }
  }

  function renderCode() {
    if (!live) {
      return;
    }
    var html = "";
    var done = 0;
    var list = steps();
    list.forEach(function (step, index) {
      var state = selectionOf(step);
      if (state.valid) {
        done++;
      }
      if (index) {
        html += '<span class="code-dot">.</span>';
      }
      if (!state.code) {
        html += '<span class="code-seg is-empty">—</span>';
        return;
      }
      var style = state.color ? ' style="--seg: ' + state.color + '"' : "";
      html += '<span class="code-seg"' + style + ">" + escapeHtml(state.code) + "</span>";
    });
    live.innerHTML = html;
    if (progress) {
      progress.textContent = done + " van " + list.length;
    }
  }

  function queryString() {
    var data = new FormData(form);
    var params = new URLSearchParams();
    data.forEach(function (value, key) {
      if (key === "bijwerken") {
        return;
      }
      if (String(value) === "" && key.indexOf("invul[") === 0) {
        return;
      }
      params.append(key, String(value));
    });
    return params.toString();
  }

  function syncUrl() {
    var next = "bouwen.php?" + queryString();
    if (window.location.search !== "?" + queryString() && window.history && window.history.replaceState) {
      window.history.replaceState(null, "", next);
    }
  }

  function scrollStep(step) {
    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    step.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "start" });
  }

  function focusStep(step) {
    var state = selectionOf(step);
    var fill = step.querySelector("[data-fill]");
    var wrap = step.querySelector("[data-step-fill]");
    if (state.needsFill && fill && wrap && !wrap.hidden) {
      fill.focus({ preventScroll: true });
      return;
    }
    var summary = step.querySelector("summary");
    if (summary) {
      summary.focus({ preventScroll: true });
    }
  }

  function openOnly(step) {
    suppress = true;
    steps().forEach(function (other) {
      other.open = other === step;
    });
    suppress = false;
    scrollStep(step);
    window.setTimeout(function () {
      focusStep(step);
    }, 40);
  }

  function nextUnfinishedBelow(step) {
    var list = steps();
    var index = list.indexOf(step);
    var i;
    for (i = index + 1; i < list.length; i++) {
      if (!selectionOf(list[i]).valid) {
        return list[i];
      }
    }
    return null;
  }

  function finishForward(step) {
    var next = nextUnfinishedBelow(step);
    if (next) {
      openOnly(next);
      return;
    }
    suppress = true;
    step.open = false;
    suppress = false;
    scrollResult = true;
  }

  function maybeAdvance(step) {
    if (step.getAttribute("data-complete") === "1") {
      return;
    }
    var state = selectionOf(step);
    if (!state.valid || state.needsFill) {
      if (state.needsFill) {
        var fill = step.querySelector("[data-fill]");
        if (fill) {
          fill.focus({ preventScroll: true });
        }
      }
      return;
    }
    var token = String(Date.now()) + Math.random();
    step.setAttribute("data-pending", token);
    window.setTimeout(function () {
      if (step.getAttribute("data-pending") !== token) {
        return;
      }
      if (step.getAttribute("data-complete") === "1" || !step.open) {
        return;
      }
      var now = selectionOf(step);
      if (!now.valid || now.needsFill) {
        return;
      }
      step.setAttribute("data-complete", "1");
      step.removeAttribute("data-pending");
      finishForward(step);
    }, 200);
  }

  function commitFill(step, allowAdvance) {
    syncStep(step);
    renderCode();
    var state = selectionOf(step);
    if (step.getAttribute("data-complete") !== "1" && state.needsFill && state.valid) {
      step.setAttribute("data-complete", "1");
      if (allowAdvance && step.open) {
        finishForward(step);
      }
    }
    syncUrl();
    scheduleResult();
  }

  function scheduleResult() {
    window.clearTimeout(resultTimer);
    if (!result) {
      return;
    }
    if (!allValid()) {
      ticket++;
      scrollResult = false;
      result.innerHTML = "";
      result.removeAttribute("aria-busy");
      return;
    }
    result.setAttribute("aria-busy", "true");
    Array.prototype.forEach.call(result.querySelectorAll("button"), function (button) {
      button.disabled = true;
    });
    var number = result.querySelector(".confirm .number");
    if (number) {
      var parts = [];
      steps().forEach(function (step) {
        var state = selectionOf(step);
        if (state.code) {
          parts.push(state.code);
        }
      });
      number.textContent = parts.join(".");
    }
    resultTimer = window.setTimeout(flushResult, 280);
  }

  function flushResult() {
    if (!result || !allValid()) {
      return;
    }
    var mine = ++ticket;
    var url = "bouwen.php?" + queryString();
    result.setAttribute("aria-busy", "true");
    window.fetch(url, { credentials: "same-origin", headers: { Accept: "text/html" } }).then(function (response) {
      return response.text();
    }).then(function (html) {
      if (mine !== ticket) {
        return;
      }
      var doc = new DOMParser().parseFromString(html, "text/html");
      var next = doc.querySelector("[data-result]");
      result.innerHTML = next ? next.innerHTML : "";
      result.removeAttribute("aria-busy");
      if (scrollResult && result.querySelector(".confirm")) {
        var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        result.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "nearest" });
      }
      scrollResult = false;
    }).catch(function () {
      if (mine !== ticket) {
        return;
      }
      result.removeAttribute("aria-busy");
      result.innerHTML = '<p class="hint">Het nummer kon niet worden bijgewerkt. Gebruik “Werk nummer bij”.</p>';
    });
  }

  form.addEventListener("pointerdown", function (event) {
    var target = event.target;
    skipAdvance = !!(target && target.closest && target.closest("summary"));
  });

  form.addEventListener("keydown", function (event) {
    if (event.key !== "Enter" || !event.target || event.target.type !== "radio") {
      return;
    }
    event.preventDefault();
    var step = event.target.closest("[data-step]");
    if (step) {
      maybeAdvance(step);
    }
  });

  steps().forEach(function (step) {
    step.addEventListener("toggle", function () {
      if (suppress || !step.open) {
        return;
      }
      suppress = true;
      steps().forEach(function (other) {
        if (other !== step) {
          other.open = false;
        }
      });
      suppress = false;
      scrollStep(step);
    });
    step.addEventListener("change", function (event) {
      var target = event.target;
      if (!target || target.type !== "radio") {
        return;
      }
      syncStep(step);
      renderCode();
      syncUrl();
      scheduleResult();
    });
    Array.prototype.forEach.call(step.querySelectorAll(".option-card"), function (card) {
      card.addEventListener("click", function () {
        window.setTimeout(function () {
          maybeAdvance(step);
        }, 0);
      });
    });
    var fill = step.querySelector("[data-fill]");
    if (!fill) {
      return;
    }
    fill.addEventListener("input", function () {
      syncStep(step);
      renderCode();
      syncUrl();
      scheduleResult();
    });
    fill.addEventListener("keydown", function (event) {
      if (event.key !== "Enter") {
        return;
      }
      event.preventDefault();
      commitFill(step, true);
    });
    fill.addEventListener("blur", function () {
      window.setTimeout(function () {
        var allow = !skipAdvance;
        skipAdvance = false;
        commitFill(step, allow);
      }, 180);
    });
  });

  suppress = true;
  steps().forEach(function (step, index) {
    step.open = index === 0;
    syncStep(step);
  });
  suppress = false;
  renderCode();
})();
