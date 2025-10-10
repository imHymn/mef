<!-- View SKU Modal -->
<div class="modal fade" id="viewSkuModal" tabindex="-1" role="dialog" aria-labelledby="viewSkuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSkuModalLabel">View SKU Item</h5>
                <button type="button" class="close closeViewSkuBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Basic Fields -->
                <div class="d-flex align-items-center" style="gap: 2rem;">
                    <input type="text" id="viewId" hidden>

                    <div>
                        <label class="font-weight-bold mb-0">Material No:</label>
                        <p id="viewMaterialNo" class="form-control-plaintext d-inline mb-0"></p>
                    </div>

                    <div>
                        <label class="font-weight-bold mb-0">Material Description:</label>
                        <p id="viewMaterialDesc" class="form-control-plaintext d-inline mb-0"></p>
                    </div>

                    <div>
                        <label class="font-weight-bold mb-0">Quantity:</label>
                        <p id="viewQuantity" class="form-control-plaintext d-inline mb-0"></p>
                    </div>
                </div>


                <hr>

                <table class="table table-bordered table-sm" id="viewAssemblyTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Sub Component</th>
                            <th>Assembly Process</th>
                            <th>Assembly Section</th>
                            <th>Cycle Time</th>
                            <th>Manpower</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeViewSkuBtn" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        // Expose globally so parent can call it
        window.openViewModal = function(item) {
            try {
                // Basic fields
                const elMaterialNo = document.getElementById('viewMaterialNo');
                const elMaterialDesc = document.getElementById('viewMaterialDesc');
                const elQuantity = document.getElementById('viewQuantity');

                const elId = document.getElementById('viewId');

                elMaterialNo.textContent = item.material_no || '';
                elMaterialDesc.textContent = item.material_description || '';
                elQuantity.textContent = item.quantity || '';

                if (elId) elId.value = item.id || '';

                // Safely parse arrays
                const subComponents = parseJSONSafe(item.sub_component);
                const assemblySections = parseJSONSafe(item.assembly_section);
                const assemblyProcesses = parseJSONSafe(item.assembly_process);
                const processTimes = parseJSONSafe(item.assembly_processtime);
                const manpower = parseJSONSafe(item.manpower);

                const tbody = document.querySelector('#viewAssemblyTable tbody');
                if (!tbody) return console.warn('viewAssemblyTable tbody not found');

                tbody.innerHTML = '';

                const maxLen = Math.max(
                    subComponents.length,
                    assemblyProcesses.length,
                    assemblySections.length,
                    processTimes.length,
                    manpower.length
                );

                for (let i = 0; i < maxLen; i++) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${i + 1}</td>
        <td>${subComponents[i] || ''}</td>
<td>${assemblyProcesses[i] || ''}</td>
<td>${assemblySections[i] || ''}</td>
<td>${processTimes[i] || 0}</td>
<td>${manpower[i] || 0}</td>

                `;
                    tbody.appendChild(tr);
                }

                $('#viewSkuModal').modal('show');
            } catch (err) {
                console.error('openViewModal error:', err);
            }
        };

        // Global close function
        window.closeViewSkuModal = function() {
            $('#viewSkuModal').modal('hide');
        };

        // Helper for safe JSON parsing
        function parseJSONSafe(str) {
            try {
                if (!str) return [];
                return JSON.parse(str);
            } catch (err) {
                console.warn('Invalid JSON:', str);
                return [];
            }
        }

        // Attach close button listeners (safe)
        const closeButtons = document.getElementsByClassName('closeViewSkuBtn');
        Array.from(closeButtons || []).forEach(btn => {
            btn.addEventListener('click', window.closeViewSkuModal);
        });
    })();
</script>