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

                    <div class="form-group col-md-3">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="editComponentMaterialNo" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Component Name</label>
                        <input type="text" class="form-control" id="editComponentName" readonly>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Inventory</label>
                        <input type="number" class="form-control" id="editComponentInventory">
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
        window.openEditComponent = function(item) {
            console.log('Editing item:', item);
            document.getElementById('editComponentId').value = item.id || '';
            document.getElementById('editComponentMaterialNo').value = item.material_no || '';
            document.getElementById('editComponentName').value = item.components_name || '';
            document.getElementById('editComponentInventory').value = item.actual_inventory || '';

            // ✅ Set radio button value
            if (item.process === 'stamping') {
                document.getElementById('editSourceStamping').checked = true;
            } else if (item.process === 'supplied') {
                document.getElementById('editSourceSupplied').checked = true;
            } else {
                document.getElementsByName('editComponentSource').forEach(r => r.checked = false);
            }

            const tbody = document.querySelector('#editStageTable tbody');
            tbody.innerHTML = '';

            const stageData = parseJSONSafe(item.stage_name);
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
                    <td><input type="number" class="form-control form-control-sm" value="${info.cycle || 0}" ></td>
                    <td><input type="text" class="form-control form-control-sm" value="${info.machine || ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" value="${info.manpower || 1}" min="1"></td> <!-- ✅ Added manpower -->
                    <td><button class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
                `;
                    tbody.appendChild(tr);
                });
            });

            updateRowNumbers();
            $('#editComponentModal').modal('show');
        };

        // ✅ Add new row (includes manpower)
        document.getElementById('addStageRowBtn').addEventListener('click', () => {
            const tbody = document.querySelector('#editStageTable tbody');
            const rowCount = tbody.rows.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${rowCount + 1}</td>
            <td><input type="text" class="form-control form-control-sm"></td>
            <td><input type="text" class="form-control form-control-sm"></td>
            <td><input type="number" class="form-control form-control-sm" value="0"></td>
            <td><input type="text" class="form-control form-control-sm"></td>
            <td><input type="number" class="form-control form-control-sm" value="1" min="1"></td> <!-- ✅ Added manpower default -->
            <td><button class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
        `;
            tbody.appendChild(tr);
            updateRowNumbers();
        });

        // ✅ Remove row event
        document.querySelector('#editStageTable tbody').addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-row-btn')) {
                e.target.closest('tr').remove();
                updateRowNumbers();
            }
        });

        function updateRowNumbers() {
            const rows = document.querySelectorAll('#editStageTable tbody tr');
            rows.forEach((r, i) => r.cells[0].textContent = i + 1);
        }

        // ✅ Save button logic
        document.getElementById('saveEditComponentBtn').addEventListener('click', () => {
            const rows = document.querySelectorAll('#editStageTable tbody tr');
            const structuredData = [];
            const customer_name = localStorage.getItem('selectedCustomer') || '';
            const model = localStorage.getItem('selectedModel') || '';

            rows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                const section = inputs[0].value.trim();
                const stage = inputs[1].value.trim();
                const cycle = Number(inputs[2].value);
                const machine = inputs[3].value.trim();
                const manpower = Number(inputs[4].value) || 1; // ✅ Default to 1

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

            const sourceType = document.querySelector('input[name="editComponentSource"]:checked')?.value || '';
            const material_name = document.getElementById('editComponentMaterialNo').value.trim();
            const components_name = document.getElementById('editComponentName').value.trim();
            const actual_inventory = document.getElementById('editComponentInventory').value.trim();

            const payload = {
                id: document.getElementById('editComponentId').value,
                material_name,
                components_name,
                stage_name: JSON.stringify(structuredData),
                process: sourceType,
                customer_name,
                model,
                actual_inventory
            };
            console.log('Payload to be sent:', payload);
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
                        $('#editComponentModal').modal('hide');
                    } else {
                        showAlert('error', 'Failed', response.message || 'Update failed.');
                    }
                })
                .catch(err => {
                    console.error('Error updating component:', err);
                    showAlert('error', 'Error', 'Something went wrong.');
                });
        });

        function parseJSONSafe(str) {
            try {
                return str ? JSON.parse(str) : [];
            } catch {
                return [];
            }
        }

        window.closeEditComponentModal = function() {
            $('#editComponentModal').modal('hide');
        };

        Array.from(document.getElementsByClassName('closeEditComponentBtn')).forEach(btn =>
            btn.addEventListener('click', window.closeEditComponentModal)
        );
    })();
</script>