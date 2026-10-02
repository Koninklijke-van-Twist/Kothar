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
  Array.prototype.forEach.call(form.querySelectorAll("[data-fill]"), function (fill) {
    var next = String(fill.value || "").replace(/\s+/g, "");
    if (next !== fill.value) {
      fill.value = next;
      fill.defaultValue = next;
    }
  });
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

  function meterToken(raw) {
    var text = String(raw == null ? "" : raw).replace(/\s+/g, "").replace(",", ".");
    if (!/^\d+(?:\.\d+)?$/.test(text)) {
      return "";
    }
    if (text.indexOf(".") !== -1) {
      text = text.replace(/0+$/, "").replace(/\.$/, "");
    }
    return text;
  }

  function measureBox(step, mode) {
    return step.querySelector('[data-measures="' + mode + '"]');
  }

  function measureValue(box, name) {
    var input = box ? box.querySelector('[data-measure="' + name + '"]') : null;
    return meterToken(input ? input.value : "");
  }

  function syncMeasures(step) {
    var kind = step.getAttribute("data-kind") || "choices";
    if (kind !== "dimensions") {
      return;
    }
    var radio = step.querySelector("input[data-vector]:checked");
    var mode = radio ? (radio.getAttribute("data-vector") || "") : "";
    Array.prototype.forEach.call(step.querySelectorAll("[data-measures]"), function (box) {
      var on = box.getAttribute("data-measures") === mode && mode !== "";
      box.hidden = !on;
      Array.prototype.forEach.call(box.querySelectorAll("input"), function (input) {
        input.disabled = !on;
      });
    });
  }

  function selectionOf(step) {
    var kind = step.getAttribute("data-kind") || "choices";
    if (kind === "quantity") {
      var qty = step.querySelector("[data-quantity]");
      var qtyCode = qty ? String(qty.value || "").replace(/\s+/g, "") : "";
      if (!/^\d+$/.test(qtyCode)) {
        qtyCode = "";
      }
      var prompt = step.getAttribute("data-prompt") || "Vul quantity in";
      return {
        valid: qtyCode !== "",
        code: qtyCode,
        label: qtyCode !== "" ? qtyCode : prompt,
        color: qtyCode !== "" ? kotharSegmentColor(qtyCode) : "",
        needsFill: true,
        radio: null
      };
    }
    if (kind === "hwl" || kind === "diameter" || kind === "dimensions") {
      syncMeasures(step);
      var mode = kind;
      var vectorRadio = null;
      if (kind === "dimensions") {
        vectorRadio = step.querySelector("input[data-vector]:checked");
        mode = vectorRadio ? (vectorRadio.getAttribute("data-vector") || "") : "";
      }
      var box = mode === "hwl" || mode === "diameter" ? measureBox(step, mode) : null;
      var vectorCode = "";
      if (mode === "hwl") {
        var height = measureValue(box, "h");
        var width = measureValue(box, "b");
        var length = measureValue(box, "l");
        if (height !== "" && width !== "" && length !== "") {
          vectorCode = "H" + height + "xB" + width + "xL" + length;
        }
      } else if (mode === "diameter") {
        var diameter = measureValue(box, "d");
        var diaLength = measureValue(box, "l");
        if (diameter !== "" && diaLength !== "") {
          vectorCode = "⌀" + diameter + "xL" + diaLength;
        }
      }
      var vectorLabel = vectorCode;
      if (vectorLabel === "") {
        vectorLabel = mode === "" ? "Kies ⌀×L of H×B×L" : "Vul de maten in";
      }
      return {
        valid: vectorCode !== "",
        code: vectorCode,
        label: vectorLabel,
        color: vectorCode !== "" ? kotharSegmentColor(vectorCode) : "",
        needsFill: true,
        radio: vectorRadio
      };
    }
    var radio = step.querySelector('input[type="radio"]:checked');
    var fill = step.querySelector("[data-fill]");
    if (!radio) {
      return { valid: false, code: "", label: "", color: "", needsFill: false, radio: null };
    }
    var needsFill = radio.getAttribute("data-needs-fill") === "1";
    var label = radio.getAttribute("data-label") || "";
    var code = needsFill ? (fill ? String(fill.value || "").replace(/\s+/g, "") : "") : (radio.getAttribute("data-code") || "");
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
      var kind = step.getAttribute("data-kind") || "choices";
      if (kind === "choices" && state.needsFill && state.code) {
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
      if (target.hasAttribute("data-vector")) {
        var box = step.querySelector('[data-measures="' + target.getAttribute("data-vector") + '"]');
        var first = box ? box.querySelector("[data-measure]") : null;
        if (first) {
          first.focus({ preventScroll: true });
        }
      }
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
    Array.prototype.forEach.call(step.querySelectorAll("[data-measure], [data-quantity]"), function (input) {
      input.addEventListener("input", function () {
        syncStep(step);
        renderCode();
        syncUrl();
        scheduleResult();
      });
      input.addEventListener("keydown", function (event) {
        if (event.key !== "Enter") {
          return;
        }
        event.preventDefault();
        commitFill(step, true);
      });
      input.addEventListener("blur", function () {
        window.setTimeout(function () {
          var allow = !skipAdvance;
          skipAdvance = false;
          commitFill(step, allow);
        }, 180);
      });
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

(function () {
  var triggers = document.querySelectorAll("[data-confirm]");
  if (!triggers.length || typeof HTMLDialogElement === "undefined") {
    return;
  }
  var pending = null;
  var dialog = document.createElement("dialog");
  dialog.className = "modal";
  dialog.innerHTML = ''
    + '<div class="modal-card">'
    + '<h2 id="bevestig-titel">Verwijderen</h2>'
    + '<p id="bevestig-tekst"></p>'
    + '<p class="hint" id="bevestig-noot" hidden></p>'
    + '<div class="modal-actions">'
    + '<button type="button" class="quiet" data-cancel>Annuleren</button>'
    + '<button type="button" class="danger" data-ok>Verwijderen</button>'
    + '</div></div>';
  dialog.setAttribute("aria-labelledby", "bevestig-titel");
  dialog.setAttribute("aria-describedby", "bevestig-tekst");
  document.body.appendChild(dialog);
  var text = dialog.querySelector("#bevestig-tekst");
  var note = dialog.querySelector("#bevestig-noot");
  var cancel = dialog.querySelector("[data-cancel]");
  var ok = dialog.querySelector("[data-ok]");

  function closeModal() {
    pending = null;
    if (dialog.open) {
      dialog.close();
    }
  }

  function openModal(button) {
    pending = button;
    text.textContent = button.getAttribute("data-confirm") || "Weet je zeker dat je dit wilt verwijderen?";
    var extra = button.getAttribute("data-confirm-note") || "";
    note.textContent = extra;
    note.hidden = extra === "";
    dialog.showModal();
    cancel.focus();
  }

  Array.prototype.forEach.call(triggers, function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      openModal(button);
    });
  });

  cancel.addEventListener("click", closeModal);
  dialog.addEventListener("cancel", function () {
    pending = null;
  });
  dialog.addEventListener("click", function (event) {
    if (event.target === dialog) {
      closeModal();
    }
  });
  ok.addEventListener("click", function () {
    var button = pending;
    var form = button && button.form;
    var actie = button ? (button.getAttribute("data-actie") || "") : "";
    var optie = button ? (button.getAttribute("data-optie") || "") : "";
    closeModal();
    if (!form || actie === "") {
      return;
    }
    if (optie !== "") {
      var optieInput = form.querySelector('input[name="optie"]');
      if (!optieInput) {
        optieInput = document.createElement("input");
        optieInput.type = "hidden";
        optieInput.name = "optie";
        form.appendChild(optieInput);
      }
      optieInput.value = optie;
    }
    if (window.kotharEnsureCsrf) {
      window.kotharEnsureCsrf(form);
    }
    var input = document.createElement("input");
    input.type = "hidden";
    input.name = "actie";
    input.value = actie;
    form.appendChild(input);
    form.submit();
  });
})();

(function () {
  function token() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute("content") || "") : "";
  }

  function sameOriginPost(form, action) {
    if (!form || String(form.method || "").toLowerCase() !== "post") {
      return false;
    }
    var target = typeof action === "string" && action !== "" ? action : (form.action || "");
    try {
      return new URL(target, window.location.href).origin === window.location.origin;
    } catch (error) {
      return false;
    }
  }

  function ensure(form, action) {
    var value = token();
    if (!form || value === "" || !sameOriginPost(form, action)) {
      return value;
    }
    var input = form.querySelector('input[name="csrf"]');
    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = "csrf";
      form.appendChild(input);
    }
    if (input.value === "") {
      input.value = value;
    }
    return value;
  }

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form || form.tagName !== "FORM") {
      return;
    }
    var action = "";
    var submitter = event.submitter;
    if (submitter && submitter.hasAttribute && submitter.hasAttribute("formaction")) {
      action = submitter.formAction || "";
    }
    ensure(form, action);
  }, true);
  window.kotharCsrf = token;
  window.kotharEnsureCsrf = ensure;
})();

