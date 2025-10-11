<!-- RM Modal -->
<div class="modal fade" id="addRMModal" tabindex="-1" role="dialog" aria-labelledby="addRMModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addRMModalLabel">Add Raw Material</h5>
                <button type="button" class="close closeRMModalBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="rmMaterialNo" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Component Name</label>
                        <select class="form-control" id="rmComponentNameSelect">
                            <option value="">Select Component</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Component Usage</label>
                        <input type="number" class="form-control" id="componentUsage" readonly>
                    </div>
                </div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-10">
                        <label>Description</label>
                        <textarea class="form-control" id="rmDescription" rows="2" placeholder="Enter Material Description"></textarea>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Usage</label>
                        <input type="number" class="form-control" id="rmUsage" placeholder="Usage" min="0" step="0.001">
                    </div>
                </div>


            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeRMModalBtn" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRMBtn">Save</button>
            </div>
        </div>
    </div>
</div>


<script>
    (function() {
        const componentSelect = document.getElementById('rmComponentNameSelect');
        const materialNoInput = document.getElementById('rmMaterialNo');
        const usageInput = document.getElementById('rmUsage');
        const componentUsageInput = document.getElementById('componentUsage');

        // Fetch component list on modal open
        window.openAddRM = async function(item = {}) {
            // Reset fields
            componentSelect.innerHTML = '<option value="">Select Component</option>';
            materialNoInput.value = '';
            usageInput.value = 0;
            componentUsageInput.value = '';

            try {
                const model = localStorage.getItem('selectedModel') || '';
                const res = await fetch(`api/masterlist/getRMComponent?model=${encodeURIComponent(model)}`);
                const components = await res.json();

                // Populate select
                components.forEach(comp => {
                    const opt = document.createElement('option');
                    opt.value = comp.material_no;
                    opt.textContent = comp.components_name;
                    opt.dataset.usageType = comp.usage_type ?? 1; // store usage_type
                    componentSelect.appendChild(opt);
                });

                // If editing, preselect values
                if (item.rm_component_name) {
                    const selected = Array.from(componentSelect.options)
                        .find(o => o.textContent === item.rm_component_name);
                    if (selected) componentSelect.value = selected.value;
                    materialNoInput.value = item.rm_material_no || selected?.value || '';
                }
            } catch (err) {
                console.error('Error fetching components:', err);
            }

            usageInput.value = item.rm_usage ?? 0;
            componentUsageInput.value = ''; // will update on component select
            document.getElementById('rmDescription').value = item.rm_material_description ?? '';

            $('#addRMModal').modal('show');
        };

        // When a component is selected
        componentSelect.addEventListener('change', () => {
            const selectedOption = componentSelect.selectedOptions[0];
            if (!selectedOption) return;

            materialNoInput.value = selectedOption.value;

            const usageType = parseFloat(selectedOption.dataset.usageType || 1);
            componentUsageInput.value = usageType;
        });

        usageInput.addEventListener('input', () => {
            const usageType = parseFloat(componentUsageInput.value) || 1;
            const rawUsage = parseFloat(usageInput.value) || 0;
            usageInput.value = (rawUsage * usageType).toFixed(3);
        });

        // Save button
        document.getElementById('saveRMBtn').addEventListener('click', () => {
            const selectedOption = componentSelect.selectedOptions[0];
            if (!selectedOption) return showAlert('error', 'Error', 'Please select a component.');
            const model = localStorage.getItem('selectedModel') || '';
            const usageType = parseFloat(selectedOption.dataset.usageType || 1);
            const rawUsage = parseFloat(usageInput.value) || 0;

            const payload = {
                material_no: materialNoInput.value,
                component_name: selectedOption?.textContent || '',
                usage: rawUsage * usageType, // multiply by usage_type before sending
                usage_type: usageType, // send original usage_type
                material_description: document.getElementById('rmDescription').value.trim(),
                model
            };

            console.log('RM payload:', payload);

            fetch('api/masterlist/addRM', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        showAlert('success', 'Success', 'Raw Material saved successfully!');
                        $('#addRMModal').modal('hide');
                        if (window.refreshRMTable) window.refreshRMTable();
                    } else {
                        showAlert('error', 'Failed', response.message || 'Failed to save RM.');
                    }
                })
                .catch(err => {
                    console.error('Error saving RM:', err);
                    showAlert('error', 'Error', 'Something went wrong. Check console.');
                });
        });
    })();
</script>