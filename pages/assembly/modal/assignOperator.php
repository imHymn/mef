<!-- Add this once -->
<script src="assets/js/sortable.min.js"></script>

<div class="modal fade" id="accountSelectModal" tabindex="-1" aria-labelledby="accountSelectLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="accountSelectLabel">Select Account</h5>
      </div>
      <div class="modal-body">
        <input type="text" id="account-search" class="form-control mb-3" placeholder="Search by name or ID...">
        <div id="account-list" class="list-group" style="max-height: 400px; overflow-y: auto;">
          <p class="text-muted text-center">Loading accounts...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function getTasks(items) {
    if (!items.length) return;
    console.log(items);

    const type = items[0]?.type; // 'sku' or 'component'
    const section = type === 'sku' ? items[0]?.assembly_section : items[0]?.section;

    // Filter only the relevant items for this type
    const relevantItems = items.filter(item => item.type === type);

    // Determine which render function to use
    const renderFn = type === 'sku' ? renderAssemblyTasks : renderStampingTasks;

    scanQRCodeForUser({
      section: ['ASSEMBLY', 'FINISHING'],
      role: 'OPERATOR',
      userProductionLocation: section,
      onSuccess: ({
        user_id,
        full_name
      }) => renderFn(user_id, full_name, relevantItems)
    });
  }


  // ---------------- Assembly ----------------
  function renderAssemblyTasks(user_id, full_name, selectedItems = []) {
    const model = selectedItems?.[0]?.model || 'L300 DIRECT';
    const body = JSON.stringify({
      full_name
    });
    const headers = {
      'Content-Type': 'application/json'
    };
    const ts = Date.now();

    fetch(`api/assembly/getSpecificData_assigned?model=${encodeURIComponent(model)}&_=${ts}`, {
        method: 'POST',
        headers,
        body
      })
      .then(r => r.json())
      .then(data => {
        if (!data.success) {
          showAlert('error', 'Error', 'Failed to load assembly tasks.');
          return;
        }


        let items = (data.items || [])
          .filter(item => item.id && item.material_no && item.material_description)
          .sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));


        selectedItems
          .filter(sel => sel.type === 'sku') // only merge assembly items
          .forEach(sel => {
            items = items.filter(item => item.id !== sel.id);
            items.push(sel);
          });
        console.log(items)

        const html = `
        <h6 class="mt-2 mb-1 text-start text-uppercase">Assembly</h6>
        <div style="max-height:300px; overflow-y:auto; border:1px solid #dee2e6;">
          <table class="table table-bordered table-sm text-center align-middle" id="assembly-tasks-table">
            <thead class="table-light">
              <tr>
                <th style="width:40px;">#</th>
                <th>Material No</th>
                <th>Description</th>
                <th>Sub Component</th>
                <th>Process</th>
                <th>Order</th>
              </tr>
            </thead>
            <tbody>
              ${items.map((item, i) => `
                <tr draggable="true"
                    data-id="${item.id}"
                    data-material_no="${item.material_no}"
                    data-description="${item.material_description}"
                    data-sub_component="${item.sub_component}"
                    data-process="${item.assembly_process}">
                  <td class="handle">${i + 1}</td>
                  <td>${item.material_no}</td>
                  <td>${item.material_description}</td>
                  <td>${item.sub_component}</td>
                  <td>${item.assembly_process}</td>
                  <td class="order-col">${item.by_order ?? i + 1}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;

        Swal.fire({
          icon: 'info',
          title: full_name,
          html,
          width: 900,
          showCancelButton: true,
          confirmButtonText: 'Save Order',
          cancelButtonText: 'Close',
          didOpen: enableDragAndDrop
        }).then(result => {
          if (result.isConfirmed) {
            const rows = [...document.querySelectorAll('#assembly-tasks-table tbody tr')];
            rows.forEach((tr, i) => assign(tr.dataset.id, i + 1, full_name, 'assembly'));
          }
        });
      });
  }

  // ---------------- Stamping ----------------
  function renderStampingTasks(user_id, full_name, selectedItems = []) {
    console.log(selectedItems)
    const model = selectedItems?.[0]?.model || 'L300 DIRECT';
    const body = JSON.stringify({
      full_name
    });
    const headers = {
      'Content-Type': 'application/json'
    };
    const ts = Date.now();

    fetch(`api/stamping/getSpecificData_assigned?model=${encodeURIComponent(model)}&_=${ts}`, {
        method: 'POST',
        headers,
        body
      })
      .then(r => r.json())
      .then(data => {
        if (!data.success) {
          showAlert('error', 'Error', 'Failed to load stamping tasks.');
          return;
        }

        console.log('Raw data.items:', data);

        let items = (data.items || [])
          .filter(item => item.id && item.stage_name)
          .map(item => ({
            ...item,
            quantity: item.total_quantity ?? 0,
            _section: 'stamping'
          }));

        selectedItems
          .filter(sel => sel.type === 'component') // only merge stamping items
          .forEach(sel => {
            items = items.filter(item => item.id !== sel.id);
            items.push(sel);
          });


        // Sort by by_order
        items.sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));

        const html = renderStampingTable(items);
        Swal.fire({
          icon: 'info',
          title: full_name,
          html,
          width: 1000,
          showCancelButton: true,
          confirmButtonText: 'Save Order',
          cancelButtonText: 'Close',
          didOpen: (modal) => {
            const tbody = modal.querySelector('.swal2-html-container table tbody');
            if (tbody) new Sortable(tbody, {
              handle: '.handle',
              animation: 150
            });
          }
        }).then(result => {
          if (result.isConfirmed) {
            // Query the table inside the Swal modal
            const modalEl = document.querySelector('.swal2-container .swal2-html-container');
            const rows = [...modalEl.querySelectorAll('#stamping-tasks-table tbody tr')];
            rows.forEach((tr, i) => assign(tr.dataset.id, i + 1, full_name, 'stamping'));
          }
        });

      });
  }

  function renderStampingTable(items) {
    console.log(items)
    if (!items.length) return `<p class="text-muted">No Stamping tasks</p>`;
    return `
    <h6 class="mt-2 mb-1 text-start text-uppercase">Stamping</h6>
    <div style="max-height:200px; overflow-y:auto; border:1px solid #dee2e6;">
      <table class="table table-bordered table-sm mb-0" id="stamping-tasks-table">
        <thead class="table-light">
          <tr>
            <th style="width:40px;">#</th>
            <th style="width:150px;">Material No</th>
            <th>Components Name</th>
            <th style="width:140px;">Process</th>
            <th style="width:120px;">Section</th>
            <th style="width:40px;">Quantity</th>
            <th style="width:80px;">Order</th>
          </tr>
        </thead>
        <tbody>
          ${items.map((item, i) => `
            <tr draggable="true"
                data-id="${item.id}"
                data-material_no="${item.material_no}"
                data-components_name="${item.components_name}"
                data-stage_name="${item.stage_name}">
              <td class="handle">${i + 1}</td>
              <td>${item.material_no}</td>
              <td>${item.components_name}</td>
              <td>${item.stage_name}</td>
              <td>${item.section ?? ''}</td>
              <td>${item.quantity}</td>
              <td class="order-col">${item.by_order ?? i + 1}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
  }

  function assign(id, by_order, full_name, section) {
    const url = section === 'stamping' ?
      'api/stamping/assignOperator' :
      'api/assembly/assignOperator';

    fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id,
          by_order,
          person_incharge: full_name
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showAlert('success', 'Assigned!', `Task #${id} has been successfully assigned to ${full_name}.`);
          setTimeout(() => window.location.reload(), 2000);
        } else {
          showAlert('error', 'Assignment Failed', data.message || `Failed to assign task #${id}.`);
        }
      })
      .catch(err => {
        console.error('Error assigning:', err);
        showAlert('error', 'Error', 'An unexpected error occurred.');
      });
  }




  function enableDragAndDrop(container) {
    const el = container.querySelector('table tbody');
    if (!el) return;

    new Sortable(el, {
      handle: '.handle',
      animation: 150,
      onEnd: () => {
        el.querySelectorAll('tr').forEach((tr, i) => {
          tr.querySelector('.handle').textContent = i + 1;
          tr.querySelector('.order-col').textContent = i + 1;
        });
      }
    });
  }

  function updateOrderNumbers() {
    document.querySelectorAll('#tasks-table tbody tr').forEach((tr, index) => {
      tr.querySelector('.handle').textContent = index + 1;
      tr.querySelector('.order-col').textContent = index + 1;
    });
  }

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
    const targetRoles = Array.isArray(role) ?
      role.map(r => r.toUpperCase()) : [(role ?? '').toUpperCase()];

    if (accRole === 'ADMINISTRATOR' && targetRoles.includes('ADMINISTRATOR')) return true;
    if (accRole === 'ADMINISTRATOR' && targetRoles.includes('OPERATOR')) return false;

    // Normalize user's production
    let accProductions = [];
    try {
      accProductions = JSON.parse(acc.section || '[]');
    } catch {
      accProductions = [acc.section ?? ''];
    }
    accProductions = accProductions.map(p => p.toUpperCase());

    // Normalize user's production_location
    let accLocations = [];
    try {
      accLocations = JSON.parse(acc.specific_section || '[]');
    } catch {
      accLocations = [acc.specific_section ?? ''];
    }
    accLocations = accLocations.map(loc => loc.toLowerCase().replace(/[-\s]/g, ''));

    // ✅ Handle multiple sections
    const targetSections = Array.isArray(section) ?
      section.map(s => s.toUpperCase()) :
      section ? [section.toUpperCase()] : [];

    let targetLocations = Array.isArray(userProductionLocation) ?
      userProductionLocation :
      (userProductionLocation ? [userProductionLocation] : []);
    targetLocations = targetLocations.map(loc => loc.toLowerCase().replace(/[-\s]/g, ''));

    // ✅ Match production if ANY of the target sections match
    const isMatchingProduction = !targetSections.length ||
      accProductions.some(p =>
        targetSections.includes('QC') ?
        p.includes('QC') :
        targetSections.includes(p)
      );

    const isMatchingRole = !role || targetRoles.includes(accRole);
    const isMatchingLocation =
      targetSections.includes('QC') ?
      true :
      (!targetLocations.length || accLocations.some(loc => targetLocations.includes(loc)));

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
      accountData = accounts.filter(acc => passesAccountRules(acc, {
        section,
        role,
        userProductionLocation
      }));
      renderAccountList(accountData);
    }).catch(() => {
      listContainer.innerHTML = '<p class="text-danger text-center">Failed to load accounts.</p>';
    });

    function renderAccountList(data) {
      listContainer.innerHTML = !data.length ?
        '<p class="text-muted text-center">No authorized accounts found.</p>' :
        data.map(acc => `
          <button class="list-group-item list-group-item-action"
                  data-userid="${acc.user_id}" data-name="${acc.name}">
            <strong>${acc.name}</strong><br><small>ID: ${acc.user_id}</small>
          </button>
        `).join('');
    }

    const clickHandler = e => {
      const btn = e.target.closest('button');
      if (!btn) return;
      onSuccess?.({
        user_id: btn.dataset.userid,
        full_name: btn.dataset.name
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