<!-- Edit RM Modal -->
<div class="modal fade" id="editRMModal" tabindex="-1" role="dialog" aria-labelledby="editRMModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRMModalLabel">Edit Raw Material</h5>
                <button type="button" class="close closeEditRMBtn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <input type="hidden" id="editRMId">

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Material No</label>
                        <input type="text" class="form-control" id="editRMMaterialNo" readonly>
                    </div>
                    <div class="form-group col-md-5">
                        <label>Component Name</label>
                        <input type="text" class="form-control" id="editRMComponentName" readonly>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Usage</label>
                        <input type="number" class="form-control" id="editRMUsage" step="0.001">
                    </div>
                </div>

                <div class="form-group">
                    <label>Material Description</label>
                    <input type="text" class="form-control" id="editRMMaterialDesc">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeEditRMBtn" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditRMBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        // Close modal
        window.closeEditRMModal = function() {
            $('#editRMModal').modal('hide');
        };
        document.querySelectorAll('.closeEditRMBtn').forEach(btn => {
            btn.addEventListener('click', window.closeEditRMModal);
        });

        // Open modal and populate RM fields only
        window.openEditRM = function(item) {
            console.log('Editing RM:', item);

            const compUsage = parseFloat(item.comp_usage_type) || 1;
            const usageInput = document.getElementById('editRMUsage');

            // Fill fields (show raw RM usage initially)
            document.getElementById('editRMId').value = item.rm_id || '';
            document.getElementById('editRMMaterialNo').value = item.rm_material_no || '';
            document.getElementById('editRMMaterialDesc').value = item.rm_material_description || '';
            document.getElementById('editRMComponentName').value = item.rm_component_name || '';
            usageInput.value = parseFloat(item.rm_usage).toFixed(3); // show as-is first

            // Remove any previously attached input handler
            if (usageInput._handler) {
                usageInput.removeEventListener('input', usageInput._handler);
            }

            // Add live multiplication — triggers only after user types
            const handler = (e) => {
                const baseVal = parseFloat(e.target.value) || 0;
                const multipliedVal = baseVal * compUsage;
                e.target.value = multipliedVal.toFixed(3);
            };

            usageInput._handler = handler;
            usageInput.addEventListener('input', handler);

            $('#editRMModal').modal('show');
        };

        // Helper: convert empty string or array to null
        function normalizeValue(val) {
            if (val === '' || val === undefined || val === null) return null;
            if (Array.isArray(val) && val.length === 0) return null;
            return val;
        }

        // Save RM changes
        document.getElementById('saveEditRMBtn').addEventListener('click', () => {
            const updatedRM = {
                rm_id: normalizeValue(document.getElementById('editRMId').value),
                rm_material_no: normalizeValue(document.getElementById('editRMMaterialNo').value),
                rm_material_description: normalizeValue(document.getElementById('editRMMaterialDesc').value),
                rm_usage: normalizeValue(parseFloat(document.getElementById('editRMUsage').value)),
                rm_component_name: normalizeValue(document.getElementById('editRMComponentName').value)
            };

            fetch('api/masterlist/updateRM', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(updatedRM)
                })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        Swal.fire('Success', 'Raw Material updated successfully!', 'success');
                        setTimeout(() => window.location.reload(), 2000);
                        $('#editRMModal').modal('hide');
                    } else {
                        Swal.fire('Error', 'Failed to update RM: ' + (response.message || ''), 'error');
                    }
                })
                .catch(err => {
                    console.error('Error updating RM:', err);
                    Swal.fire('Error', 'Error updating RM. Check console for details.', 'error');
                });
        });
    })();
</script>