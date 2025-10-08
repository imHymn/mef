<!-- CSS -->
<style>
  #modal-edit-mefCheckboxGroup {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;

    row-gap: 0.5rem;
    justify-content: flex-start;
    align-items: center;

  }

  #modal-edit-locationCheckboxGroup {
    display: flex;
    gap: 2rem;
    /* side by side */
  }

  .location-column {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .location-column h6 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #ddd;
    padding-bottom: 0.2rem;
    color: #555;
  }

  #modal-edit-locationCheckboxGroup .form-check,
  #modal-edit-mefCheckboxGroup .form-check {
    display: flex;
    align-items: center;
    width: auto !important;
    margin: 0;
    padding: 0.2rem 0.4rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f9f9f9;
    transition: background 0.2s, border-color 0.2s;
  }

  #modal-edit-locationCheckboxGroup .form-check:hover,
  #modal-edit-mefCheckboxGroup .form-check:hover {
    background: #f1f1f1;
    border-color: #ccc;

  }

  #modal-edit-locationCheckboxGroup .form-check-label,
  #modal-edit-mefCheckboxGroup .form-check-label {
    margin-left: 1rem;
    font-size: 0.85rem;

  }

  #modal-edit-locationCheckboxGroup .form-check-input,
  #modal-edit-mefCheckboxGroup .form-check-input {
    margin-left: .1rem;
    font-size: 1rem;
  }
</style>

<!-- Modal -->
<div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="update-user-form" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateUserModalLabel">Update User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="modal-edit-id" />

        <!-- Full Name -->
        <div class="mb-3">
          <label for="modal-edit-name" class="form-label">Full Name</label>
          <input type="text" class="form-control" id="modal-edit-name" required />
        </div>

        <!-- User ID -->
        <div class="mb-3">
          <label for="modal-edit-user-id" class="form-label">User ID</label>
          <input type="text" class="form-control" id="modal-edit-user-id" required />
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label for="modal-edit-password" class="form-label">Password</label>
          <input type="password" class="form-control" id="modal-edit-password" minlength="5" />
        </div>

        <!-- Department -->
        <div class="mb-3" id="modal-edit-departmentWrapper">
          <label for="modal-edit-department" class="form-label">Department</label>
          <select id="modal-edit-department" class="form-control" required>
            <option disabled selected>Choose department</option>
            <option value="qc">Quality Control</option>
            <option value="mef">Metal Fabrication</option>
            <option value="logistics">Logistics</option>
          </select>
        </div>

        <!-- Role -->
        <div class="mb-3 d-none" id="modal-edit-roleWrapper">
          <label for="modal-edit-role" class="form-label">Role</label>
          <select id="modal-edit-role" class="form-control">
            <option disabled selected>Choose role</option>
          </select>
        </div>

        <!-- MEF Sections -->
        <div class="mb-3 d-none" id="modal-edit-mefWrapper">
          <label class="form-label">Sections</label>
          <div id="modal-edit-mefCheckboxGroup">
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" value="assembly" id="edit-section-assembly">
              <label class="form-check-label mt-2" for="edit-section-assembly">Assembly</label>
            </div>
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" value="stamping" id="edit-section-stamping">
              <label class="form-check-label mt-2" for="edit-section-stamping">Stamping</label>
            </div>
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" value="finishing" id="edit-section-finishing">
              <label class="form-check-label mt-2" for="edit-section-finishing">Finishing</label>
            </div>
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" value="painting" id="edit-section-painting">
              <label class="form-check-label mt-2" for="edit-section-painting">Painting</label>
            </div>
          </div>
        </div>

        <!-- MEF Locations -->
        <div class="mb-3 d-none" id="modal-edit-locationWrapper">
          <label class="form-label">Locations</label>
          <div id="modal-edit-locationCheckboxGroup">
            <div id="location-assembly-column" class="location-column d-none">
              <h6>Assembly</h6>
            </div>
            <div id="location-stamping-column" class="location-column d-none">
              <h6>Stamping</h6>
            </div>
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