(function () {
  var list = document.querySelector("[data-column-list]");
  if (!list) {
    return;
  }
  var categoryId = list.getAttribute("data-category-id") || "";
  var button = document.querySelector("[data-volgorde-knop]");
  var hint = document.querySelector("[data-reorder-hint]");
  var orderQueue = Promise.resolve();
  var serverOrder = columnIds();

  if (list.getAttribute("data-reordering") === "1") {
    document.body.classList.add("is-reordering");
    closeColumns();
  }

  function columnNodes() {
    return Array.prototype.slice.call(list.querySelectorAll("[data-column]"));
  }

  function columnIds() {
    var ids = [];
    columnNodes().forEach(function (node) {
      ids.push(node.getAttribute("data-column-id") || "");
    });
    return ids;
  }

  function closeColumns() {
    columnNodes().forEach(function (column) {
      column.open = false;
    });
  }

  function messageFrom(text) {
    var raw = String(text || "").trim();
    if (raw === "") {
      return "Opslaan mislukt.";
    }
    try {
      var data = JSON.parse(raw);
      if (data && data.message) {
        return String(data.message);
      }
    } catch (error) {
      return raw;
    }
    return raw;
  }

  function showError(message) {
    var main = document.querySelector("main");
    var node = document.querySelector("[data-admin-error]");
    if (!node) {
      node = document.createElement("p");
      node.className = "flash flash-warn";
      node.setAttribute("data-admin-error", "");
      node.setAttribute("role", "alert");
      if (main) {
        main.insertBefore(node, main.firstChild);
      }
    }
    node.hidden = false;
    node.textContent = message;
  }

  function hideError() {
    var node = document.querySelector("[data-admin-error]");
    if (node) {
      node.hidden = true;
    }
  }

  function currentOptionOrder(optionList) {
    var ids = [];
    Array.prototype.forEach.call(optionList.querySelectorAll("[data-option-id]"), function (row) {
      var id = row.getAttribute("data-option-id") || "";
      if (id !== "") {
        ids.push(id);
      }
    });
    return ids.join(",");
  }

  function formDirty(form) {
    var fields = form.querySelectorAll("input, textarea, select");
    var i;
    for (i = 0; i < fields.length; i++) {
      var field = fields[i];
      if (field.type === "hidden" || field.type === "submit" || field.type === "button") {
        continue;
      }
      if (field.value !== field.defaultValue) {
        return true;
      }
    }
    var optionList = form.querySelector("[data-option-list]");
    if (!optionList) {
      return false;
    }
    return (optionList.getAttribute("data-original-order") || "") !== currentOptionOrder(optionList);
  }

  function syncDirty(form) {
    Array.prototype.forEach.call(form.querySelectorAll("input, textarea, select"), function (field) {
      if (field.type === "hidden" || field.type === "submit" || field.type === "button") {
        return;
      }
      field.classList.toggle("is-dirty", field.value !== field.defaultValue);
    });
    var optionList = form.querySelector("[data-option-list]");
    if (!optionList) {
      return;
    }
    var original = (optionList.getAttribute("data-original-order") || "").split(",");
    var now = currentOptionOrder(optionList).split(",");
    if (original.length === 1 && original[0] === "") {
      original = [];
    }
    if (now.length === 1 && now[0] === "") {
      now = [];
    }
    Array.prototype.forEach.call(optionList.querySelectorAll("[data-option-id]"), function (row) {
      var id = row.getAttribute("data-option-id") || "";
      var handle = row.querySelector("[data-option-handle]");
      if (!handle) {
        return;
      }
      handle.classList.toggle("is-dirty", original.indexOf(id) !== now.indexOf(id));
    });
  }

  function anyDirty() {
    var forms = document.querySelectorAll("form[data-save-actie]");
    var i;
    for (i = 0; i < forms.length; i++) {
      if (formDirty(forms[i])) {
        return true;
      }
    }
    return false;
  }

  function dirtyForms() {
    return Array.prototype.filter.call(document.querySelectorAll("form[data-save-actie]"), function (form) {
      return formDirty(form);
    });
  }

  function setReordering(on) {
    document.body.classList.toggle("is-reordering", on);
    closeColumns();
    columnNodes().forEach(function (column) {
      var handle = column.querySelector("[data-column-handle]");
      if (handle) {
        handle.hidden = !on;
      }
    });
    if (button) {
      button.textContent = on ? "Volgorde aanpassen gereed" : "Volgorde aanpassen";
      button.setAttribute("aria-pressed", on ? "true" : "false");
    }
    if (hint) {
      hint.hidden = !on;
    }
    if (window.history && window.history.replaceState) {
      var url = new URL(window.location.href);
      if (on) {
        url.searchParams.set("volgorde", "1");
      } else {
        url.searchParams.delete("volgorde");
      }
      window.history.replaceState(null, "", url.pathname + url.search);
    }
  }

  function reorderUrl() {
    return "beheer_categorie.php?id=" + encodeURIComponent(categoryId) + "&volgorde=1";
  }

  function postForm(form) {
    if (window.kotharStripCodes) {
      window.kotharStripCodes(form);
    }
    if (window.kotharEnsureCsrf) {
      window.kotharEnsureCsrf(form);
    }
    var data = new FormData(form);
    data.set("actie", form.getAttribute("data-save-actie") || "");
    data.set("ajax", "1");
    if (!data.get("csrf") && window.kotharCsrf) {
      data.set("csrf", window.kotharCsrf());
    }
    return fetch(window.location.pathname + window.location.search, {
      method: "POST",
      body: data,
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    }).then(function (response) {
      if (!response.ok) {
        return response.text().then(function (text) {
          throw new Error(messageFrom(text));
        });
      }
    });
  }

  function saveDirtyThenReorder() {
    var forms = dirtyForms();
    var chain = Promise.resolve();
    forms.forEach(function (form) {
      chain = chain.then(function () {
        return postForm(form);
      });
    });
    return chain.then(function () {
      window.location = reorderUrl();
    });
  }

  function postColumnOrder(ids) {
    var body = new URLSearchParams();
    body.set("csrf", window.kotharCsrf ? window.kotharCsrf() : "");
    body.set("id", categoryId);
    body.set("actie", "kolom-volgorde");
    body.set("ajax", "1");
    ids.forEach(function (id) {
      body.append("kolom_id[]", id);
    });
    return fetch("beheer_categorie.php?id=" + encodeURIComponent(categoryId), {
      method: "POST",
      body: body,
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    }).then(function (response) {
      if (!response.ok) {
        return response.text().then(function (text) {
          throw new Error(messageFrom(text));
        });
      }
    });
  }

  function applyColumnOrder(ids) {
    var byId = {};
    columnNodes().forEach(function (node) {
      byId[node.getAttribute("data-column-id")] = node;
    });
    ids.forEach(function (id) {
      if (byId[id]) {
        list.appendChild(byId[id]);
      }
    });
  }

  function persistColumnOrder() {
    var snapshot = columnIds();
    if (snapshot.join(",") === serverOrder.join(",")) {
      return;
    }
    orderQueue = orderQueue.then(function () {
      return postColumnOrder(snapshot).then(function () {
        serverOrder = snapshot.slice();
        hideError();
      }).catch(function (error) {
        applyColumnOrder(serverOrder);
        showError(error.message || "Ongeldige sessie. Laad de pagina opnieuw.");
      });
    });
  }

  function bindSort(container, canStart, onUpdate) {
    var dragItem = null;
    var pointerId = null;

    function move(event) {
      if (!dragItem || event.pointerId !== pointerId) {
        return;
      }
      dragItem.style.pointerEvents = "none";
      var under = document.elementFromPoint(event.clientX, event.clientY);
      dragItem.style.pointerEvents = "";
      var target = under && under.closest("[data-sort-item]");
      if (!target || target === dragItem || target.parentElement !== container) {
        return;
      }
      var rect = target.getBoundingClientRect();
      var after = event.clientY > rect.top + rect.height / 2;
      container.insertBefore(dragItem, after ? target.nextSibling : target);
    }

    function finish(event) {
      if (!dragItem || event.pointerId !== pointerId) {
        return;
      }
      document.removeEventListener("pointermove", move);
      document.removeEventListener("pointerup", finish);
      document.removeEventListener("pointercancel", finish);
      dragItem.classList.remove("is-dragging");
      dragItem.style.pointerEvents = "";
      dragItem = null;
      pointerId = null;
      onUpdate();
    }

    container.addEventListener("pointerdown", function (event) {
      if (event.button > 0) {
        return;
      }
      var item = canStart(event);
      if (!item || item.parentElement !== container) {
        return;
      }
      event.preventDefault();
      var focusTarget = event.target.closest("[data-option-handle], [data-column-handle]");
      if (!focusTarget) {
        focusTarget = event.target.closest("summary");
      }
      if (focusTarget && typeof focusTarget.focus === "function") {
        focusTarget.focus();
      }
      dragItem = item;
      pointerId = event.pointerId;
      item.classList.add("is-dragging");
      document.addEventListener("pointermove", move);
      document.addEventListener("pointerup", finish);
      document.addEventListener("pointercancel", finish);
    });

    container.addEventListener("keydown", function (event) {
      if (event.key !== "ArrowUp" && event.key !== "ArrowDown") {
        return;
      }
      var item = canStart(event);
      if (!item || item.parentElement !== container) {
        return;
      }
      event.preventDefault();
      var moved = false;
      if (event.key === "ArrowUp" && item.previousElementSibling) {
        container.insertBefore(item, item.previousElementSibling);
        moved = true;
      } else if (event.key === "ArrowDown" && item.nextElementSibling) {
        container.insertBefore(item.nextElementSibling, item);
        moved = true;
      }
      if (event.target && typeof event.target.focus === "function") {
        event.target.focus();
      }
      if (moved) {
        onUpdate();
      }
    });
  }

  columnNodes().forEach(function (column) {
    column.setAttribute("data-sort-item", "");
    var summary = column.querySelector("summary");
    if (summary) {
      summary.addEventListener("click", function (event) {
        if (document.body.classList.contains("is-reordering")) {
          event.preventDefault();
        }
      });
    }
    column.addEventListener("toggle", function () {
      if (document.body.classList.contains("is-reordering") && column.open) {
        column.open = false;
      }
    });
    var form = column.querySelector("[data-column-form]");
    if (!form) {
      return;
    }
    form.addEventListener("input", function () {
      syncDirty(form);
      var nameInput = form.querySelector('[name="kolomnaam"]');
      var nameLabel = column.querySelector(".column-name");
      if (nameInput && nameLabel) {
        nameLabel.textContent = nameInput.value.trim() || "Kolom";
      }
    });
    var optionList = form.querySelector("[data-option-list]");
    if (optionList) {
      Array.prototype.forEach.call(optionList.querySelectorAll("[data-option-id]"), function (row) {
        row.setAttribute("data-sort-item", "");
      });
      bindSort(optionList, function (event) {
        var handle = event.target.closest("[data-option-handle]");
        if (!handle || !optionList.contains(handle)) {
          return null;
        }
        return handle.closest("[data-option-id]");
      }, function () {
        syncDirty(form);
      });
    }
    var add = form.querySelector("[data-add-option]");
    if (add) {
      add.addEventListener("click", function () {
        var label = form.querySelector("[data-new-label]");
        if (!label || label.value.trim() === "") {
          if (label) {
            label.focus();
          }
          return;
        }
        var submit = form.querySelector('button[type="submit"]');
        var previous = submit ? submit.value : "";
        if (submit) {
          submit.value = "optie-nieuw";
        }
        if (window.kotharEnsureCsrf) {
          window.kotharEnsureCsrf(form);
        }
        if (form.requestSubmit && submit) {
          form.requestSubmit(submit);
        } else if (form.requestSubmit) {
          form.requestSubmit();
        } else {
          form.submit();
        }
        if (submit) {
          submit.value = previous;
        }
      });
    }
  });

  bindSort(list, function (event) {
    if (!document.body.classList.contains("is-reordering")) {
      return null;
    }
    var summary = event.target.closest("summary");
    if (!summary || !list.contains(summary)) {
      return null;
    }
    return summary.closest("[data-column]");
  }, persistColumnOrder);

  Array.prototype.forEach.call(document.querySelectorAll("form[data-save-actie]"), function (form) {
    form.addEventListener("input", function () {
      syncDirty(form);
    });
  });

  if (button) {
    button.addEventListener("click", function () {
      if (document.body.classList.contains("is-reordering")) {
        setReordering(false);
        return;
      }
      if (!anyDirty()) {
        setReordering(true);
        return;
      }
      openUnsaved(function (choice) {
        if (choice === "save") {
          button.disabled = true;
          saveDirtyThenReorder().catch(function (error) {
            button.disabled = false;
            showError(error.message || "Opslaan mislukt.");
          });
          return;
        }
        if (choice === "discard") {
          window.location = reorderUrl();
        }
      });
    });
  }

  function openUnsaved(done) {
    if (typeof HTMLDialogElement === "undefined") {
      if (window.confirm("Er zijn niet-opgeslagen wijzigingen. OK verwerpt ze en past de volgorde aan.")) {
        done("discard");
      }
      return;
    }
    var dialog = document.createElement("dialog");
    dialog.className = "modal";
    dialog.innerHTML = ""
      + '<div class="modal-card">'
      + "<h2>Niet-opgeslagen wijzigingen</h2>"
      + "<p>Er zijn niet-opgeslagen wijzigingen. Wil je die opslaan of verwerpen voordat je de volgorde aanpast?</p>"
      + '<div class="modal-actions">'
      + '<button type="button" class="quiet" data-cancel>Annuleren</button>'
      + '<button type="button" class="quiet" data-discard>Verwerpen</button>'
      + '<button type="button" data-save>Opslaan</button>'
      + "</div></div>";
    document.body.appendChild(dialog);
    var cancel = dialog.querySelector("[data-cancel]");
    var discard = dialog.querySelector("[data-discard]");
    var save = dialog.querySelector("[data-save]");
    function close(choice) {
      dialog.close();
      dialog.remove();
      done(choice);
    }
    cancel.addEventListener("click", function () { close(""); });
    discard.addEventListener("click", function () { close("discard"); });
    save.addEventListener("click", function () { close("save"); });
    dialog.addEventListener("cancel", function () { done(""); });
    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) {
        close("");
      }
    });
    dialog.showModal();
    cancel.focus();
  }
})();

