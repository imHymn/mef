<div class="modal fade" id="editSkuModal" tabindex="-1" role="dialog" aria-labelledby="editSkuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document"> <!-- add modal-dialog-centered -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSkuModalLabel">Edit SKU Item</h5>
                <button type="button" class="close closeEditSkuBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Basic Fields -->
                <div class="form-row">

                    <input type="text" class="form-control" id="id" hidden>

                    <div class="form-group col-md-4">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="editMaterialNo" readonly>
                    </div>
                    <div class="form-group col-md-5">
                        <label>Material Description</label>
                        <input type="text" class="form-control" id="editMaterialDesc" readonly>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="editQuantity">
                    </div>
                </div>

                <hr>

                <div class="mb-2 text-right">
                    <button type="button" class="btn btn-sm btn-success" id="addAssemblyRowBtn">
                        Add Process
                    </button>
                </div>

                <table class="table table-bordered table-sm" id="editAssemblyTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Sub Component</th>
                            <th>Assembly Process</th>
                            <th>Assembly Section</th>
                            <th>Cycle Time</th>
                            <th>Manpower</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeEditSkuBtn" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditSkuBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        // ensure global functions (so parent can call them regardless of include order)
        window.closeEditSkuModal = function() {
            $('#editSkuModal').modal('hide');
        };

        // attach close buttons safely (no error if none)
        const closeButtons = document.getElementsByClassName('closeEditSkuBtn');
        Array.from(closeButtons || []).forEach(btn => {
            btn.addEventListener('click', window.closeEditSkuModal);
        });

        // open modal and populate fields
        window.openEditModal = function(item) {
            try {
                // basic fields
                const elMaterialNo = document.getElementById('editMaterialNo');
                const elMaterialDesc = document.getElementById('editMaterialDesc');
                const elQuantity = document.getElementById('editQuantity');
                const elId = document.getElementById('id');

                if (elMaterialNo) elMaterialNo.value = item.material_no || '';
                if (elMaterialDesc) elMaterialDesc.value = item.material_description || '';
                if (elQuantity) elQuantity.value = item.quantity || '';
                if (elId) elId.value = item.id || '';

                // parse arrays safely
                const subComponents = item.sub_component ? JSON.parse(item.sub_component) : [];
                const assemblySections = item.assembly_section ? JSON.parse(item.assembly_section) : [];
                const assemblyProcesses = item.assembly_process ? JSON.parse(item.assembly_process) : [];
                const processTimes = item.assembly_processtime ? JSON.parse(item.assembly_processtime) : [];
                const manpower = item.manpower ? JSON.parse(item.manpower) : [];

                const tbody = document.querySelector('#editAssemblyTable tbody');
                if (!tbody) return console.warn('editAssemblyTable tbody not found');

                tbody.innerHTML = '';
                for (let i = 0; i < Math.max(subComponents.length, assemblyProcesses.length, assemblySections.length, processTimes.length, manpower.length); i++) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${i + 1}</td>
                    <td><input type="text" class="form-control form-control-sm" value="${subComponents[i] || ''}"></td>
                    <td><input type="text" class="form-control form-control-sm" value="${assemblyProcesses[i] || ''}"></td>
                    <td><input type="text" class="form-control form-control-sm" value="${assemblySections[i] || ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" value="${processTimes[i] || 0}"></td>
                    <td><input type="number" class="form-control form-control-sm" value="${manpower[i] || 0}"></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
                `;
                    tbody.appendChild(tr);
                }
                updateRowNumbers();
                $('#editSkuModal').modal('show');
            } catch (err) {
                console.error('openEditModal error:', err);
            }
            makeRowsDraggable();

        };

        // add row (guarded)
        const addBtn = document.getElementById('addAssemblyRowBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                const tbody = document.querySelector('#editAssemblyTable tbody');
                if (!tbody) return;
                const rowCount = tbody.rows.length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${rowCount + 1}</td>
                <td><input type="text" class="form-control form-control-sm" value=""></td>
                <td><input type="text" class="form-control form-control-sm" value=""></td>
                <td><input type="text" class="form-control form-control-sm" value=""></td>
                <td><input type="number" class="form-control form-control-sm" value="0"></td>
                <td><input type="number" class="form-control form-control-sm" value="0"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
            `;
                tbody.appendChild(tr);
                updateRowNumbers();
                makeRowsDraggable();
            });


        }

        // remove row (delegated)
        const editAssemblyTbody = document.querySelector('#editAssemblyTable tbody');
        if (editAssemblyTbody) {
            editAssemblyTbody.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-row-btn')) {
                    e.target.closest('tr').remove();
                    updateRowNumbers();
                }
            });
        }

        // Row number updater
        function updateRowNumbers() {
            const rows = document.querySelectorAll('#editAssemblyTable tbody tr');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
        }

        /* ========== DRAG & DROP SORTING ========== */
        let draggedRow = null;

        const tbody = document.querySelector('#editAssemblyTable tbody');
        if (tbody) {
            tbody.addEventListener('dragstart', (e) => {
                draggedRow = e.target.closest('tr');
                e.dataTransfer.effectAllowed = 'move';
                e.target.style.opacity = '0.5';
            });

            tbody.addEventListener('dragend', (e) => {
                e.target.style.opacity = '';
                draggedRow = null;
                updateRowNumbers(); // refresh numbering after drop
            });

            tbody.addEventListener('dragover', (e) => {
                e.preventDefault(); // allow drop
                const targetRow = e.target.closest('tr');
                if (targetRow && targetRow !== draggedRow) {
                    const bounding = targetRow.getBoundingClientRect();
                    const offset = bounding.y + bounding.height / 2;
                    if (e.clientY - offset > 0) {
                        targetRow.parentNode.insertBefore(draggedRow, targetRow.nextSibling);
                    } else {
                        targetRow.parentNode.insertBefore(draggedRow, targetRow);
                    }
                }
            });
        }

        // make rows draggable
        function makeRowsDraggable() {
            const rows = document.querySelectorAll('#editAssemblyTable tbody tr');
            rows.forEach(row => row.setAttribute('draggable', 'true'));
        }

        // call this after you build or modify rows

        // Save (guarded)
        const saveBtn = document.getElementById('saveEditSkuBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                try {
                    const updatedItem = {
                        id: document.getElementById('id') ? document.getElementById('id').value : '',
                        material_no: document.getElementById('editMaterialNo') ? document.getElementById('editMaterialNo').value : '',
                        material_description: document.getElementById('editMaterialDesc') ? document.getElementById('editMaterialDesc').value : '',
                        quantity: document.getElementById('editQuantity') ? document.getElementById('editQuantity').value : '',
                        total_process: 0,
                        sub_component: [],
                        assembly_section: [],
                        assembly_process: [],
                        assembly_processtime: [],
                        manpower: []
                    };

                    const rows = document.querySelectorAll('#editAssemblyTable tbody tr');
                    rows.forEach(row => {
                        const inputs = row.querySelectorAll('input');
                        updatedItem.sub_component.push(inputs[0] ? inputs[0].value.trim() : '');
                        updatedItem.assembly_process.push(inputs[1] ? inputs[1].value.trim() : '');
                        updatedItem.assembly_section.push(inputs[2] ? inputs[2].value.trim() : '');
                        updatedItem.assembly_processtime.push(Number(inputs[3] ? inputs[3].value : 0));
                        updatedItem.manpower.push(Number(inputs[4] ? inputs[4].value : 0));
                    });

                    updatedItem.total_process = rows.length;

                    fetch('api/masterlist/updateSKU', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(updatedItem)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.success) {
                                showAlert('success', 'Success', 'SKU updated successfully!');
                                $('#editSkuModal').modal('hide');
                            } else {
                                showAlert('error', 'Failed', 'Failed to update SKU: ' + (response.message || ''));
                            }
                        })
                        .catch(err => {
                            console.error('Error updating SKU:', err);
                            showAlert('error', 'Error', 'Error updating SKU. Check console for details.');
                        });
                } catch (err) {
                    console.error('saveEditSkuBtn handler error:', err);
                }
            });
        }
        makeRowsDraggable();

    })();
</script>