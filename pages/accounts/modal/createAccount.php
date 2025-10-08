<!-- Required CSS -->
<link rel="stylesheet" href="assets/css/all.min.css">
<style>
  #mefCheckboxGroup {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    row-gap: 0.5rem;
  }

  #locationCheckboxGroup {
    display: flex;
    flex-direction: column;
    /* ⬅️ stack vertically */
    gap: 0.5rem;
  }

  /* Shared styles */
  #mefCheckboxGroup .form-check,
  #locationCheckboxGroup .form-check {
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

  #mefCheckboxGroup .form-check:hover,
  #locationCheckboxGroup .form-check:hover {
    background: #f1f1f1;
    border-color: #ccc;
  }

  #mefCheckboxGroup .form-check-input,
  #locationCheckboxGroup .form-check-input {
    margin: 0;
  }

  #mefCheckboxGroup .form-check-label,
  #locationCheckboxGroup .form-check-label {
    margin-left: 1rem;
    font-size: 0.85rem;
  }

  #locationCheckboxGroup {
    display: grid;
    grid-template-columns: 1fr 1fr;
    /* side-by-side */
    gap: 2rem;
    /* space between the two columns */
  }

  #locationCheckboxGroup .location-column {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  #locationCheckboxGroup .location-column h6 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    border-bottom: 1px solid #ddd;
    padding-bottom: 0.2rem;
    color: #555;
  }

  /* Keep your existing form-check styling */
  #locationCheckboxGroup .form-check {
    display: flex;
    align-items: center;
    padding: 0.2rem 0.4rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f9f9f9;
    transition: background 0.2s, border-color 0.2s;
  }

  #locationCheckboxGroup .form-check:hover {
    background: #f1f1f1;
    border-color: #ccc;
  }

  #locationCheckboxGroup .form-check-input {
    margin: 0;
  }

  #locationCheckboxGroup .form-check-label {
    margin-left: 1rem;
    font-size: 0.85rem;
  }
</style>

<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="createAccountForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createAccountModalLabel">Create New Account</h5>
      </div>

      <div class="modal-body">
        <!-- Full Name -->
        <div class="mb-3">
          <label for="name" class="form-label">Full Name</label>
          <input type="text" id="name" name="name" class="form-control" required
            style="text-transform: uppercase;"
            oninput="this.value = this.value.toUpperCase()">

        </div>

        <!-- User ID -->
        <div class="mb-3">
          <label for="user_id" class="form-label">User ID</label>
          <input type="text" id="user_id" name="user_id" class="form-control" required>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" required minlength="5">
        </div>

        <!-- Department -->
        <div class="mb-3" id="departmentWrapper">
          <label for="department" class="form-label">Department</label>
          <select name="department" id="department" class="form-control" required>
            <option disabled selected>Choose department</option>
            <option value="qc">Quality Control</option>
            <option value="mef">Metal Fabrication</option>
            <option value="logistics">Logistics</option>
          </select>
        </div>

        <!-- Role -->
        <div class="mb-3 d-none" id="roleWrapper">
          <label for="role" class="form-label">Role</label>
          <select name="role" id="role" class="form-control">
            <option disabled selected>Choose role</option>
          </select>
        </div>

        <!-- Sections (for MEF) -->
        <div class="mb-3 d-none" id="mefWrapper">
          <label class="form-label">Sections</label>
          <div id="mefCheckboxGroup" class="form-check-group">
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" name="sections[]" value="assembly" id="section-assembly">
              <label class="form-check-label mt-1" for="section-assembly">Assembly</label>
            </div>
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" name="sections[]" value="stamping" id="section-stamping">
              <label class="form-check-label mt-1" for="section-stamping">Stamping</label>
            </div>
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" name="sections[]" value="finishing" id="section-finishing">
              <label class="form-check-label mt-1" for="section-finishing">Finishing</label>
            </div>
            <div class="form-check">
              <input class="form-check-input mef-section" type="checkbox" name="sections[]" value="painting" id="section-painting">
              <label class="form-check-label mt-1" for="section-painting">Painting</label>
            </div>
          </div>
        </div>

        <!-- Location (depends on selected sections inside MEF) -->
        <!-- Location (for MEF, shown dynamically) -->
        <div class="mb-3 d-none" id="locationWrapper">
          <label class="form-label">Locations</label>
          <div id="locationCheckboxGroup" class="form-check-group">
            <!-- Populated by JS -->
          </div>
        </div>


      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Account</button>
      </div>
    </form>
  </div>
