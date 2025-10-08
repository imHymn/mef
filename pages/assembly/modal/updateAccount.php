<div class="modal fade" id="updateProductionModal" tabindex="-1" aria-labelledby="updateProductionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="update-production-form">
        <div class="modal-header">
          <h5 class="modal-title" id="updateProductionModalLabel">Update Production Info</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="production-edit-id" />

          <!-- Production (multi-select) -->
          <div class="mb-3">
            <label class="form-label">Production</label>
            <div id="production-edit-wrapper" class="form-check-group ms-3">
              <div class="form-check">
                <input type="checkbox" id="prod-assembly" class="form-check-input" value="assembly" name="production[]" style="margin-left: .5rem;">
                <label for="prod-assembly" class="form-check-label">Assembly</label>
              </div>

              <div class="form-check">
                <input type="checkbox" id="prod-stamping" class="form-check-input" value="stamping" name="production[]" style="margin-left: .5rem;">
                <label for="prod-stamping" class="form-check-label">Stamping</label>
              </div>

              <div class="form-check">
                <input type="checkbox" id="prod-finishing" class="form-check-input" value="finishing" name="production[]" style="margin-left: .5rem;">
                <label for="prod-finishing" class="form-check-label">Finishing</label>
              </div>

              <div class="form-check">
                <input type="checkbox" id="prod-painting" class="form-check-input" value="painting" name="production[]" style="margin-left: .5rem;">
                <label for="prod-painting" class="form-check-label">Painting</label>
              </div>
            </div>
          </div>


          <!-- Production Locations (checkbox group) -->
          <div class="mb-3" id="production-edit-location-wrapper">
            <label class="form-label">Production Locations</label>
            <div id="production-edit-location-group" class="row ms-1">
              <!-- dynamically filled -->
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('updateProductionModal');
    const form = document.getElementById('update-production-form');
    const locationGroup = document.getElementById('production-edit-location-group');
    const updateModal = new bootstrap.Modal(modalElement);

    const locationOptions = {
      stamping: ['BIG MECH', 'BIG HYD', 'MUFFLER COMPS', 'OEM SMALL'],
      assembly: ['L300 ASSY', 'MIG WELDING', 'BIW STATIONARY SPOT', 'BIW HANGING SPOT', 'HPI FENDER ASSY', 'K1AK ROBOT ASSEMBLY', 'U-BOLT']
    };

    // Open modal via custom event
    modalElement.addEventListener('openProductionModal', event => {
      const user = event.detail;
      if (!user) return;

      form.reset();
      locationGroup.innerHTML = '';

      document.getElementById('production-edit-id').value = user.id ?? '';

      // Fill production checkboxes
      let productions = [];
      try {
        productions = JSON.parse(user.section);
      } catch {
        productions = [user.section];
      }
      productions.forEach(p => {
        const cb = form.querySelector(`input[name="production[]"][value="${p}"]`);
        if (cb) cb.checked = true;
      });

      // Fill locations
      let locations = [];
      try {
        locations = JSON.parse(user.specific_section);
      } catch {
        locations = [user.specific_section];
      }

      // Assembly column
      const colLeft = document.createElement('div');
      colLeft.className = 'col-6';
      colLeft.innerHTML = '<h6>Assembly</h6>';
      locationOptions.assembly.forEach(loc => {
        const id = `edit-assembly-${loc.replace(/\s+/g,'-')}`;
        colLeft.innerHTML += `
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="${id}" value="${loc}" name="production_location[]" style="margin-left:.5rem;" 
            ${locations.includes(loc) ? 'checked' : ''}>
          <label for="${id}" class="form-check-label">${loc}</label>
        </div>`;
      });

      // Stamping column
      const colRight = document.createElement('div');
      colRight.className = 'col-6';
      colRight.innerHTML = '<h6>Stamping</h6>';
      locationOptions.stamping.forEach(loc => {
        const id = `edit-stamping-${loc.replace(/\s+/g,'-')}`;
        colRight.innerHTML += `
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="${id}" value="${loc}" name="production_location[]" style="margin-left:.5rem;" 
            ${locations.includes(loc) ? 'checked' : ''}>
          <label for="${id}" class="form-check-label">${loc}</label>
        </div>`;
      });

      locationGroup.appendChild(colLeft);
      locationGroup.appendChild(colRight);

      updateModal.show();
    });

    // Submit form
    form.addEventListener('submit', e => {
      e.preventDefault();
      const id = document.getElementById('production-edit-id').value;
      const productions = Array.from(form.querySelectorAll('input[name="production[]"]:checked')).map(cb => cb.value);
      let locations = Array.from(form.querySelectorAll('input[name="production_location[]"]:checked')).map(cb => cb.value);

      if (!id || productions.length === 0) {
        showAlert('warning', 'Missing Info', 'Please complete all fields.');
        return;
      }

      // Add FINISHING / PAINTING if production selected
      const normalized = productions.map(p => p.toLowerCase());
      if (normalized.includes('finishing') && !locations.includes('FINISHING')) locations.push('FINISHING');
      if (normalized.includes('painting') && !locations.includes('PAINTING')) locations.push('PAINTING');
      console.log(id, productions, locations)
      fetch('api/reusable/updateAccountMinimal', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id,
            section: productions,
            specific_section: locations
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showAlert('success', 'Updated', data.message || 'Production info updated.');

            // Hide modal and refresh list after success delay
            setTimeout(() => {
              updateModal.hide();
              if (typeof loadAccounts === 'function') loadAccounts();
            }, 2000);
          } else {
            showAlert('error', 'Error', data.message || 'Update failed.');
          }
        })
        .catch(err => {
          console.error(err);
          showAlert('error', 'Error', 'Unexpected error occurred.');
        });

    });

    // Bind update buttons in table
    window.bindUpdateButtons = function(users) {
      document.querySelectorAll('.btn-update-user').forEach(btn => {
        btn.addEventListener('click', () => {
          const userId = btn.getAttribute('data-user-id');
          const selectedUser = users.find(u => u.user_id === userId);
          if (!selectedUser) return;
          modalElement.dispatchEvent(new CustomEvent('openProductionModal', {
            detail: selectedUser
          }));
        });
      });
    };
  });
</script>