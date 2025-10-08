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
  function getTasks(selectedItems) {
    if (!Array.isArray(selectedItems) || selectedItems.length === 0) return;

    const first = selectedItems[0];
    console.log(first.section);

    scanQRCodeForUser({
      section: 'STAMPING',
      role: 'OPERATOR',
      userProductionLocation: first.section,
      onSuccess: ({
        user_id,
        full_name
      }) => {
        // Keep keys consistent with renderTasks / renderStampingTable
        const itemsToInject = selectedItems.map(item => ({
          id: item.id,
          material_no: item.material_no,
          components_name: item.components_name, // keep original
          stage_name: item.stage_name, // keep original
          section: item.section, // keep original
          quantity: item.total_quantity,
          by_order: null,
          model: item.model // pass correct name
        }));

        renderTasks(user_id, full_name, itemsToInject);
      }
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
            <th style="width:40px; text-align:center;">#</th>
            <th style="width:150px; text-align:center;">Material No</th>
            <th style="text-align:left;">Components Name</th>
            <th style="width:140px; text-align:center;">Process</th>
            <th style="width:120px; text-align:center;">Section</th>
             <th style="width:40px; text-align:center;">Quantity</th>
             <th style="width:80px; text-align:center;">Order</th>
           
          </tr>
        </thead>
        <tbody>
          ${items.map((item, i) => `
            <tr draggable="true"
                data-id="${item.id ?? ''}"
                data-section="stamping"
                data-material_no="${item.material_no ?? ''}"
                data-components_name="${item.components_name ?? ''}"
                data-stage_name="${item.stage_name ?? ''}">
              <td class="handle text-center">${i + 1}</td>
              <td class="text-center">${item.material_no ?? ''}</td>
              <td class="text-start">${item.components_name ?? ''}</td>
              <td class="text-center">${item.stage_name ?? ''}</td>
              <td class="text-center">${item.section ?? ''}</td>
 <td class="text-center">${item.quantity ?? ''}</td>
              
              <td class="order-col text-center">${item.by_order ?? i + 1}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
  }


  function renderTasks(user_id, full_name, selectedItems = []) {
    const model = selectedItems[0]?.model || 'L300 DIRECT';

    fetch(`api/stamping/getSpecificData_assigned?model=${encodeURIComponent(model)}&_=${Date.now()}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          full_name
        })
      })
      .then(r => r.json())
      .then(res => {
        const stampingItems = (res.items || [])
          .filter(item => item.id && item.stage_name)
          .map(item => ({
            ...item,
            quantity: item.total_quantity ?? 0, // 👈 map backend "total_quantity" to "quantity"
            _section: 'stamping'
          }))
          .sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));

        // Inject selected items
        selectedItems.forEach(item => {
          stampingItems.push({
            id: item.id,
            material_no: item.material_no,
            components_name: item.components_name,
            stage_name: item.stage_name,
            section: item.section,
            model: item.model,
            quantity: item.quantity,
            by_order: null,
            _section: 'stamping'
          });
        });

        const tableHTML = renderStampingTable(stampingItems);

        Swal.fire({
          icon: 'info',
          title: `${full_name}`,
          html: tableHTML,
          width: 1000,
          showCancelButton: true,
          confirmButtonText: 'Save Order',
          cancelButtonText: 'Close',
          didOpen: () => enableDragAndDrop()
        }).then(result => {
          if (result.isConfirmed) {
            const rows = [...document.querySelectorAll(`#stamping-tasks-table tbody tr`)];
            rows.forEach((tr, index) => {
              assign(tr.dataset.id, index + 1, full_name, 'stamping');
            });
          }
        });
      })
      .catch(err => {
        console.error(err);
        showAlert('error', 'Network Error', 'Please try again.');

      });
  }


  function assign(id, by_order, full_name, section) {
    let url;
    if (section === 'assembly') {
      url = 'api/assembly/assignOperator';
    } else if (section === 'finishing') {
      url = 'api/finishing/assignOperator';
    } else if (section === 'stamping') {
      url = 'api/stamping/assignOperator';
    }

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
          window.location.reload();
        } else {
          showAlert('error', 'Assignment Failed', data.message || `Failed to assign task #${id}.`);
        }
      })
      .catch(err => {
        console.error('Error assigning:', err);
        showAlert('error', 'Error', 'An unexpected error occurred.');
      });

  }

  function enableDragAndDrop() {
    ['#assembly-tasks-table tbody', '#finishing-tasks-table tbody', '#stamping-tasks-table tbody']
    .forEach(selector => {
      const tbody = document.querySelector(selector);
      if (tbody) {
        Sortable.create(tbody, {
          animation: 150,
          handle: '.handle', // allow dragging from the number cell
          onEnd: () => updateOrderNumbers(tbody)
        });
      }
    });
  }

  function updateOrderNumbers(tbody) {
    tbody.querySelectorAll('tr').forEach((tr, index) => {
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
    const targetRole = Array.isArray(role) ? role.map(r => r.toUpperCase()) : [(role ?? '').toUpperCase()];

    if (accRole === 'ADMINISTRATOR' && targetRole.includes('ADMINISTRATOR')) return true;
    if (accRole === 'ADMINISTRATOR' && targetRole.includes('OPERATOR')) return false;

    let accProductions = [];
    try {
      accProductions = JSON.parse(acc.section || '[]');
    } catch {
      accProductions = [acc.section ?? ''];
    }
    accProductions = accProductions.map(p => p.toUpperCase());

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

    const isMatchingProduction = !section || (targetSection === 'QC' ? accProductions.some(p => p.includes('QC')) : accProductions.includes(targetSection));
    const isMatchingRole = !role || targetRole.includes(accRole);
    const isMatchingLocation = targetSection === 'QC' ? true : (!targetLocations.length || accLocations.some(loc => targetLocations.includes(loc)));

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