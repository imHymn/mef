<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>

<style>
  .custom-hover tbody tr:hover {
    background-color: #dde0e2ff !important;
    /* light blue */
  }
</style>
<div class="page-content">
  <nav class="page-breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="#">Pages</a></li>
      <li class="breadcrumb-item" aria-current="page">Stamping Section</li>
    </ol>
  </nav>

  <div class="row">
    <div class="col-md-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title mb-0">Components Inventory</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>

          <div class="d-flex align-items-center justify-content-between w-100 mb-2">
            <input
              type="text"
              id="filter-input"
              class="form-control form-control-sm me-2"
              style="max-width: 300px;"
              placeholder="Type to filter..." />

          </div>



          <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
            <thead>
              <tr>
                <th style="width: 5%; text-align: center;">Material No <span class="sort-icon"></span></th>
                <th style="width: 10%; text-align: center;">Component Name <span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Usage<span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Quantity <span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;white-space: normal; word-wrap: break-word;">Raw Material Qty <span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Stock Status <span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Issue<span class="sort-icon"></span></th>
              </tr>
            </thead>
            <tbody id="data-body"></tbody>
          </table>


          <div id="pagination" class="mt-3 d-flex justify-content-center"></div>




        </div>
      </div>
    </div>
  </div>
  <script>
    const dataBody = document.getElementById('data-body');
    const filterColumn = document.getElementById('filter-column');
    const filterInput = document.getElementById('filter-input');
    const paginationContainerId = 'pagination';
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;
    let componentsData = [];
    let paginator = null;
    let model = null;

    function getData(model) {
      fetch(`api/stamping/getComponentInventory?model=${encodeURIComponent(model)}`)
        .then(response => response.json())
        .then(data => {
          const seen = new Set();
          const clipFlags = {
            'CLIP 25': false,
            'CLIP 60': false
          };

          componentsData = data.filter(item => {
            const name = (item.components_name || '').trim().toUpperCase();

            // Special case: allow only one CLIP 25 and one CLIP 60
            if (name === 'CLIP 25' || name === 'CLIP 60') {
              if (clipFlags[name]) return false;
              clipFlags[name] = true;
              return true;
            }

            // General uniqueness check by material_no + components_name
            const key = `${item.material_no}__${item.components_name}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
          });

          paginator = createPaginator({
            data: componentsData,
            rowsPerPage: 10,
            paginationContainerId,
            renderPageCallback: renderTable,
            defaultSortFn: (a, b) => {
              const aNeedsRequest = a.actual_inventory <= a.reorder;
              const bNeedsRequest = b.actual_inventory <= b.reorder;
              return bNeedsRequest - aNeedsRequest;
            }
          });

          paginator.render();

          // ─── Setup search filter and highlight ───
          setupSearchFilter({
            filterInputSelector: '#filter-input',
            data: componentsData,
            searchableFields: ['material_no', 'components_name', 'usage_type', 'actual_inventory', 'rm_stocks'],
            onFilter: (filtered, query) => {
              currentFilterQuery = query; // <--- update the global variable
              paginator.setData(filtered);
            }
          });

        })
        .catch(error => {
          console.error('Error fetching component data:', error);
          showAlert('error', 'Error', 'Failed to load inventory data.');
        });

    }

    function renderTable(data) {
      dataBody.innerHTML = '';
      console.log(userRole)



      const statusPriority = {
        'Critical': 1,
        'Minimum': 2,
        'Reorder': 3,
        'Normal': 4,
        'Maximum': 5
      };

      const processedData = data.map(item => {
        const inventory = item.actual_inventory;
        const reorder = item.reorder;
        const critical = item.critical;
        const minimum = item.minimum;
        const maximum = item.maximum_inventory;

        let statusLabel = '';
        let statusColor = '';

        if (inventory <= critical) {
          statusLabel = "Critical";
          statusColor = "red";
        } else if (inventory <= minimum && inventory > critical) {
          statusLabel = "Minimum";
          statusColor = "orange";
        } else if (inventory <= reorder && inventory > minimum) {
          statusLabel = "Reorder";
          statusColor = "yellow";
        } else if (inventory > reorder && inventory <= maximum) {
          statusLabel = "Normal";
          statusColor = "green";
        } else if (inventory > maximum) {
          statusLabel = "Maximum";
          statusColor = "green";
        }

        const textColor = (statusColor === "yellow") ? "black" : "white";
        const stockText = `<button type="button" class="btn btn-sm" style="background-color: ${statusColor}; color: ${textColor};" title="${statusLabel}">${statusLabel}</button>`;

        return {
          ...item,
          statusLabel,
          stockText,
          priority: statusPriority[statusLabel] || 99
        };
      });

      processedData.sort((a, b) => a.priority - b.priority);

      const currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';

      for (const item of processedData) {

        const row = document.createElement('tr');
        row.innerHTML = `
  <td style="text-align: center;">${highlightText(item.material_no, currentFilterQuery)}</td>
  <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.components_name, currentFilterQuery)}</td>
  <td style="text-align: center;">${highlightText(item.usage_type, currentFilterQuery)}</td>
  <td style="text-align: center;">${highlightText(item.actual_inventory, currentFilterQuery)}</td>
  <td style="text-align: center;">${highlightText(item.rm_stocks, currentFilterQuery)} ${item.rm_stocks ? '<br/>(Ongoing)' : ''}</td>
  <td style="text-align: center;">${item.stockText}</td>
  <td style="text-align: center;">
    <button class="btn btn-sm btn-primary issueBtn">Issue</button>
  </td>
`;
        dataBody.appendChild(row);
        row.querySelector(".issueBtn").addEventListener("click", function() {
          getRowData(item);
        });
      }
      if (userRole !== "administrator") {
        // Disable all issue buttons
        document.querySelectorAll('.issueBtn').forEach(btn => {
          btn.disabled = true; // disable click
          btn.classList.add('btn-secondary'); // optional: make it look disabled
          btn.classList.remove('btn-primary'); // optional: remove primary style
        });
      }
      const now = new Date();
      document.getElementById('last-updated').textContent = `Last updated: ${now.toLocaleString()}`;
    }


    filterColumn.addEventListener('change', () => {
      filterInput.value = '';
      filterInput.disabled = !filterColumn.value;
      if (!filterColumn.value) {
        paginator.setData(componentsData);
      }
    });

    filterInput.addEventListener('input', () => {
      const column = filterColumn.value;
      const filterText = filterInput.value.trim().toLowerCase();

      if (!column) return;

      const filtered = componentsData.filter(item => {
        const value = item[column];
        return value && value.toString().toLowerCase().includes(filterText);
      });

      paginator.setData(filtered);
    });


    async function getRowData(data) {
      let reference_no = null;
      fetch('api/stamping/getLatestReferenceNo')
        .then(response => response.json())
        .then(data => {
          reference_no = data;
        })
      const {
        value: customQty
      } = await Swal.fire({
        title: `Issue Quantity`,
        html: `
      <p style="margin-bottom:10px;font-size:14px;">
        Component: <strong>${data.components_name}</strong><br>
        Material No: <strong>${data.material_no}</strong><br>
        Model: <strong>${data.model}</strong>
      </p>
    `,
        input: 'number',
        inputLabel: 'Enter quantity to issue',
        inputPlaceholder: 'Quantity',
        inputAttributes: {
          min: 1,
          step: 1
        },
        showCancelButton: true,
        confirmButtonText: 'Confirm Issue',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
          if (!value || value <= 0) {
            return 'Please enter a valid quantity';
          }
        }
      });

      if (customQty) {
        const issueData = {
          id: data.id,
          material_no: data.material_no,
          component_name: data.components_name,
          quantity: parseFloat(customQty),
          process_quantity: data.process_quantity,
          model: data.model,
          stage_name: data.stage_name,
          type: data.usage_type,
          reference_no: reference_no
        };

        console.log(issueData);

        sendIssueRequest(issueData);
      }
    }

    function sendIssueRequest(data) {
      console.log(data);

      fetch('api/rm/issueRM', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
          if (response.status === 'success') {
            showAlert('success', 'Success', response.message || 'Issued successfully.');
            // setTimeout(() => {
            //   window.location.reload();
            // }, 2000); // matches your auto-close timer
          } else {
            showAlert('error', 'Error', response.message || 'Issue failed.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showAlert('error', 'Error', 'Something went wrong.');
        });
    }

    enableTableSorting(".table");
  </script>
  <style>
    /* Hover effect */
    .custom-hover tbody tr:hover {
      background-color: #dde0e2ff !important;
    }

    /* Responsive tweaks */
    @media (max-width: 991.98px) {

      /* Tablet */
      .custom-hover th,
      .custom-hover td {
        white-space: normal;
        /* allow wrapping */
        font-size: 0.85rem;
        padding: 6px 8px;
      }
    }

    @media (max-width: 576px) {

      /* Mobile */
      .custom-hover th,
      .custom-hover td {
        font-size: 0.75rem;
        padding: 4px 6px;
      }
    }
  </style>