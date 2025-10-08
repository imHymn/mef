class ModelDrawer {
  constructor(options = {}) {
    this.model =
      localStorage.getItem("selectedModel") || options.initialModel || null;
    this.container = options.container || document.body;
    this.onModelChange = options.onModelChange || function () {};

    this._render();
    this._setupEvents();
    this._fetchModels();
  }
  _injectStyles() {
    if (document.getElementById("model-drawer-styles")) return; // avoid duplicates

    const style = document.createElement("style");
    style.id = "model-drawer-styles";
    style.textContent = `
    .drawer {
      position: fixed;
      top: 0;
      right: -300px;
      width: 250px;
      height: 100%;
      background: white;
      box-shadow: -2px 0 5px rgba(0, 0, 0, 0.2);
      transition: right 0.3s ease;
      z-index: 1050;
    }
    .drawer.open {
      right: 0;
    }
    .openDrawerBtn {
      position: fixed;
      right: 20px;
      top: 97%;
      transform: translateY(-50%);
      z-index: 1100;
      background: transparent;
      border: none;
      padding: 0;
    }
  `;
    document.head.appendChild(style);
  }

  _render() {
    this._injectStyles(); // inject CSS

    this.drawer = document.createElement("div");
    this.drawer.className = "drawer";

    this.drawer.innerHTML = `
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong style="font-size: 1rem; display: block;">Models</strong>
          <button class="btn btn-sm btn-outline-secondary closeDrawerBtn">&times;</button>
        </div>
        <div class="list-group list-group-flush modelContainer"></div>
      </div>
    `;

    // Create open button
    this.openBtn = document.createElement("button");
    this.openBtn.className = "openDrawerBtn";
    this.openBtn.style.cssText = `
      position: fixed; right: 20px; top: 97%; transform: translateY(-50%);
      z-index: 1100; background: transparent; border: none; padding: 0;
    `;
    this.openBtn.innerHTML = `<img src="/mes/assets/images/car-front.svg" alt="Car Icon" style="width: 25px; height: 25px;">`;

    this.container.appendChild(this.drawer);
    this.container.appendChild(this.openBtn);

    this.modelContainer = this.drawer.querySelector(".modelContainer");
    this.closeBtn = this.drawer.querySelector(".closeDrawerBtn");
  }

  _setupEvents() {
    this.openBtn.addEventListener("click", () => {
      this.drawer.classList.add("open");
      this.openBtn.style.display = "none";
    });

    this.closeBtn.addEventListener("click", () => {
      this.drawer.classList.remove("open");
      this.openBtn.style.display = "block";
    });
  }

  _fetchModels() {
    fetch(`api/reusable/getCustomerandModel`)
      .then((res) => res.json())
      .then((result) => {
        console.log(result);
        this._renderModels(result.data || []);
      })
      .catch(() => {
        showAlert('error','Error', 'Failed to load customer and model data.');
      });
  }

  _renderModels(models) {
    this.modelContainer.innerHTML = "";

    const scrollWrapper = document.createElement("div");
    scrollWrapper.className = "overflow-auto";
    scrollWrapper.style.maxHeight = "calc(100vh - 100px)";
    scrollWrapper.style.padding = "0.5rem";

    const btnGroup = document.createElement("div");
    btnGroup.className = "btn-group-vertical w-100";
    btnGroup.role = "group";

    let selectedButton = null;

    models.forEach((item) => {
      const btn = document.createElement("button");
      btn.textContent = item.model;
      btn.className = "btn fw-bold border mb-1 fs-4 py-3 w-100";

      if (item.model === this.model) {
        btn.classList.add("active", "bg-primary", "text-white");
        selectedButton = btn; // store for later auto-trigger
      }

      btn.addEventListener("click", () => {
        this.model = item.model;
        this.onModelChange(this.model);
        localStorage.setItem("selectedModel", this.model);

        btnGroup.querySelectorAll("button").forEach((b) => {
          b.classList.remove("active", "bg-primary", "text-white");
        });
        btn.classList.add("active", "bg-primary", "text-white");
        this.drawer.classList.remove("open");
        this.openBtn.style.display = "block";
      });

      btn.addEventListener("mouseenter", () => {
        if (!btn.classList.contains("active")) {
          btn.classList.add("bg-primary", "text-white");
        }
      });
      btn.addEventListener("mouseleave", () => {
        if (!btn.classList.contains("active")) {
          btn.classList.remove("bg-primary", "text-white");
        }
      });

      btnGroup.appendChild(btn);
    });

    scrollWrapper.appendChild(btnGroup);
    this.modelContainer.appendChild(scrollWrapper);

    // ✅ Auto-trigger model load if savedModel exists
    if (selectedButton) {
      selectedButton.click();
    }
  }
}
