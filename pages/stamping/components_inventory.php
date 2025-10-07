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
          <div class="row mb-3 col-md-3">


            <input
              type="text"
              id="filter-input"
              class="form-control"
              placeholder="Type to filter..." />

          </div>
          <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
            <thead>
              <tr>
                <th style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                <th style="width: 18%; text-align: center;">Component Name <span class="sort-icon"></span></th>
                <th style="width: 3%; text-align: center;">Usage<span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Quantity <span class="sort-icon"></span></th>
                <th style="width: 7%; text-align: center;">Raw Material Qty <span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Stock Status <span class="sort-icon"></span></th>
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
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load inventory data.'
          });
        });
    }

    function renderTable(data) {
      dataBody.innerHTML = '';

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
    `;
        dataBody.appendChild(row);
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