</div>

<script>
  const departmentSelect = document.getElementById('department');
  const roleWrapper = document.getElementById('roleWrapper');
  const roleSelect = document.getElementById('role');
  const mefWrapper = document.getElementById('mefWrapper');
  const mefSectionCheckboxes = document.querySelectorAll('.mef-section');
  const locationWrapper = document.getElementById('locationWrapper');
  const locationCheckboxGroup = document.getElementById('locationCheckboxGroup');

  const locationOptions = {
    stamping: [
      'BIG MECH',
      'BIG HYD',
      'MUFFLER COMPS',
      'OEM SMALL',

    ],
    assembly: [
      'L300 ASSY',
      'MIG WELDING',
      'BIW STATIONARY SPOT',
      'BIW HANGING SPOT',
      'HPI FENDER ASSY',
      'K1AK ROBOT ASSEMBLY',
      'U-BOLT',
    ]
  };

  mefSectionCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      const checkedSections = [...mefSectionCheckboxes]
        .filter(c => c.checked)
        .map(c => c.value);

      let options = [];
      checkedSections.forEach(sec => {
        if (locationOptions[sec]) {
          options.push(...locationOptions[sec]);
        }
      });

      const uniqueOptions = [...new Set(options)];

      if (uniqueOptions.length > 0) {
        locationWrapper.classList.remove('d-none');
        locationCheckboxGroup.innerHTML = uniqueOptions.map(loc => `
        <div class="form-check">
          <input class="form-check-input mef-location" type="checkbox" 
                 name="locations[]" value="${loc}" id="loc-${loc.replace(/\s+/g,'-')}">
          <label class="form-check-label" for="loc-${loc.replace(/\s+/g,'-')}">${loc}</label>
        </div>
      `).join('');
      } else {
        locationWrapper.classList.add('d-none');
        locationCheckboxGroup.innerHTML = '';
      }
    });
  });

  // Department change
  departmentSelect.addEventListener('change', () => {
    const selected = departmentSelect.value;

    if (selected === 'qc') {
      roleWrapper.classList.remove('d-none');
      mefWrapper.classList.add('d-none');
      locationWrapper.classList.add('d-none');
      roleSelect.innerHTML = `
      <option disabled selected>Choose role</option>
      <option value="supervisor">Supervisor</option>
      <option value="operator">Operator</option>
      <option value="line leader">Line Leader</option>
    `;
      roleSelect.required = true;

    } else if (selected === 'logistics') {
      roleWrapper.classList.remove('d-none');
      mefWrapper.classList.add('d-none');
      locationWrapper.classList.add('d-none');
      roleSelect.innerHTML = `
      <option disabled selected>Choose role</option>
      <option value="planner">Planner</option>
      <option value="delivery">Delivery</option>
      <option value="rm warehouse">RM Warehouse</option>
    `;
      roleSelect.required = true;

    } else if (selected === 'mef') {
      roleWrapper.classList.remove('d-none');
      roleSelect.innerHTML = `
      <option disabled selected>Choose role</option>
      <option value="fg warehouse">FG Warehouse</option>
      <option value="supervisor">Supervisor</option>
      <option value="line leader">Line Leader</option>
      <option value="operator">Operator</option>
    `;
      roleSelect.required = true;

      // Initially hide sections
      mefWrapper.classList.add('d-none');
      locationWrapper.classList.add('d-none');

    } else {
      roleWrapper.classList.add('d-none');
      mefWrapper.classList.add('d-none');
      locationWrapper.classList.add('d-none');
      roleSelect.required = false;
      roleSelect.innerHTML = '';
    }
  });
  // Sections change (for MEF): dynamically populate locations
  mefSectionCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      const checkedSections = [...mefSectionCheckboxes]
        .filter(c => c.checked)
        .map(c => c.value);

      const assemblyLocs = locationOptions.assembly.filter(l => checkedSections.includes('assembly'));
      const stampingLocs = locationOptions.stamping.filter(l => checkedSections.includes('stamping'));

      if (assemblyLocs.length || stampingLocs.length) {
        locationWrapper.classList.remove('d-none');
        locationCheckboxGroup.innerHTML = `
        ${assemblyLocs.length ? `
          <div class="location-column">
            <h6>Assembly</h6>
            ${assemblyLocs.map(loc => `
              <div class="form-check">
                <input class="form-check-input mef-location" type="checkbox"
                       name="locations[]" value="${loc}" id="loc-${loc.replace(/\s+/g,'-')}">
                <label class="form-check-label mt-1" for="loc-${loc.replace(/\s+/g,'-')}">${loc}</label>
              </div>
            `).join('')}
          </div>` : ''}

        ${stampingLocs.length ? `
          <div class="location-column">
            <h6>Stamping</h6>
            ${stampingLocs.map(loc => `
              <div class="form-check">
                <input class="form-check-input mef-location" type="checkbox"
                       name="locations[]" value="${loc}" id="loc-${loc.replace(/\s+/g,'-')}">
                <label class="form-check-label" for="loc-${loc.replace(/\s+/g,'-')}">${loc}</label>
              </div>
            `).join('')}
          </div>` : ''}
      `;
      } else {
        locationWrapper.classList.add('d-none');
        locationCheckboxGroup.innerHTML = '';
      }
    });
  });

  roleSelect.addEventListener('change', () => {
    const selectedRole = roleSelect.value;
    if (departmentSelect.value === 'mef' && ['supervisor', 'line leader', 'operator'].includes(selectedRole)) {
      mefWrapper.classList.remove('d-none');
    } else {
      mefWrapper.classList.add('d-none');
      locationWrapper.classList.add('d-none'); // also hide locations if sections hidden
      // uncheck all sections and locations
      mefSectionCheckboxes.forEach(cb => cb.checked = false);
      document.querySelectorAll('.mef-location').forEach(loc => loc.checked = false);
    }
  });
  const createAccountForm = document.getElementById('createAccountForm');

  createAccountForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(createAccountForm);
    let productionValue;
    if (formData.get('department') === 'qc') {
      // ✅ If QC, force both to "QC"
      productionValue = ['qc'];
      productionLocationValue = ['QC'];
    } else {
      // Otherwise, get selected sections and locations
      productionValue = formData.getAll('sections[]'); // keep as-is
      productionLocationValue = formData.getAll('locations[]') || [];

      // Normalize sections to lowercase for checking
      const sectionsNormalized = productionValue.map(p => p.toLowerCase());

      if (sectionsNormalized.includes('finishing') && !productionLocationValue.includes('FINISHING')) {
        productionLocationValue.push('FINISHING');
      }
      if (sectionsNormalized.includes('painting') && !productionLocationValue.includes('PAINTING')) {
        productionLocationValue.push('PAINTING');
      }
    }
    const data = {
      name: formData.get('name'),
      user_id: formData.get('user_id')?.replace(/\s+/g, ''),
      password: formData.get('password'),
      department: formData.get('department'),
      section: productionValue, // keep original casing
      specific_section: productionLocationValue, // with FINISHING/PAINTING uppercase
      role: formData.get('role') || null,
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };

    console.log('📤 Data to send:', data);

    fetch('api/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      })
      .then(res => res.json())
      .then(response => {
        if (response.success) {
          showAlert('success', 'Success', 'Account created successfully!');
          setTimeout(() => {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            location.reload();
          }, 2000); // matches success auto-close
        } else {
          showAlert('error', 'Error', response.message || 'Failed to create account.');
        }
      })
      .catch(err => {
        console.error('Request failed', err);
        showAlert('error', 'Error', 'Something went wrong.');
      });

  });
</script>