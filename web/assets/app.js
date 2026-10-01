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
