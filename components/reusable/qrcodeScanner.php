<script src="assets/js/html5.qrcode.js"></script>

<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title" id="qrModalLabel">Scan QR Code</h5>
        <div class="d-flex align-items-center">
          <button id="toggleCameraBtn" class="btn btn-secondary btn-sm me-2">Toggle Camera</button>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body">
        <div id="qr-reader" style="width: 100%;"></div>
        <div id="qr-result" class="mt-3 text-center fw-bold text-success"></div>
      </div>
    </div>
  </div>
</div>

<script>
  let html5QrcodeScanner = null;
  let currentCameraId = null;
  let camerasList = [];
  let usingFront = true; // ← set this to true

  // Fetch accounts from API
  async function fetchAccounts() {
    const res = await fetch('api/accounts/getAccounts');
    return res.ok ? res.json() : [];
  }

  // Check if account passes the rules
  function passesAccountRules(acc, {
    section = null,
    role = null,
    userProductionLocation = null
  }) {
    const accRole = (acc.role ?? '').toUpperCase();
    const targetRole = Array.isArray(role) ? role.map(r => r.toUpperCase()) : [(role ?? '').toUpperCase()];

    if (accRole === 'ADMINISTRATOR' && targetRole.includes('ADMINISTRATOR')) return true;
    if (accRole === 'ADMINISTRATOR' && targetRole.includes('OPERATOR')) return false;

    // Parse production
    let accProductions = [];
    try {
      accProductions = JSON.parse(acc.section || '[]');
    } catch {
      accProductions = [acc.section ?? ''];
    }
    accProductions = accProductions.map(p => p.toUpperCase());

    // Parse production_location
    let accLocations = [];
    try {
      accLocations = JSON.parse(acc.specific_section || '[]');
    } catch {
      accLocations = [acc.specific_section ?? ''];
    }
    accLocations = accLocations.map(loc => loc.toLowerCase().replace(/[-\s]/g, ''));

    const targetSection = (section ?? '').toUpperCase();
    let targetLocations = Array.isArray(userProductionLocation) ? userProductionLocation : (userProductionLocation ? [userProductionLocation] : []);
    targetLocations = targetLocations.map(loc => loc.toLowerCase().replace(/[-\s]/g, ''));

    const isMatchingProduction = !section ||
      (targetSection === 'QC' ? accProductions.some(p => p.includes('QC')) : accProductions.includes(targetSection));
    const isMatchingRole = !role || targetRole.includes(accRole);
    const isMatchingLocation = !targetLocations.length || accLocations.some(loc => targetLocations.includes(loc));

    return isMatchingProduction && isMatchingRole && isMatchingLocation;
  }

  // Scan QR code and validate user
  async function scanQRCodeForUser({
    onSuccess,
    onCancel,
    section = null,
    role = null,
    userProductionLocation = null
  }) {
    const modalElement = document.getElementById('qrModal');
    const resultContainer = document.getElementById('qr-result');
    const qrReader = new Html5Qrcode('qr-reader');
    html5QrcodeScanner = qrReader;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    resultContainer.textContent = 'Waiting for QR scan…';

    camerasList = await Html5Qrcode.getCameras();
    if (!camerasList || !camerasList.length) {
      resultContainer.textContent = 'No cameras found on this device.';
      return;
    }

    function getCameraId(front = true) {
      let cam = camerasList.find(c => front ? c.label.toLowerCase().includes('front') : c.label.toLowerCase().includes('back'));
      return cam ? cam.id : camerasList[0].id;
    }

    currentCameraId = getCameraId(usingFront);

    const startScanner = (cameraId) => {
      qrReader.start({
          deviceId: {
            exact: cameraId
          }
        }, {
          fps: 10,
          qrbox: 550
        },
        async (decodedText) => {
            resultContainer.textContent = `QR Code Scanned: ${decodedText}`;
            qrReader.pause();

            const confirmScan = await Swal.fire({
              title: 'Confirm Scan',
              text: `Is this the correct QR code?\n${decodedText}`,
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Yes, confirm',
              cancelButtonText: 'No, rescan'
            });

            if (!confirmScan.isConfirmed) {
              resultContainer.textContent = 'Waiting for QR scan…';
              qrReader.resume();
              return;
            }

            const user_id = (decodedText.match(/ID:\s*([^\n]+)/)?.[1] || '').trim();
            const full_name = (decodedText.match(/Name:\s*(.+)/)?.[1] || '').trim();

            if (!user_id || !full_name) {
              await showAlert('error', 'Error', 'Could not extract user ID or name.');
              resultContainer.textContent = 'Waiting for QR scan…';
              qrReader.resume();
              return;
            }

            const allAccounts = await fetchAccounts();
            const exactMatch = allAccounts.find(
              acc => acc.user_id === user_id && acc.name.toUpperCase() === full_name.toUpperCase()
            );

            if (!exactMatch) {
              await showAlert('error', 'Error', 'User does not exist in the system.');
              resultContainer.textContent = 'Waiting for QR scan…';
              qrReader.resume();
              return;
            }

            const authorized = passesAccountRules(exactMatch, {
              section,
              role,
              userProductionLocation
            });

            if (!authorized) {
              await showAlert('error', 'Access Denied', 'User is not authorized for this section/location.');
              resultContainer.textContent = 'Waiting for QR scan…';
              qrReader.resume();
              return;
            }
            onSuccess?.({
              user_id: exactMatch.user_id,
              full_name: exactMatch.name,
              production_location: exactMatch.specific_section,
              production: exactMatch.section,
              role: exactMatch.role
            });

            modal.hide();
            cleanupQRScanner(qrReader);
          },
          () => {
            /* ignore scan errors */
          }
      ).catch(err => resultContainer.textContent = `Unable to start scanner: ${err}`);
    };

    startScanner(currentCameraId);

    document.getElementById('toggleCameraBtn').onclick = () => {
      usingFront = !usingFront;
      currentCameraId = getCameraId(usingFront);
      qrReader.stop().then(() => {
        qrReader.clear();
        startScanner(currentCameraId);
      }).catch(console.warn);
    };

    modalElement.addEventListener('hidden.bs.modal', () => {
      cleanupQRScanner(qrReader);
      onCancel?.();
    }, {
      once: true
    });
  }

  function cleanupQRScanner(reader) {
    reader?.stop().then(() => reader.clear()).catch(console.warn);
  }
</script>