(function () {
  function isCodeField(field) {
    return !!(field && field.matches && field.matches('input[name="code[]"], input[data-fill]'));
  }

  function stripField(field) {
    var raw = String(field.value || "");
    var next = raw.replace(/\s+/g, "");
    if (next === raw) {
      return;
    }
    var start = field.selectionStart;
    var end = field.selectionEnd;
    field.value = next;
    if (document.activeElement !== field || start == null || !field.setSelectionRange) {
      return;
    }
    var before = raw.slice(0, start).replace(/\s+/g, "").length;
    var span = raw.slice(start, end == null ? start : end).replace(/\s+/g, "").length;
    field.setSelectionRange(before, before + span);
  }

  function stripRoot(root) {
    var scope = root && root.querySelectorAll ? root : document;
    Array.prototype.forEach.call(scope.querySelectorAll('input[name="code[]"], input[data-fill]'), stripField);
  }

  window.kotharStripCodes = stripRoot;

  document.addEventListener("input", function (event) {
    if (isCodeField(event.target)) {
      stripField(event.target);
    }
  }, true);

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (form && form.querySelectorAll) {
      stripRoot(form);
    }
  }, true);
})();

(function () {
  var openButton = document.querySelector("[data-category-edit]");
  var dialog = document.querySelector("[data-category-modal]");
  if (!openButton || !dialog) {
    return;
  }
  var form = dialog.querySelector("form");
  var cancel = dialog.querySelector("[data-category-cancel]");
  var nameInput = dialog.querySelector("#naam");

  function resetForm() {
    if (!form) {
      return;
    }
    form.reset();
    form.dispatchEvent(new Event("input", { bubbles: true }));
  }

  function openModal() {
    if (typeof dialog.showModal === "function") {
      if (!dialog.open) {
        dialog.showModal();
      }
    } else {
      dialog.setAttribute("open", "");
    }
    openButton.setAttribute("aria-expanded", "true");
    if (nameInput) {
      nameInput.focus();
    }
  }

  function closeModal() {
    resetForm();
    if (dialog.open && typeof dialog.close === "function") {
      dialog.close();
    } else {
      dialog.removeAttribute("open");
    }
    openButton.setAttribute("aria-expanded", "false");
  }

  openButton.addEventListener("click", openModal);
  if (cancel) {
    cancel.addEventListener("click", closeModal);
  }
  dialog.addEventListener("cancel", function () {
    resetForm();
    openButton.setAttribute("aria-expanded", "false");
  });
  dialog.addEventListener("close", function () {
    openButton.setAttribute("aria-expanded", "false");
  });
  dialog.addEventListener("click", function (event) {
    if (event.target === dialog) {
      closeModal();
    }
  });
})();
