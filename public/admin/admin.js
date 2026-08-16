/* Admin panel — confirm dialogs, logo preview, drag-to-reorder. */
(function () {
  'use strict';

  /* Confirm before destructive submits. */
  document.addEventListener('click', function (event) {
    var el = event.target.closest('[data-confirm]');
    if (el && !window.confirm(el.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });

  /* Live preview of a chosen logo file. */
  var logoInput = document.getElementById('logoInput');
  var logoPreview = document.getElementById('logoPreview');

  if (logoInput && logoPreview) {
    logoInput.addEventListener('change', function () {
      var file = logoInput.files && logoInput.files[0];
      if (!file) { return; }
      var url = URL.createObjectURL(file);
      logoPreview.innerHTML = '';
      var img = document.createElement('img');
      img.src = url;
      img.alt = '';
      img.onload = function () { URL.revokeObjectURL(url); };
      logoPreview.appendChild(img);
    });
  }

  /* Keep each colour swatch's hex label in step with its picker. */
  Array.prototype.forEach.call(document.querySelectorAll('.a-color input[type="color"]'), function (input) {
    var code = input.parentNode.querySelector('code');
    if (!code) { return; }
    input.addEventListener('input', function () { code.textContent = input.value; });
  });

  /* Drag rows to reorder the business grid. */
  var table = document.getElementById('bizTable');
  var bar = document.getElementById('orderBar');

  if (table) {
    var tbody = table.querySelector('tbody');
    var dragged = null;

    tbody.addEventListener('dragstart', function (event) {
      dragged = event.target.closest('tr');
      if (!dragged) { return; }
      dragged.classList.add('is-dragging');
      event.dataTransfer.effectAllowed = 'move';
      // Firefox needs data set for the drag to start at all.
      event.dataTransfer.setData('text/plain', dragged.getAttribute('data-id'));
    });

    tbody.addEventListener('dragover', function (event) {
      event.preventDefault();
      var row = event.target.closest('tr');
      if (!row || row === dragged) { return; }
      Array.prototype.forEach.call(tbody.rows, function (r) { r.classList.remove('is-over'); });
      row.classList.add('is-over');
    });

    tbody.addEventListener('drop', function (event) {
      event.preventDefault();
      var row = event.target.closest('tr');
      if (!row || !dragged || row === dragged) { return; }

      var rows = Array.prototype.slice.call(tbody.rows);
      var from = rows.indexOf(dragged);
      var to = rows.indexOf(row);
      tbody.insertBefore(dragged, from < to ? row.nextSibling : row);

      row.classList.remove('is-over');
      if (bar) { bar.hidden = false; }
    });

    tbody.addEventListener('dragend', function () {
      if (dragged) { dragged.classList.remove('is-dragging'); }
      Array.prototype.forEach.call(tbody.rows, function (r) { r.classList.remove('is-over'); });
      dragged = null;
    });
  }
})();