<!-- JS -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('updateUserModal');
    const form = document.getElementById('update-user-form');

    const departmentSelect = document.getElementById('modal-edit-department');
    const roleWrapper = document.getElementById('modal-edit-roleWrapper');
    const roleSelect = document.getElementById('modal-edit-role');
    const mefWrapper = document.getElementById('modal-edit-mefWrapper');
    const mefCheckboxes = modalEl.querySelectorAll('.mef-section');
    const locationWrapper = document.getElementById('modal-edit-locationWrapper');
    const locationCheckboxGroup = document.getElementById('modal-edit-locationCheckboxGroup');

    const locationOptions = {
      stamping: ['BIG MECH', 'BIG HYD', 'MUFFLER COMPS', 'OEM SMALL'],
      assembly: ['L300 ASSY', 'MIG WELDING', 'BIW STATIONARY SPOT', 'BIW HANGING SPOT', 'HPI FENDER ASSY', 'K1AK ROBOT ASSEMBLY', 'U-BOLT']
    };

    // Populate locations vertically per section and pre-check user locations
    function populateLocations(userLocations = []) {
      const checkedSections = [...mefCheckboxes].filter(cb => cb.checked).map(cb => cb.value);

      const assemblyCol = document.getElementById('location-assembly-column');
      const stampingCol = document.getElementById('location-stamping-column');

      assemblyCol.innerHTML = '<h6>Assembly</h6>';
      stampingCol.innerHTML = '<h6>Stamping</h6>';

      if (checkedSections.includes('assembly')) {
        assemblyCol.classList.remove('d-none');
        locationOptions['assembly'].forEach(loc => {
          const isChecked = userLocations.includes(loc) ? 'checked' : '';
          assemblyCol.innerHTML += `<div class="form-check">
          <input type="checkbox" class="form-check-input mef-location" name="locations[]" value="${loc}" id="edit-loc-${loc.replace(/\s+/g,'-')}" ${isChecked}>
          <label class="form-check-label" for="edit-loc-${loc.replace(/\s+/g,'-')}">${loc}</label>
        </div>`;
        });
      } else {
        assemblyCol.classList.add('d-none');
      }

      if (checkedSections.includes('stamping')) {
        stampingCol.classList.remove('d-none');
        locationOptions['stamping'].forEach(loc => {
          const isChecked = userLocations.includes(loc) ? 'checked' : '';
          stampingCol.innerHTML += `<div class="form-check">
          <input type="checkbox" class="form-check-input mef-location" name="locations[]" value="${loc}" id="edit-loc-${loc.replace(/\s+/g,'-')}" ${isChecked}>
          <label class="form-check-label" for="edit-loc-${loc.replace(/\s+/g,'-')}">${loc}</label>
        </div>`;
        });
      } else {
        stampingCol.classList.add('d-none');
      }

      locationWrapper.classList.toggle('d-none', !checkedSections.includes('assembly') && !checkedSections.includes('stamping'));
    }

    // Populate role options
    function populateRoleOptions(dept, selectedRole = null) {
      let options = `<option disabled selected>Choose role</option>`;
      if (dept === 'qc') options += `<option value="supervisor">Supervisor</option><option value="operator">Operator</option><option value="line leader">Line Leader</option>`;
      else if (dept === 'logistics') options += `<option value="planner">Planner</option><option value="delivery">Delivery</option><option value="rm warehouse">RM Warehouse</option>`;
      else if (dept === 'mef') options += `<option value="account manager">Account Manager</option><option value="fg warehouse">FG Warehouse</option><option value="supervisor">Supervisor</option><option value="line leader">Line Leader</option><option value="operator">Operator</option>`;
      roleSelect.innerHTML = options;
      if (selectedRole) roleSelect.value = selectedRole;
      roleWrapper.classList.toggle('d-none', !dept);
    }

    // Open modal and populate data
    modalEl.addEventListener('openUpdateModal', (event) => {
      const user = event.detail;
      if (!user) return;

      document.getElementById('modal-edit-id').value = user.id || '';
      document.getElementById('modal-edit-name').value = user.name || '';
      document.getElementById('modal-edit-user-id').value = user.user_id || '';
      document.getElementById('modal-edit-password').value = '';

      departmentSelect.value = user.department || '';
      populateRoleOptions(departmentSelect.value, user.role);

      mefWrapper.classList.add('d-none');
      locationWrapper.classList.add('d-none');
      mefCheckboxes.forEach(cb => cb.checked = false);

      // ✅ Parse JSON properly
      let section = [];
      try {
        section = user.section ? JSON.parse(user.section) : [];
      } catch {
        section = [];
      }

      let userLocations = [];
      try {
        userLocations = user.specific_section ? JSON.parse(user.specific_section) : [];
      } catch {
        userLocations = [];
      }

      if (section.length) {
        mefWrapper.classList.remove('d-none');
        mefCheckboxes.forEach(cb => {
          if (section.includes(cb.value)) cb.checked = true;
        });
        populateLocations(userLocations);
      }
    });


    // Event listeners
    departmentSelect.addEventListener('change', () => populateRoleOptions(departmentSelect.value));
    roleSelect.addEventListener('change', () => {
      if (['supervisor', 'line leader', 'operator'].includes(roleSelect.value) && departmentSelect.value === 'mef') {
        mefWrapper.classList.remove('d-none');
      } else {
        mefWrapper.classList.add('d-none');
        locationWrapper.classList.add('d-none');
        mefCheckboxes.forEach(cb => cb.checked = false);
      }
    });
    mefCheckboxes.forEach(cb => cb.addEventListener('change', () => populateLocations()));

    // Submit handler
    form.addEventListener('submit', e => {
      e.preventDefault();

      // Gather checked values
      let section = [...mefCheckboxes].filter(cb => cb.checked).map(cb => cb.value);
      let specific_section = [...locationCheckboxGroup.querySelectorAll('input:checked')].map(cb => cb.value);

      // ✅ If QC department, override both
      if (departmentSelect.value === 'qc') {
        section = ['qc'];
        specific_section = ['QC'];
      } else {
        // ✅ Inject FINISHING / PAINTING if needed
        const sectionsNormalized = section.map(p => p.toLowerCase());
        if (sectionsNormalized.includes('finishing') && !specific_section.includes('FINISHING')) {
          specific_section.push('FINISHING');
        }
        if (sectionsNormalized.includes('painting') && !specific_section.includes('PAINTING')) {
          specific_section.push('PAINTING');
        }
      }

      const payload = {
        id: document.getElementById('modal-edit-id').value.trim(),
        name: document.getElementById('modal-edit-name').value.trim(),
        user_id: document.getElementById('modal-edit-user-id').value.trim(),
        password: document.getElementById('modal-edit-password').value || null,
        department: departmentSelect.value,
        role: roleSelect.value,
        section: section.length ? section : null,
        specific_section: specific_section.length ? specific_section : null
      };

      // Clean out null/empty fields
      Object.keys(payload).forEach(k =>
        payload[k] === null || (Array.isArray(payload[k]) && payload[k].length === 0) ?
        delete payload[k] :
        null
      );

      console.log('Update payload:', payload);

      fetch('api/accounts/updateAccount', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(async data => {
          if (data.success) {
            await showAlert('success', 'Success', data.message || 'Updated!');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
          } else {
            await showAlert('error', 'Error', data.message || 'Update failed');
          }
        })
        .catch(async err => {
          console.error(err);
          await showAlert('error', 'Error', 'Unexpected error');
        });

    });

  });
</script>