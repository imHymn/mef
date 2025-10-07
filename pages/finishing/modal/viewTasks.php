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
    function viewTasks(userProductionLocation) {
        scanQRCodeForUser({
            section: 'FINISHING',
            role: 'OPERATOR',
            userProductionLocation: userProductionLocation,
            onSuccess: ({
                user_id,
                full_name
            }) => {
                renderTasks(user_id, full_name, null);
            }
        });
    }

    function renderAssemblyTable(items) {
        if (!items.length) return `<p class="text-muted">No Assembly tasks</p>`;
        return `
    <h6 class="mt-2 mb-1 text-start text-uppercase">Assembly</h6>
    <div style="max-height:200px; overflow-y:auto; border:1px solid #dee2e6;">
      <table class="table table-bordered table-sm text-center align-middle mb-0" id="assembly-tasks-table">
        <thead class="table-light">
          <tr>
            <th style="width:40px;">#</th>
            <th>Material No</th>
            <th>Material Description</th>
            <th>Sub Component</th>
            <th>Assembly Process</th>
            <th>Order</th>
          </tr>
        </thead>
        <tbody>
          ${items.map((item, i) => `
            <tr draggable="true"
                data-id="${item.id ?? ''}"
                data-section="assembly"
                data-sub_component="${item.sub_component ?? ''}"
                data-assembly_process="${item.assembly_process ?? ''}">
              <td class="handle">${i + 1}</td>
              <td>${item.material_no ?? ''}</td>
              <td>${item.material_description ?? ''}</td>
              <td>${item.sub_component ?? ''}</td>
              <td>${item.assembly_process ?? ''}</td>
              <td class="order-col">${item.by_order ?? i + 1}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
    }

    function renderFinishingTable(items) {
        if (!items.length) return `<p class="text-muted">No Finishing tasks</p>`;
        return `
    <h6 class="mt-2 mb-1 text-start text-uppercase">Finishing</h6>
    <div style="max-height:200px; overflow-y:auto; border:1px solid #dee2e6;">
      <table class="table table-bordered table-sm text-center align-middle mb-0" id="finishing-tasks-table">
        <thead class="table-light">
          <tr>
            <th style="width:40px;">#</th>
            <th>Material No</th>
            <th>Description</th>
            <th>Rework</th>
            <th>Replace</th>
            <th>Order</th>
          </tr>
        </thead>
        <tbody>
          ${items.map((item, i) => `
            <tr draggable="true"
                data-id="${item.id ?? ''}"
                data-section="finishing"
                data-material_no="${item.material_no ?? ''}"
                data-description="${item.material_description ?? ''}">
              <td class="handle">${i + 1}</td>
              <td>${item.material_no ?? ''}</td>
              <td>${item.material_description ?? ''}</td>
              <td>${item.rework ?? ''}</td>
              <td>${item.replace ?? ''}</td>
              <td class="order-col">${item.by_order ?? i + 1}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
    }

    function renderTasks(user_id, full_name, selectedItem = null, section = null) {
        const model = selectedItem?.model || 'L300 DIRECT';

        Promise.all([
                fetch(`api/assembly/getAllModelData_assigned?model=${encodeURIComponent(model)}&_=${Date.now()}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name
                    })
                }).then(r => r.json()),
                fetch(`api/finishing/getAllModelData_assigned?model=${encodeURIComponent(model)}&_=${Date.now()}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name
                    })
                }).then(r => r.json())
            ])
            .then(([assemblyRes, finishingRes]) => {
                if (!assemblyRes.success && !finishingRes.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load tasks from both assembly and finishing.'
                    });
                    return;
                }

                let assemblyItems = (assemblyRes.items || [])
                    .filter(item => item.id && (item.assembly_process || item.sub_component))
                    .map(item => ({
                        ...item,
                        _section: 'assembly'
                    }))
                    .sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));

                let finishingItems = (finishingRes.items || [])
                    .filter(item => item.id && item.material_no && item.material_description)
                    .map(item => ({
                        ...item,
                        _section: 'finishing'
                    }))
                    .sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));

                // ✅ Include selectedItem if not already in the lists
                if (selectedItem?.id) {
                    const itemWithSection = {
                        ...selectedItem,
                        _section: section || 'assembly'
                    };
                    if (itemWithSection._section === 'finishing') {
                        itemWithSection.rework ??= '';
                        itemWithSection.replace ??= '';
                        finishingItems.push(itemWithSection);
                        finishingItems.sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));
                    } else {
                        itemWithSection.sub_component ??= '';
                        itemWithSection.assembly_process ??= '';
                        assemblyItems.push(itemWithSection);
                        assemblyItems.sort((a, b) => (a.by_order ?? 9999) - (b.by_order ?? 9999));
                    }
                }

                const tableHTML = `
        ${renderAssemblyTable(assemblyItems)}
        ${renderFinishingTable(finishingItems)}
      `;

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
                        ['assembly', 'finishing'].forEach(sec => {
                            const rows = [...document.querySelectorAll(`#${sec}-tasks-table tbody tr`)];
                            rows.forEach((tr, index) => {
                                const id = tr.dataset.id;
                                const by_order = index + 1;
                                assign(id, by_order, full_name, sec);
                            });
                        });
                    }
                });
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Please try again.'
                });
            });
    }



    function assign(id, by_order, full_name, section) {
        const url = section === 'assembly' ?
            'api/assembly/assignOperator.php' :
            'api/finishing/assignOperator.php';

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Assigned!',
                        text: `Task #${id} has been successfully assigned to ${full_name}.`
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Assignment Failed',
                        text: data.message || `Failed to assign task #${id}.`
                    });
                }
            })
            .catch(err => {
                console.error('Error assigning:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred.'
                });
            });
    }
    // 🔹 Enable drag & drop per table
    function enableDragAndDrop() {
        const tables = [
            document.getElementById('assembly-tasks-table'),
            document.getElementById('finishing-tasks-table')
        ].filter(Boolean);

        tables.forEach((table) => {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            Sortable.create(tbody, {
                animation: 150,
                handle: '.handle',
                onEnd: () => updateOrderNumbers(tbody)
            });
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