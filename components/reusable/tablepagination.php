<script>
  function createPaginator({
    data,
    rowsPerPage = 10,
    renderPageCallback,
    paginationContainerId,
    defaultSortFn = null
  }) {
    let currentPage = 1;

    function renderPage() {
      let sortedData = defaultSortFn ? [...data].sort(defaultSortFn) : [...data];
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const pageData = sortedData.slice(start, end);

      renderPageCallback(pageData, currentPage);
      updatePaginationControls();
    }


    function updatePaginationControls() {
      const container = document.getElementById(paginationContainerId);
      container.innerHTML = '';

      const totalPages = Math.ceil(data.length / rowsPerPage);
      if (totalPages <= 1) return;

      const wrapper = document.createElement('div');
      wrapper.classList.add('d-flex', 'align-items-center', 'justify-content-center', 'gap-2', 'flex-wrap');

      const createBtn = (text, disabled, onClick) => {
        const btn = document.createElement('button');
        btn.textContent = text;
        btn.className = 'btn btn-sm btn-outline-primary px-3 fw-semibold';
        btn.disabled = disabled;
        btn.style.transition = 'background-color 0.2s ease';
        btn.addEventListener('mouseenter', () => {
          if (!btn.disabled) btn.classList.add('btn-primary', 'text-white');
        });
        btn.addEventListener('mouseleave', () => {
          if (!btn.disabled) btn.classList.remove('btn-primary', 'text-white');
        });
        btn.addEventListener('click', onClick);
        return btn;
      };

      // Prev button
      wrapper.appendChild(createBtn('Prev', currentPage === 1, () => {
        currentPage--;
        renderPage();
      }));

      // Dynamic page numbers — group of 3
      const groupSize = 3;
      const currentGroup = Math.floor((currentPage - 1) / groupSize);
      const groupStart = currentGroup * groupSize + 1;
      const groupEnd = Math.min(groupStart + groupSize - 1, totalPages);

      for (let i = groupStart; i <= groupEnd; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.textContent = i;
        pageBtn.className = 'btn btn-sm ' + (i === currentPage ? 'btn-primary text-white' : 'btn-outline-primary');
        pageBtn.addEventListener('click', () => {
          currentPage = i;
          renderPage();
        });
        wrapper.appendChild(pageBtn);
      }

      // Next button
      wrapper.appendChild(createBtn('Next', currentPage === totalPages, () => {
        currentPage++;
        renderPage();
      }));

      container.appendChild(wrapper);
    }

    return {
      setData(newData) {
        data = newData;
        currentPage = 1;
        renderPage();
      },
      render: renderPage,
      goToPage(page) {
        currentPage = Math.max(1, Math.min(page, Math.ceil(data.length / rowsPerPage)));
        renderPage();
      }
    };

  }
</script>