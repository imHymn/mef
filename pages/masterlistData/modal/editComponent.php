<div class="modal fade" id="editComponentModal" tabindex="-1" role="dialog" aria-labelledby="editComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editComponentModalLabel">Edit Component</h5>
                <button type="button" class="close closeEditComponentBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-row">
                    <input type="hidden" id="editComponentId">

                    <div class="form-group col-md-2">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="editComponentMaterialNo" readonly>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Component Name</label>
                        <input type="text" class="form-control" id="editComponentName" readonly>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="editComponentInventory">
                    </div>
                    <div class="form-group col-md-1">
                        <label>Usage</label>
                        <input type="number" class="form-control" id="editComponentUsage" placeholder="Enter Usage" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group col-md-3 align-items-center mb-0">
                        <label>Source</label>
                        <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mb-0 p-0  w-75">
                                <input class="form-check-input m-0" type="radio" name="editComponentSource" id="editSourceStamping" value="stamping">
                                <label class="form-check-label ml-1 mr-3 mb-0" for="editSourceStamping">Stamping SKU/Muffler Component</label>
                            </div>
                            <div class="form-check form-check-inline mb-0 p-0">
                                <input class="form-check-input m-0" type="radio" name="editComponentSource" id="editSourceSupplied" value="supplied">
                                <label class="form-check-label ml-1 mb-0" for="editSourceSupplied">PPIC</label>
                            </div>
                        </div>

                    </div>


                </div>


                <hr>

                <div class="mb-2 text-right">
                    <button type="button" class="btn btn-sm btn-success" id="addStageRowBtn">Add Process</button>
                </div>
                <table class="table table-bordered table-sm" id="editStageTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Section</th>
                            <th>Process</th>
                            <th>Cycle Time</th>
                            <th>Machine</th>
                            <th>Manpower</th> <!-- ✅ Added -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeEditComponentBtn" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditComponentBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        // Global modal close
        window.closeEditComponentModal = function() {
            $('#editComponentModal').modal('hide');
        };
        Array.from(document.getElementsByClassName('closeEditComponentBtn')).forEach(btn =>
            btn.addEventListener('click', window.closeEditComponentModal)
        );

        // Open modal and populate fields
        window.openEditComponent = function(item) {
            try {
                document.getElementById('editComponentId').value = item.id || '';
                document.getElementById('editComponentMaterialNo').value = item.material_no || '';
                document.getElementById('editComponentName').value = item.components_name || '';
                document.getElementById('editComponentInventory').value = item.actual_inventory || '';
                document.getElementById('editComponentUsage').value = item.usage_type || '';

                // Set radio
                const sourceType = item.process || '';
                document.getElementsByName('editComponentSource').forEach(r => r.checked = r.value === sourceType);

                const tbody = document.querySelector('#editStageTable tbody');
                if (!tbody) return console.warn('editStageTable tbody not found');

                tbody.innerHTML = '';
                const stageData = item.stage_name ? JSON.parse(item.stage_name) : [];

                let rowIndex = 1;
                stageData.forEach(sectionData => {
                    const section = sectionData.section || '';
                    const stages = sectionData.stages || {};
                    Object.entries(stages).forEach(([stageName, info]) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                        <td>${rowIndex++}</td>
                        <td><input type="text" class="form-control form-control-sm" value="${section}"></td>
                        <td><input type="text" class="form-control form-control-sm" value="${stageName}"></td>
                        <td><input type="number" class="form-control form-control-sm" value="${info.cycle || 0}"></td>
                        <td><input type="text" class="form-control form-control-sm" value="${info.machine || ''}"></td>
                        <td><input type="number" class="form-control form-control-sm" value="${info.manpower || 1}" min="1"></td>
                        <td><button type="button" class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
                    `;
                        tbody.appendChild(tr);
                    });
                });

                updateRowNumbers();
                makeRowsDraggable();
                $('#editComponentModal').modal('show');
            } catch (err) {
                console.error('openEditComponent error:', err);
            }
        };

        // Add new row
        const addBtn = document.getElementById('addStageRowBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                const tbody = document.querySelector('#editStageTable tbody');
                if (!tbody) return;
                const rowCount = tbody.rows.length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${rowCount + 1}</td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="number" class="form-control form-control-sm" value="0"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="number" class="form-control form-control-sm" value="1" min="1"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
            `;
                tbody.appendChild(tr);
                updateRowNumbers();
                makeRowsDraggable();
            });
        }

        // Remove row (delegated)
        const tbody = document.querySelector('#editStageTable tbody');
        if (tbody) {
            tbody.addEventListener('click', e => {
                if (e.target.classList.contains('remove-row-btn')) {
                    e.target.closest('tr').remove();
                    updateRowNumbers();
                }
            });
        }

        // Update row numbers
        function updateRowNumbers() {
            const rows = document.querySelectorAll('#editStageTable tbody tr');
            rows.forEach((r, i) => r.cells[0].textContent = i + 1);
        }

        /* ========== DRAG & DROP ========== */
        let draggedRow = null;

        function makeRowsDraggable() {
            const rows = document.querySelectorAll('#editStageTable tbody tr');
            rows.forEach(row => {
                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', e => {
                    draggedRow = row;
                    e.dataTransfer.effectAllowed = 'move';
                    row.style.opacity = '0.5';
                });

                row.addEventListener('dragend', e => {
                    row.style.opacity = '';
                    draggedRow = null;
                    updateRowNumbers();
                });

                row.addEventListener('dragover', e => {
                    e.preventDefault();
                    const target = e.target.closest('tr');
                    if (target && target !== draggedRow) {
                        const bounding = target.getBoundingClientRect();
                        const offset = bounding.y + bounding.height / 2;
                        if (e.clientY - offset > 0) {
                            target.parentNode.insertBefore(draggedRow, target.nextSibling);
                        } else {
                            target.parentNode.insertBefore(draggedRow, target);
                        }
                    }
                });
            });
        }

        // Save button
        const saveBtn = document.getElementById('saveEditComponentBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                try {
                    const rows = document.querySelectorAll('#editStageTable tbody tr');
                    const structuredData = [];
                    const model = localStorage.getItem('selectedModel') || '';
                    rows.forEach(row => {
                        const inputs = row.querySelectorAll('input');
                        const section = inputs[0] ? inputs[0].value.trim() : '';
                        const stage = inputs[1] ? inputs[1].value.trim() : '';
                        const cycle = Number(inputs[2] ? inputs[2].value : 0);
                        const machine = inputs[3] ? inputs[3].value.trim() : '';
                        const manpower = Number(inputs[4] ? inputs[4].value : 1);

                        let sectionObj = structuredData.find(s => s.section === section);
                        if (!sectionObj) {
                            sectionObj = {
                                section,
                                stages: {}
                            };
                            structuredData.push(sectionObj);
                        }

                        sectionObj.stages[stage] = {
                            cycle,
                            machine,
                            manpower
                        };
                    });

                    const payload = {
                        id: document.getElementById('editComponentId').value,
                        material_name: document.getElementById('editComponentMaterialNo').value.trim(),
                        components_name: document.getElementById('editComponentName').value.trim(),
                        stage_name: JSON.stringify(structuredData),
                        process: document.querySelector('input[name="editComponentSource"]:checked')?.value || '',
                        actual_inventory: document.getElementById('editComponentInventory').value.trim(),
                        usage: document.getElementById('editComponentUsage').value.trim(),
                        model
                    };

                    fetch('api/masterlist/updateComponent', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.success) {
                                showAlert('success', 'Updated', 'Component updated successfully!');
                                setTimeout(() => window.location.reload(), 2000);
                                $('#editComponentModal').modal('hide');
                            } else {
                                showAlert('error', 'Failed', response.message || 'Update failed.');
                            }
                        })
                        .catch(err => {
                            console.error('Error updating component:', err);
                            showAlert('error', 'Error', 'Something went wrong.');
                        });

                } catch (err) {
                    console.error('Save component error:', err);
                }
            });
        }

    })();
</script>