<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
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
      <li class="breadcrumb-item" aria-current="page">Warehouse Section</li>
    </ol>
  </nav>

  <div class="row">
    <div class="col-md-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title mb-0">Pulled out History</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>

          <div class="row mb-3 align-items-end g-2">
            <!-- Left Section: Column Select + Search -->
            <div class="col-md-3 d-flex gap-2">
              <div class="flex-grow-1">
                <label for="search-input" class="form-label">Search</label>
                <input type="text" id="search-input" class="form-control" placeholder="Type to filter..." />
              </div>
            </div>

            <!-- Right Section: From and To Dates -->
            <div class="col-md-9 d-flex justify-content-end gap-2 ms-auto">
              <div>
                <label for="from-date" class="form-label">From</label>
                <input type="date" id="from-date" class="form-control" />
              </div>
              <div>
                <label for="to-date" class="form-label">To</label>
                <input type="date" id="to-date" class="form-control" />
              </div>
            </div>
          </div>


          <div class="table-responsive">
            <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
              <thead>
                <tr>
                  <th style="text-align: center;">Material No <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Material Description <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Model <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Total Quantity <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Shift <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Lot No <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Date Needed <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Pulled At <span class="sort-icon"></span></th>
                </tr>
              </thead>
              <tbody id="data-body" style="word-wrap: break-word; white-space: normal;">
                <!-- Table rows here -->
              </tbody>
            </table>
          </div>


          <!-- Pagination -->
          <div id="pagination" class="d-flex justify-content-center mt-3"></div>

          <!-- Last Updated -->


        </div>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/sweetalert2@11.js"></script>
<script>
  let fullDataSet = [];

  let paginator;

  // Fetch data from API (kept outside DOMContentLoaded)
  function getData(model) {
    fetch('api/fg/getPulledoutHistory?model=' + encodeURIComponent(model || '') + '&_=' + new Date().getTime())
      .then(res => res.json())
      .then(data => {
        fullDataSet = data || [];
        if (paginator) {
          paginator.setData(fullDataSet);
        }
      })
      .catch(err => {
        console.error('Error loading data:', err);
      });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('data-body');
    const searchInput = document.getElementById('search-input');
    const columnSelect = document.getElementById('column-select');
    const fromDateInput = document.getElementById('from-date');
    const toDateInput = document.getElementById('to-date');

    // Initialize paginator globally
    paginator = createPaginator({
      data: [],
      rowsPerPage: 10,
      paginationContainerId: 'pagination',
      defaultSortFn: (a, b) => new Date(b.pulled_at) - new Date(a.pulled_at),
      renderPageCallback: (pageData) => {
        tbody.innerHTML = '';
        pageData.forEach(item => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td class="text-center">${highlightText(item.material_no, currentFilterQuery)}</td>
            <td class="text-center text-truncate" style="max-width: 200px;white-space: normal; word-wrap: break-word;">${highlightText(item.material_description, currentFilterQuery)}</td>
            <td class="text-center">${highlightText(item.model, currentFilterQuery)}</td>
            <td class="text-center">${highlightText(item.total_quantity?.toString() || '', currentFilterQuery)}</td>
            <td class="text-center">${highlightText(item.shift || '', currentFilterQuery)}</td>
            <td class="text-center">${highlightText(item.lot_no || '', currentFilterQuery)}</td>
            <td class="text-center">${highlightText(item.date_needed || '', currentFilterQuery)}</td>
            <td class="text-center">${highlightText(item.pulled_at || '', currentFilterQuery)}</td>
          `;
          tbody.appendChild(row);
        });
        document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
      }
    });

    // Setup global search filter
    setupSearchFilter({
      filterInputSelector: '#search-input',
      data: fullDataSet,
      onFilter: (filtered, query) => {
        currentFilterQuery = query || '';
        paginator.setData(filtered);
      }
    });

    // Combined column + date filter
    function applyCombinedFilter() {
      const column = columnSelect.value;
      const keyword = searchInput.value.toLowerCase();
      const fromDate = fromDateInput.value;
      const toDate = toDateInput.value;

      let filtered = [...fullDataSet];

      // Filter by column keyword
      if (column && keyword) {
        filtered = filtered.filter(row => {
          const val = (row[column] ?? '').toString().toLowerCase();
          return val.includes(keyword);
        });
      }

      // Filter by date range
      if (fromDate || toDate) {
        filtered = filtered.filter(row => {
          const pulledAt = new Date(row.pulled_at);
          const from = fromDate ? new Date(fromDate + 'T00:00:00') : null;
          const to = toDate ? new Date(toDate + 'T23:59:59') : null;
          return (!from || pulledAt >= from) && (!to || pulledAt <= to);
        });
      }

      paginator.setData(filtered);
    }

    fromDateInput.addEventListener('change', applyCombinedFilter);
    toDateInput.addEventListener('change', applyCombinedFilter);

    enableTableSorting(".table");
  });
</script>
<style>
  /* Hover effect for all rows */
  .custom-hover tbody tr:hover td,
  .custom-hover tbody tr:hover th {
    background-color: #dde0e2 !important;
  }

  /* Tablet only: horizontal scroll and sticky columns */
  @media (max-width: 991.98px) {
    .table-responsive {
      overflow-x: auto;
      width: 100%;
    }

    .custom-hover {
      min-width: 900px;
      /* adjust according to total columns */
      table-layout: fixed;
    }

    .custom-hover th,
    .custom-hover td {
      white-space: nowrap;
      font-size: 0.85rem;
      padding: 12px 16px;
    }

    /* Sticky first column (Material No) */
    .custom-hover th:nth-child(1),
    .custom-hover td:nth-child(1) {
      position: sticky;
      left: 0;
      background: #f9f9f9;
      z-index: 10;
      width: 100px;
    }

    /* Sticky second column (Material Description) */
    .custom-hover th:nth-child(2),
    .custom-hover td:nth-child(2) {
      position: sticky;
      left: 100px;
      /* width of first column */
      background: #f9f9f9;
      z-index: 10;
      width: 200px;
    }
  }

  /* Mobile adjustments */
  @media (max-width: 576px) {

    .custom-hover th,
    .custom-hover td {
      font-size: 0.75rem;
      padding: 4px 6px;
    }
  }
</style>