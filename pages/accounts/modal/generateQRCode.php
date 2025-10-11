<script src="assets/js/qrcode.min.js"></script>
<script src="assets/js/jspdf.umd.min.js"></script>
<style>
  /* Shrink modal for tablets (768px–991px) */
  @media (max-width: 991.98px) {
    #generateQR .modal-dialog {
      max-width: 90%;
    }

    #generateQR .modal-body .form-label,
    #generateQR .modal-body .form-control,
    #generateQR .modal-body .form-select,
    #generateQR .modal-body .btn {
      font-size: 0.85rem;
      padding: 0.35rem 0.5rem;
    }

    #generateQR #userSearchResults {
      max-height: 200px;
      font-size: 0.85rem;
    }
  }

  /* Shrink further for phones (<768px) */
  @media (max-width: 767.98px) {
    #generateQR .modal-dialog {
      max-width: 95%;
    }

    #generateQR .modal-body .form-label,
    #generateQR .modal-body .form-control,
    #generateQR .modal-body .form-select,
    #generateQR .modal-body .btn {
      font-size: 0.8rem;
      padding: 0.25rem 0.4rem;
    }

    #generateQR #userSearchResults {
      max-height: 150px;
      font-size: 0.8rem;
    }
  }
</style>

<div class="modal fade" id="generateQR" tabindex="-1" aria-labelledby="generateQRLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow rounded-4">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">
          <i class="bi bi-qr-code-scan me-2"></i>QR Code Generator
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="sectionSelect" class="form-label fw-semibold">Select Section</label>
            <select id="sectionSelect" class="form-select">
              <option value="">-- Select Section --</option>
            </select>
          </div>

          <div class="col-md-6 d-none" id="locationSelectWrapper">
            <label for="locationSelect" class="form-label fw-semibold">Select Location</label>
            <select id="locationSelect" class="form-select">
              <option value="">-- Select Location --</option>
            </select>
          </div>

          <div class="col-12 mt-2">
            <label for="multiUserSearch" class="form-label fw-semibold">Search Users</label>
            <input type="text" id="multiUserSearch" class="form-control" placeholder="Search by name or ID...">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold invisible">Select All</label>
            <button class="btn btn-primary w-100" onclick="toggleSelectAllDisplayed()" id="selectAllBtn">
              Select All Displayed
            </button>
          </div>


          <div class="col-12">
            <ul id="userSearchResults" class="list-group" style="max-height: 250px; overflow-y: auto;"></ul>
          </div>

          <div class="col-12">
            <button class="btn btn-success w-100" onclick="generateFiltered()">
              <i class="bi bi-filetype-pdf me-2"></i>Generate QR for All Filtered
            </button>
          </div>


        </div>
      </div>

      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>Close
        </button>
      </div>
    </div>
  </div>
</div>
<div class="table-responsive">
  <table class="table table-hover table-sm" id="userSearchTable">
    <thead class="table-dark">
      <tr>
        <th style="width:5%; text-align:center;">#</th>
        <th style="width:45%; text-align:left;">Name</th>
        <th style="width:25%; text-align:center;">Section</th>
        <th style="width:25%; text-align:center;">Specific Section</th>
      </tr>
    </thead>
    <tbody id="userSearchResults"></tbody>
  </table>
</div>

