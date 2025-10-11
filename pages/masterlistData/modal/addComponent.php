<div class="modal fade" id="addComponentModal" tabindex="-1" role="dialog" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addComponentModalLabel">Add New Component</h5>
                <button type="button" class="close closeAddComponentBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="addComponentMaterialNo" readonly>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Material Description</label>
                        <select class="form-control" id="addComponentMaterialDesc">
                            <option value="">Select Material</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4 align-items-center">
                        <label class="mb-0 mr-2" style=" text-align: center;">Source:</label>
                        <div class="d-flex align-items-center">
                            <div class="form-check form-check-inline mb-0 p-0 w-75">
                                <input class="form-check-input m-0" type="radio" name="addComponentSource" id="addSourceStamping" value="stamping">
                                <label class="form-check-label ml-1 mr-3 mb-0" for="addSourceStamping">Stamping SKU/Muffler Component</label>
                            </div>
                            <div class="form-check form-check-inline mb-0 p-0">
                                <input class="form-check-input m-0" type="radio" name="addComponentSource" id="addSourceSupplied" value="supplied">
                                <label class="form-check-label ml-1 mb-0" for="addSourceSupplied">PPIC</label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Component Name</label>
                        <input type="text" class="form-control" id="addComponentName" placeholder="Enter Component Name">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="addComponentQuantity" value="0">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Usage</label>
                        <input type="number" class="form-control" id="addComponentUsage" placeholder="Enter Usage" min="0" step="0.01" value="0">
                    </div>
                </div>

                <hr>

                <div class="mb-2 text-right">
                    <button type="button" class="btn btn-sm btn-success" id="addStageRowBtnNew">Add Process</button>
                </div>

                <table class="table table-bordered table-sm" id="addStageTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Section</th>
                            <th>Process</th>
                            <th>Cycle Time</th>
                            <th>Machine</th>
                            <th>Manpower</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeAddComponentBtn" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveAddComponentBtn">Add Component</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        let cachedComponents = [];

        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const model = localStorage.getItem('selectedModel') || '';
                const res = await fetch(`api/masterlist/getSkuMaterialNo?model=${encodeURIComponent(model)}`);
                if (!res.ok) throw new Error('Network response was not ok');

                cachedComponents = await res.json();
                console.log('✅ Components loaded:', cachedComponents);

                // ✅ Populate Material Description dropdown
                const materialDescSelect = document.getElementById('addComponentMaterialDesc');
                materialDescSelect.innerHTML = '<option value="">Select Material</option>';

                cachedComponents.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.material_no; // store material_no as value
                    opt.textContent = item.material_description; // show description
                    materialDescSelect.appendChild(opt);
                });

            } catch (err) {
                console.error('❌ Failed to load component list:', err);
                showAlert('error', 'Error', 'Failed to fetch component data on load.');
            }
        });

        // ✅ Close modal
        document.getElementById('addComponentMaterialDesc').addEventListener('change', (e) => {
            const selectedNo = e.target.value;
            const selected = cachedComponents.find(c => c.material_no === selectedNo);

            const materialNoInput = document.getElementById('addComponentMaterialNo');
            materialNoInput.value = selected ? selected.material_no : '';
        });

        // ✅ Close modal
        window.closeAddComponentModal = function() {
            $('#addComponentModal').modal('hide');
        };
        Array.from(document.getElementsByClassName('closeAddComponentBtn')).forEach(btn =>
            btn.addEventListener('click', window.closeAddComponentModal)
        );


        document.getElementById('addStageRowBtnNew').addEventListener('click', () => {
            const tbody = document.querySelector('#addStageTable tbody');
            const tr = document.createElement('tr');
            const index = tbody.rows.length + 1;
            tr.innerHTML = `
            <td>${index}</td>
            <td><input type="text" class="form-control form-control-sm"></td>
            <td><input type="text" class="form-control form-control-sm"></td>
            <td><input type="number" class="form-control form-control-sm" value="0"></td>
            <td><input type="text" class="form-control form-control-sm"></td>
            <td><input type="number" class="form-control form-control-sm" value="1" min="1"></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row-btn">Remove</button></td>
        `;
            tbody.appendChild(tr);
        });

        // ✅ Remove row
        document.querySelector('#addStageTable tbody').addEventListener('click', e => {
            if (e.target.classList.contains('remove-row-btn')) {
                e.target.closest('tr').remove();
                updateAddRowNumbers();
            }
        });

        function updateAddRowNumbers() {
            const rows = document.querySelectorAll('#addStageTable tbody tr');
            rows.forEach((r, i) => (r.cells[0].textContent = i + 1));
        }


        // ✅ Save button (same as yours)
        document.getElementById('saveAddComponentBtn').addEventListener('click', async () => {
            try {
                const rows = document.querySelectorAll('#addStageTable tbody tr');
                const stageData = [];
                const model = localStorage.getItem('selectedModel') || '';
                rows.forEach(row => {
                    const inputs = row.querySelectorAll('input');
                    const section = inputs[0].value.trim();
                    const stage = inputs[1].value.trim();
                    const cycle = Number(inputs[2].value);
                    const machine = inputs[3].value.trim();
                    const manpower = Number(inputs[4].value);

                    let sectionObj = stageData.find(s => s.section === section);
                    if (!sectionObj) {
                        sectionObj = {
                            section,
                            stages: {}
                        };
                        stageData.push(sectionObj);
                    }

                    sectionObj.stages[stage] = {
                        cycle,
                        machine,
                        manpower
                    };
                });

                // ✅ Build payload with quantity and usage included
                const payload = {
                    material_no: document.getElementById('addComponentMaterialNo').value.trim(),
                    components_name: document.getElementById('addComponentName').value.trim(),
                    stage_name: JSON.stringify(stageData),
                    process: document.querySelector('input[name="addComponentSource"]:checked')?.value || '',
                    actual_inventory: Number(document.getElementById('addComponentQuantity').value) || 0,
                    usage: Number(document.getElementById('addComponentUsage').value) || 0,
                    model,
                    process_quantity: rows.length
                };

                const res = await fetch('api/masterlist/addComponent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const response = await res.json();
                if (response.success) {
                    showAlert('success', 'Added', 'Component added successfully!');
                    setTimeout(() => window.location.reload(), 2000);
                    $('#addComponentModal').modal('hide');
                } else {
                    showAlert('error', 'Failed', response.message || 'Add failed.');
                }
            } catch (err) {
                console.error('Error adding component:', err);
                showAlert('error', 'Error', 'Something went wrong.');
            }
        });

    })();
</script>