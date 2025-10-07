<!-- tablesorting.php -->
<script>
function enableTableSorting(tableSelector) {
  const table = document.querySelector(tableSelector);
  if (!table) return;

  const headers = table.querySelectorAll("th");
  headers.forEach((header, index) => {
    const icon = header.querySelector(".sort-icon");
    if (!icon) return;

    header.addEventListener("click", () => {
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));
      const isAsc = header.classList.toggle("asc");
      header.classList.toggle("desc", !isAsc);

      // Clear all icons
      table.querySelectorAll(".sort-icon").forEach(i => i.textContent = "");
      icon.textContent = isAsc ? "▲" : "▼";

      rows.sort((a, b) => {
        const aText = a.children[index]?.textContent.trim();
        const bText = b.children[index]?.textContent.trim();
        return isAsc
          ? aText.localeCompare(bText, undefined, { numeric: true })
          : bText.localeCompare(aText, undefined, { numeric: true });
      });

      tbody.innerHTML = "";
      rows.forEach(row => tbody.appendChild(row));
    });
  });
}
</script>