<script>
  let userData = [];
  let filteredUsers = [];
  const selectedIds = new Set();
  const uniqueSections = new Set();
  const uniqueLocations = new Set();

  function cleanValue(value) {
    try {
      const parsed = JSON.parse(value);
      if (Array.isArray(parsed)) return parsed.join(', ');
      return parsed;
    } catch {
      return value;
    }
  }


  fetch('api/accounts/getAccounts')
    .then(res => res.json())
    .then(data => {

      userData = data
        .filter(u => u.user_id && u.name) // basic validation
        .map(u => ({
          ...u,
          section: cleanValue(u.section) || '',
          specific_section: cleanValue(u.specific_section) || '',
          role: (u.role || '').toLowerCase()
        }));
      const sectionSelect = document.getElementById('sectionSelect');
      const uniqueSpecificSections = new Set();

      userData.forEach(u => {
        if (u.specific_section && u.specific_section.trim() !== '') {
          // Split by comma and trim each part
          u.specific_section.split(',').forEach(part => {
            const trimmed = part.trim();
            if (trimmed !== '') uniqueSpecificSections.add(trimmed);
          });
        }
      });

      // Sort and render
      [...uniqueSpecificSections].sort().forEach(specSection => {
        const opt = document.createElement('option');
        opt.value = specSection;
        opt.textContent = specSection.toUpperCase();
        sectionSelect.appendChild(opt);
      });


      filteredUsers = [...userData];
      displayUserSearchResults(filteredUsers);
    });

  document.getElementById('sectionSelect').addEventListener('change', function() {
    const selectedSection = this.value;
    const locationWrapper = document.getElementById('locationSelectWrapper');
    const locationSelect = document.getElementById('locationSelect');

    if (selectedSection === 'stamping') {
      locationWrapper.classList.remove('d-none');
      uniqueLocations.clear();
      userData
        .filter(u => u.section === 'stamping')
        .forEach(u => uniqueLocations.add(u.specific_section));

      locationSelect.innerHTML = `<option value="">-- Select Location --</option>`;
      [...uniqueLocations].sort().forEach(loc => {
        const opt = document.createElement('option');
        opt.value = loc;
        opt.textContent = loc.toUpperCase();
        locationSelect.appendChild(opt);
      });
    } else {
      locationWrapper.classList.add('d-none');
    }

    filterAndRenderUsers();
  });

  document.getElementById('locationSelect').addEventListener('change', filterAndRenderUsers);
  document.getElementById('multiUserSearch').addEventListener('input', filterAndRenderUsers);

  function filterAndRenderUsers() {
    const selectedSpecificSection = document.getElementById('sectionSelect').value.toLowerCase();
    const location = document.getElementById('locationSelect').value.toLowerCase();
    const query = document.getElementById('multiUserSearch').value.toLowerCase();

    filteredUsers = userData.filter(u => {
      // Split comma-separated specific_section
      const specificSections = (u.specific_section || '').split(',').map(s => s.trim().toLowerCase());

      // Match selected specific_section
      const matchesSpecificSection = !selectedSpecificSection || specificSections.includes(selectedSpecificSection);

      // Match location (if needed)
      const locations = (u.section || '').split(',').map(s => s.trim().toLowerCase()); // optional if location is separate
      const matchesLocation = !location || locations.includes(location);

      // Search by name/id/role etc.
      const searchable = [u.name, u.user_id, u.role, u.section, u.specific_section]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      const matchesSearch = !query || searchable.includes(query);

      return matchesSpecificSection && matchesLocation && matchesSearch;
    });

    displayUserSearchResults(filteredUsers);
  }


  function displayUserSearchResults(users) {
    const resultContainer = document.getElementById('userSearchResults');
    resultContainer.innerHTML = '';

    if (users.length === 0) {
      resultContainer.innerHTML =
        `<li class="list-group-item text-center">No users found</li>`;
      return;
    }

    users.forEach(user => {
      if (user.role === 'administrator' || user.role === 'account manager') return; // Skip ADMINISTRATOR accounts
      const li = document.createElement('li');
      li.className = 'list-group-item user-row';
      li.dataset.userid = String(user.user_id); // normalize as string

      let section = user.section;
      try {
        const arr = JSON.parse(user.section);
        section = Array.isArray(arr) ? arr.join(', ') : arr;
      } catch {}

      let location = user.specific_section;
      try {
        const arr = JSON.parse(user.specific_section);
        location = Array.isArray(arr) ? arr.join(', ') : arr;
      } catch {}

      li.textContent = `${user.name}`;
      if (selectedIds.has(String(user.user_id))) {
        li.classList.add('bg-primary', 'text-white');
      }

      // Toggle selection on click
      li.addEventListener('click', function() {
        const id = String(this.dataset.userid); // always string
        this.classList.toggle('bg-primary');
        this.classList.toggle('text-white');

        if (this.classList.contains('bg-primary')) {
          selectedIds.add(id);
        } else {
          selectedIds.delete(id);
        }
      });

      resultContainer.appendChild(li);
    });
  }

  function toggleSelectAllDisplayed() {
    const allSelected = filteredUsers.every(u => selectedIds.has(u.user_id));
    const resultContainer = document.getElementById('userSearchResults');

    filteredUsers.forEach(user => {
      const li = resultContainer.querySelector(`li[data-userid="${user.user_id}"]`);
      if (!li) return;

      if (allSelected) {
        selectedIds.delete(user.user_id);
        li.classList.remove('bg-primary', 'text-white');
      } else {
        selectedIds.add(user.user_id);
        li.classList.add('bg-primary', 'text-white');
      }
    });

    document.getElementById('selectAllBtn').innerHTML = allSelected ?
      `<i class="bi bi-check-square me-2"></i>Select All Displayed` :
      `<i class="bi bi-x-square me-2"></i>Unselect All Displayed`;
  }


  async function generateFiltered() {
    if (selectedIds.size === 0) {
      showAlert('warning', 'No Users Selected', 'Please select at least one user before generating QR codes.');
      return;
    }


    const selectedUsers = userData.filter(u => selectedIds.has(u.user_id));

    // Generate PDF
    await generateQRCodePDF(selectedUsers, {
      showOnPage: false,
      saveAs: 'Manpower QR.pdf'
    });

    const timestamp = await generateQRCodePDF(selectedUsers, {
      showOnPage: false,
      saveAs: 'Manpower QR.pdf'
    });

    await fetch('api/accounts/updateQRGenerated', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        user_ids: selectedUsers.map(u => u.user_id),
        generated_at: timestamp
      })
    });


    const result = await response.json();

    if (result.success) {
      showAlert('success', 'QR Codes Generated', 'The QR code PDF has been successfully created and user data updated.');
    } else {
      showAlert('error', 'Update Failed', result.message || 'Failed to update user data.');
    }

  }


  async function generateQRCodePDF(
    users, {
      showOnPage = false,
      saveAs = 'qr_codes.pdf'
    } = {}
  ) {
    const {
      jsPDF
    } = window.jspdf;
    const doc = new jsPDF();

    // 1️⃣ Get generation timestamp (Asia/Manila)
    const generatedAt = new Date().toLocaleString('en-PH', {
      timeZone: 'Asia/Manila',
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });

    // If you preview QR codes on screen, clear container
    const qrContainer = document.getElementById('qrcode');
    if (showOnPage) qrContainer.innerHTML = '';

    // Layout constants
    const qrSize = 50,
      gap = 10,
      qrPerRow = 3,
      totalWidth = qrPerRow * qrSize + (qrPerRow - 1) * gap,
      pageWidth = doc.internal.pageSize.getWidth(),
      footerY = 285; // adjust if your page height differs

    let x = (pageWidth - totalWidth) / 2;
    const startX = x;
    let y = 10,
      count = 0;

    for (const user of users) {
      if (!user.user_id || !user.name) continue;

      try {
        const canvas = await createQRCodeCanvas(user.user_id, user.name, user.section, user.specific_section, user.role);

        // Optional on‑screen preview
        if (showOnPage) {
          const wrapper = document.createElement('div');
          wrapper.className = 'text-center';
          wrapper.appendChild(canvas);

          const caption = document.createElement('p');
          caption.className = 'small mt-1 mb-4';
          caption.textContent = user.name;
          wrapper.appendChild(caption);
          qrContainer.appendChild(wrapper);
        }

        // Add QR to PDF
        const imgData = canvas.toDataURL('image/png');
        doc.addImage(imgData, 'PNG', x, y, qrSize, qrSize);

        count++;
        x += qrSize + gap;
        if (count % qrPerRow === 0) {
          x = startX;
          y += qrSize + 6;

          // Add footer timestamp when near page end
          if (y > 250) {
            doc.setFontSize(8);

            doc.addPage();
            y = 10;
          }
        }
      } catch (e) {
        console.error(e);
      }
    }

    // 👉 Footer for the last page
    doc.setFontSize(8);

    doc.save(saveAs);

    // 2️⃣ Return timestamp if caller needs it (e.g. for backend log)
    return generatedAt;
  }

  function sanitizeText(text) {
    return String(text || '')
      .replace(/[\[\]'"]/g, ''); // remove [], " and '
  }


  function createQRCodeCanvas(userId, fullName, section, specific_section, role) {
    return new Promise((resolve, reject) => {
      const qrContent = `ID: ${userId}\nName: ${fullName}`;
      const tempDiv = document.createElement('div');

      new QRCode(tempDiv, {
        text: qrContent,
        width: 200,
        height: 200,
        correctLevel: QRCode.CorrectLevel.H
      });

      setTimeout(() => {
        const qrImg = tempDiv.querySelector('img');
        if (!qrImg) return reject('QR code image not found.');

        const margin = 10;
        const qrSize = 120;
        const headerHeight = 40;
        const nameHeight = 20;
        const canvasSize = Math.max(margin + headerHeight + margin + qrSize + margin + nameHeight + margin, qrSize + margin * 2);

        const canvas = document.createElement('canvas');
        canvas.width = canvasSize;
        canvas.height = canvasSize;
        const ctx = canvas.getContext('2d');

        // Background white
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvasSize, canvasSize);

        // Header image
        const headerImg = new Image();
        headerImg.src = 'assets/images/roberts_logo.png';

        headerImg.onload = () => {
          const scale = headerHeight / headerImg.height;
          const headerWidth = headerImg.width * scale;
          const headerX = (canvasSize - headerWidth) / 3;
          const headerY = margin;
          ctx.drawImage(headerImg, headerX, headerY, headerWidth, headerHeight);

          // QR code
          const qrX = (canvasSize - qrSize) / 2;
          const qrY = headerY + headerHeight + margin;
          ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

          // Name text below QR
          ctx.fillStyle = '#000';
          ctx.font = '14px Arial';
          ctx.textAlign = 'center';

          const maxLineLength = 20;
          let nameLines = [];

          if (fullName.length > maxLineLength) {
            const words = fullName.split(' ');
            let currentLine = '';
            for (const word of words) {
              if ((currentLine + word).length <= maxLineLength) {
                currentLine += (currentLine ? ' ' : '') + word;
              } else {
                nameLines.push(currentLine);
                currentLine = word;
              }
            }
            if (currentLine) nameLines.push(currentLine);
          } else {
            nameLines.push(fullName);
          }

          nameLines = nameLines.slice(0, 2);

          nameLines.forEach((line, index) => {
            ctx.fillText(line, canvasSize / 2, qrY + qrSize + margin + 10 + index * 16);
          });

          if (role === 'operator') {
            // ✅ Draw rotated section on left
            if (section) {
              ctx.save();
              ctx.translate(30, canvasSize / 2);
              ctx.rotate(-Math.PI / 2);
              ctx.fillStyle = '#333';
              ctx.font = '14px Arial';
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';
              ctx.fillText(sanitizeText(section).toUpperCase(), 0, 0);
              ctx.restore();
            }
            // ✅ Draw rotated section location on right
            // ✅ Draw rotated section location on right
            if (specific_section) {
              ctx.save();
              ctx.translate(canvasSize - 30, canvasSize / 2); // Move to right edge, vertically centered
              ctx.rotate(-Math.PI / 2); // Rotate for vertical text
              ctx.fillStyle = '#333';
              ctx.font = '14px Arial';
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';

              const maxLineLength = 12; // shorter since rotated space is limited
              const words = sanitizeText(specific_section).toUpperCase().split(' ');
              let currentLine = '';
              const wrappedLines = [];

              for (const word of words) {
                if ((currentLine + word).length <= maxLineLength) {
                  currentLine += (currentLine ? ' ' : '') + word;
                } else {
                  wrappedLines.push(currentLine);
                  currentLine = word;
                }
              }
              if (currentLine) wrappedLines.push(currentLine);

              // Only allow up to 3 lines to avoid overflowing
              const lineHeight = 16;
              const totalHeight = wrappedLines.length * lineHeight;
              const startY = -(totalHeight / 2) + lineHeight / 2;

              wrappedLines.forEach((line, i) => {
                ctx.fillText(line, 0, startY + i * lineHeight);
              });

              ctx.restore();
            }

          }


          // Border
          ctx.lineWidth = 1.5;
          ctx.strokeStyle = '#000';
          ctx.strokeRect(0.75, 0.75, canvasSize - 1.5, canvasSize - 1.5);

          resolve(canvas);
        };

        headerImg.onerror = () => reject('Failed to load header image.');
      }, 100);
    });
  }
</script>