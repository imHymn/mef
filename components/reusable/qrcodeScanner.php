<!-- Reusable Modal for Selecting User -->
<div class="modal fade" id="accountSelectModal" tabindex="-1" aria-labelledby="accountSelectLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="accountSelectLabel">Select Account</h5>
      </div>
      <div class="modal-body">
        <input type="text" id="account-search" class="form-control mb-3" placeholder="Search by name or ID...">
        <div id="account-list" style="max-height: 400px; overflow-y: auto;">
          <p class="text-muted text-center">Loading accounts...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  async function fetchAccounts() {
    const res = await fetch('api/accounts/getAccounts');
    return res.ok ? res.json() : [];
  }

  function passesAccountRules(acc, {
    section = null,
    role = null,
    userProductionLocation = null
  }) {

    const accRole = (acc.role ?? '').toUpperCase();
    const targetRole = Array.isArray(role) ? role.map(r => r.toUpperCase()) : [(role ?? '').toUpperCase()];

    // Role check
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

    // Ensure userProductionLocation is always an array and normalized
    let targetLocations = Array.isArray(userProductionLocation) ? userProductionLocation : (userProductionLocation ? [userProductionLocation] : []);
    targetLocations = targetLocations.map(loc => loc.toLowerCase().replace(/[-\s]/g, ''));

    // Production check
    const isMatchingProduction = !section ?
      true :
      (targetSection === 'QC' ?
        accProductions.includes('QC') :
        accProductions.includes(targetSection));

    // Role check
    const isMatchingRole = !role || targetRole.includes(accRole);

    // Location check: ONLY match if user's locations include the target location
    const isMatchingLocation = !targetLocations.length ?
      true :
      accLocations.some(loc => targetLocations.includes(loc));

    return isMatchingProduction && isMatchingRole && isMatchingLocation;
  }

  function scanQRCodeForUser({
    onSuccess,
    onCancel,
    section = null,
    role = null,
    userProductionLocation = null
  }) {
    const modalElement = document.getElementById('accountSelectModal');
    const searchInput = document.getElementById('account-search');
    const listContainer = document.getElementById('account-list');
    const modal = new bootstrap.Modal(modalElement);

    listContainer.innerHTML = '<p class="text-muted text-center">Loading accounts...</p>';
    searchInput.value = '';
    modal.show();

    let accountData = [];

    fetchAccounts().then(accounts => {
      // Only keep authorized users
      accountData = accounts.filter(acc => {
        const match = passesAccountRules(acc, {
          section,
          role,
          userProductionLocation
        });

        return match;
      });

      renderAccountList(accountData);
    }).catch(() => {
      listContainer.innerHTML = '<p class="text-danger text-center">Failed to load accounts.</p>';
    });

    function renderAccountList(data) {
      listContainer.innerHTML = !data.length ?
        '<p class="text-muted text-center">No authorized accounts found.</p>' :
        data.map(acc => `
        <button class="list-group-item list-group-item-action"
                data-userid="${acc.user_id}"
                data-name="${acc.name}"
                data-production='${JSON.stringify(acc.section)}'
                data-location='${JSON.stringify(acc.specific_section)}'
                data-role="${acc.role}">
          <strong>${acc.name}</strong><br>
          <small>ID: ${acc.user_id}</small>
        </button>
      `).join('');
    }

    const clickHandler = e => {
      const btn = e.target.closest('button');
      if (!btn) return;

      onSuccess?.({
        user_id: btn.dataset.userid,
        full_name: btn.dataset.name,
        production: JSON.parse(btn.dataset.section || '[]'),
        production_location: JSON.parse(btn.dataset.location || '[]'),
        role: btn.dataset.role
      });

      modal.hide();
    };


    listContainer.removeEventListener('click', listContainer._clickHandler ?? (() => {}));
    listContainer.addEventListener('click', clickHandler);
    listContainer._clickHandler = clickHandler;


    const inputHandler = () => {
      const q = searchInput.value.toLowerCase();

      renderAccountList(
        accountData.filter(acc => {
          // normalize production
          let productions = [];
          try {
            productions = JSON.parse(acc.section || '[]');
          } catch {
            productions = [acc.section ?? ''];
          }
          productions = productions.map(p => p.toLowerCase());

          // normalize production_location
          let locations = [];
          try {
            locations = JSON.parse(acc.specific_section || '[]');
          } catch {
            locations = [acc.specific_section ?? ''];
          }
          locations = locations.map(l => l.toLowerCase());

          return acc.name.toLowerCase().includes(q) ||
            acc.user_id.toLowerCase().includes(q) ||
            productions.some(p => p.includes(q)) ||
            locations.some(l => l.includes(q));
        })
      );
    };


    searchInput.removeEventListener('input', searchInput._inputHandler ?? (() => {}));
    searchInput.addEventListener('input', inputHandler);
    searchInput._inputHandler = inputHandler;

    modalElement.removeEventListener('hidden.bs.modal', modalElement._hideHandler ?? (() => {}));
    const hideHandler = () => {
      onCancel?.();
      listContainer.innerHTML = '';
      listContainer.removeEventListener('click', clickHandler);
      searchInput.removeEventListener('input', inputHandler);
    };
    modalElement.addEventListener('hidden.bs.modal', hideHandler, {
      once: true
    });
    modalElement._hideHandler = hideHandler;
  }
</script>