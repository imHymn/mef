<!-- View Component Modal -->
<div class="modal fade" id="viewComponentModal" tabindex="-1" role="dialog" aria-labelledby="viewComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewComponentModalLabel">View Component</h5>
                <button type="button" class="close closeViewComponentBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Basic Info -->
                <div class="d-flex align-items-center" style="gap: 2rem;">
                    <input type="hidden" id="viewComponentId">

                    <div>
                        <label class="font-weight-bold mb-0">Material No:</label>
                        <p id="viewComponentMaterialNo" class="form-control-plaintext d-inline mb-0"></p>
                    </div>

                    <div>
                        <label class="font-weight-bold mb-0">Component Name:</label>
                        <p id="viewComponentName" class="form-control-plaintext d-inline mb-0"></p>
                    </div>

                    <div>
                        <label class="font-weight-bold mb-0">Inventory:</label>
                        <p id="viewComponentInventory" class="form-control-plaintext d-inline mb-0"></p>
                    </div>
                </div>

                <hr>

                <!-- Stage Details Table -->
                <table class="table table-bordered table-sm" id="viewStageTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Section</th>
                            <th>Process / Stage</th>
                            <th>Cycle Time</th>
                            <th>Machine</th>
                            <th>Manpower</th> <!-- ✅ Added -->
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeViewComponentBtn" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        window.openViewComponent = function(item) {
            try {
                document.getElementById('viewComponentId').value = item.id || '';
                document.getElementById('viewComponentMaterialNo').textContent = item.material_no || '';
                document.getElementById('viewComponentName').textContent = item.components_name || '';
                document.getElementById('viewComponentInventory').textContent = item.actual_inventory || '';

                const tbody = document.querySelector('#viewStageTable tbody');
                tbody.innerHTML = '';

                const stageData = parseJSONSafe(item.stage_name);
                if (!stageData.length) return;

                let rowIndex = 1;
                stageData.forEach(sectionData => {
                    const section = sectionData.section || 'N/A';
                    const stages = sectionData.stages || {};

                    Object.entries(stages).forEach(([stageName, info]) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
        <td>${rowIndex++}</td>
        <td>${section}</td>
        <td>${stageName}</td>
        <td>${info.cycle || 0}</td>
        <td>${info.machine || ''}</td>
        <td>${info.manpower ?? ''}</td> <!-- ✅ Added manpower column -->
    `;
                        tbody.appendChild(tr);
                    });

                });

                $('#viewComponentModal').modal('show');
            } catch (err) {
                console.error('openViewComponent error:', err);
            }
        };

        function parseJSONSafe(str) {
            try {
                if (!str) return [];
                return JSON.parse(str);
            } catch (e) {
                console.warn('Invalid stage_name JSON:', str);
                return [];
            }
        }

        window.closeViewComponentModal = function() {
            $('#viewComponentModal').modal('hide');
        };

        const closeBtns = document.getElementsByClassName('closeViewComponentBtn');
        Array.from(closeBtns || []).forEach(btn => {
            btn.addEventListener('click', window.closeViewComponentModal);
        });
    })();
</script>