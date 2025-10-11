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

                    <div class="form-group col-md-3">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="editMaterialNo" readonly>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Material Description</label>
                        <input type="text" class="form-control" id="editMaterialDesc" readonly>
                    </div>

                    <div class="form-group col-md-2">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="editQuantity">
                    </div>

                    <div class="form-group col-md-2">
                        <label>Fuel Type</label>
                        <select class="form-control" id="editFuelType">
                            <option value="">Select Fuel Type</option>
                            <option value="GAS">Gasoline</option>
                            <option value="DIESEL">Diesel</option>
                        </select>
                    </div>

                    <div class="form-group col-md-2 ml-3">
                        <label>SKU from Stamping</label><br>
                        <input class="form-check-input ml-5" type="checkbox" id="isStampingSKU">
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
    (function() { // Helper to normalize empty values
        function normalizeValue(val) {
            if (val === '' || val === undefined || val === null) return null;
            if (Array.isArray(val) && val.length === 0) return null;
            return val;
        }

        function safeParse(json) {
            if (!json || json === 'null' || json === '[]') return [];
            try {
                const parsed = JSON.parse(json);
                return Array.isArray(parsed) ? parsed : [];
            } catch {
                return [];
            }
        }
        // ensure global functions (so parent can call them regardless of include order)
        window.closeEditSkuModal = function() {
            $('#editSkuModal').modal('hide');
        };

        // attach close buttons safely (no error if none)
        const closeButtons = document.getElementsByClassName('closeEditSkuBtn');
        Array.from(closeButtons || []).forEach(btn => {
            btn.addEventListener('click', window.closeEditSkuModal);
        });

        window.openEditModal = function(item) {
            console.log(item);
            try {
                // basic fields
                const elMaterialNo = document.getElementById('editMaterialNo');
                const elMaterialDesc = document.getElementById('editMaterialDesc');
                const elQuantity = document.getElementById('editQuantity');
                const elFuelType = document.getElementById('editFuelType'); // ✅ added
                const elId = document.getElementById('id');

                // check if this SKU is from stamping
                const isStampingCheckbox = document.getElementById('isStampingSKU');
                if (isStampingCheckbox) {
                    isStampingCheckbox.checked = item.process && item.process.toLowerCase() === 'stamping';
                }

                if (elMaterialNo) elMaterialNo.value = item.material_no || '';
                if (elMaterialDesc) elMaterialDesc.value = item.material_description || '';
                if (elQuantity) elQuantity.value = item.quantity || '';
                if (elFuelType) elFuelType.value = item.fuel_type || ''; // ✅ added
                if (elId) elId.value = item.id || '';

                const subComponents = safeParse(item.sub_component);
                const assemblySections = safeParse(item.assembly_section);
                const assemblyProcesses = safeParse(item.assembly_process);
                const processTimes = safeParse(item.assembly_processtime);
                const manpower = safeParse(item.manpower);

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

        const saveBtn = document.getElementById('saveEditSkuBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                try {
                    const rows = document.querySelectorAll('#editAssemblyTable tbody tr');

                    // Initialize arrays
                    const sub_component = [];
                    const assembly_process = [];
                    const assembly_section = [];
                    const assembly_processtime = [];
                    const manpower = [];

                    rows.forEach(row => {
                        const inputs = row.querySelectorAll('input');
                        sub_component.push(inputs[0]?.value.trim() || '');
                        assembly_process.push(inputs[1]?.value.trim() || '');
                        assembly_section.push(inputs[2]?.value.trim() || '');
                        assembly_processtime.push(Number(inputs[3]?.value || 0));
                        manpower.push(Number(inputs[4]?.value || 0));
                    });

                    const updatedItem = {
                        id: normalizeValue(document.getElementById('id')?.value),
                        material_no: normalizeValue(document.getElementById('editMaterialNo')?.value),
                        material_description: normalizeValue(document.getElementById('editMaterialDesc')?.value),
                        quantity: normalizeValue(document.getElementById('editQuantity')?.value),
                        fuel_type: normalizeValue(document.getElementById('editFuelType')?.value), // ✅ added
                        total_process: rows.length,
                        sub_component: normalizeValue(sub_component),
                        assembly_section: normalizeValue(assembly_section),
                        assembly_process: normalizeValue(assembly_process),
                        assembly_processtime: normalizeValue(assembly_processtime),
                        manpower: normalizeValue(manpower),
                        process: document.getElementById('isStampingSKU').checked ? 'stamping' : null
                    };

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