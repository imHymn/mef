<div class="modal fade" id="addSkuModal" tabindex="-1" role="dialog" aria-labelledby="addSkuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSkuModalLabel">Add New SKU</h5>
                <button type="button" class="close closeAddSkuBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Basic Fields -->
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="addMaterialNo">
                    </div>

                    <div class="form-group col-md-3">
                        <label>Material Description</label>
                        <input type="text" class="form-control" id="addMaterialDesc">
                    </div>

                    <div class="form-group col-md-2">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="addQuantity" value="0">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Fuel Type</label>
                        <select class="form-control" id="addSkuFuelType">
                            <option value="">Select Fuel Type</option>
                            <option value="GAS">Gasoline</option>
                            <option value="DIESEL">Diesel</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-flex flex-column justify-content-center align-items-center">
                        <label class="mb-1">Directly from Stamping</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="addFromStamping" style="margin-top:0px;">
                            <label class="form-check-label ml-0" for="addFromStamping">Yes</label>
                        </div>
                    </div>
                </div>



                <hr>

                <div class="mb-2 text-right">
                    <button type="button" class="btn btn-sm btn-success" id="addNewAssemblyRowBtn">
                        Add Process
                    </button>
                </div>

                <table class="table table-bordered table-sm" id="addAssemblyTable">
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
                <button type="button" class="btn btn-secondary closeAddSkuBtn" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAddSkuBtn">Save SKU</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        window.openAddModal = function() {
            document.getElementById('addMaterialNo').value = '';
            document.getElementById('addMaterialDesc').value = '';
            document.getElementById('addQuantity').value = 0;
            document.getElementById('addFromStamping').checked = false; // reset checkbox
            document.querySelector('#addAssemblyTable tbody').innerHTML = '';
            $('#addSkuModal').modal('show');
            makeRowsDraggable();
        };

        window.closeAddSkuModal = function() {
            $('#addSkuModal').modal('hide');
        };

        const closeButtons = document.getElementsByClassName('closeAddSkuBtn');
        Array.from(closeButtons || []).forEach(btn => {
            btn.addEventListener('click', window.closeAddSkuModal);
        });

        const addBtn = document.getElementById('addNewAssemblyRowBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                const tbody = document.querySelector('#addAssemblyTable tbody');
                const rowCount = tbody.rows.length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${rowCount + 1}</td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="number" class="form-control form-control-sm" value="0"></td>
                <td><input type="number" class="form-control form-control-sm" value="0"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
            `;
                tbody.appendChild(tr);
                updateRowNumbers();
                makeRowsDraggable();
            });
        }

        const addAssemblyTbody = document.querySelector('#addAssemblyTable tbody');
        if (addAssemblyTbody) {
            addAssemblyTbody.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-row-btn')) {
                    e.target.closest('tr').remove();
                    updateRowNumbers();
                }
            });
        }

        function updateRowNumbers() {
            const rows = document.querySelectorAll('#addAssemblyTable tbody tr');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
        }

        let draggedRow = null;
        const tbody = document.querySelector('#addAssemblyTable tbody');
        if (tbody) {
            tbody.addEventListener('dragstart', (e) => {
                draggedRow = e.target.closest('tr');
                e.dataTransfer.effectAllowed = 'move';
                e.target.style.opacity = '0.5';
            });

            tbody.addEventListener('dragend', (e) => {
                e.target.style.opacity = '';
                draggedRow = null;
                updateRowNumbers();
            });

            tbody.addEventListener('dragover', (e) => {
                e.preventDefault();
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

        function makeRowsDraggable() {
            const rows = document.querySelectorAll('#addAssemblyTable tbody tr');
            rows.forEach(row => row.setAttribute('draggable', 'true'));
            document.querySelectorAll('#addSkuModal input[type="text"]').forEach(input => {
                input.addEventListener('input', () => {
                    input.value = input.value.toUpperCase();
                });
            });
        }
        document.querySelectorAll('#addSkuModal input[type="text"]').forEach(input => {
            input.addEventListener('input', () => {
                input.value = input.value.toUpperCase();
            });
        });
        const saveBtn = document.getElementById('saveAddSkuBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const customer_name = localStorage.getItem('selectedCustomer') || '';
                const model = localStorage.getItem('selectedModel') || '';
                const fromStamping = document.getElementById('addFromStamping').checked ? 'stamping' : null;



                try {
                    const newItem = {
                        customer_name,
                        model,
                        material_no: document.getElementById('addMaterialNo').value.trim(),
                        material_description: document.getElementById('addMaterialDesc').value.trim(),
                        quantity: Number(document.getElementById('addQuantity').value) || 0,
                        fuel_type: document.getElementById('addSkuFuelType').value.trim() || '',
                        process: fromStamping,
                        total_process: 0,
                        sub_component: [],
                        assembly_section: [],
                        assembly_process: [],
                        assembly_processtime: [],
                        manpower: []
                    };
                    console.log('Saving:', {
                        newItem
                    });
                    const rows = document.querySelectorAll('#addAssemblyTable tbody tr');
                    rows.forEach(row => {
                        const inputs = row.querySelectorAll('input');
                        newItem.sub_component.push(inputs[0]?.value.trim() || '');
                        newItem.assembly_process.push(inputs[1]?.value.trim() || '');
                        newItem.assembly_section.push(inputs[2]?.value.trim() || '');
                        newItem.assembly_processtime.push(Number(inputs[3]?.value || 0));
                        newItem.manpower.push(Number(inputs[4]?.value || 0));
                    });

                    newItem.total_process = rows.length;

                    fetch('api/masterlist/addSKU', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(newItem)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.success) {
                                showAlert('success', 'Success', 'New SKU added successfully!');
                                $('#addSkuModal').modal('hide');
                            } else {
                                showAlert('error', 'Failed', 'Failed to add SKU: ' + (response.message || ''));
                            }
                        })
                        .catch(err => {
                            console.error('Error adding SKU:', err);
                            showAlert('error', 'Error', 'Error adding SKU. Check console for details.');
                        });
                } catch (err) {
                    console.error('saveAddSkuBtn error:', err);
                }
            });
        }

        makeRowsDraggable();
    })();
</script>