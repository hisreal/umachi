(() => {
  'use strict';

  const state = {
    photoCaptured: false,
    objectUrl: '',
    historyPage: 1,
    rowsPerPage: 4,
  };

  const selectors = {
    currentDate: document.getElementById('currentDate'),
    liveClock: document.getElementById('liveClock'),
    photoStatus: document.getElementById('photoStatus'),
    photoInput: document.getElementById('photoInput'),
    capturedImage: document.getElementById('capturedImage'),
    cameraPlaceholder: document.getElementById('cameraPlaceholder'),
    takePictureBtn: document.getElementById('takePictureBtn'),
    retakePhotoBtn: document.getElementById('retakePhotoBtn'),
    removePhotoBtn: document.getElementById('removePhotoBtn'),
    clockInBtn: document.getElementById('clockInBtn'),
    clockInForm: document.getElementById('clockInForm'),
    historySearch: document.getElementById('historySearch'),
    historyDate: document.getElementById('historyDate'),
    historyRows: Array.from(document.querySelectorAll('[data-history-row]')),
    historyCount: document.getElementById('historyCount'),
    prevPageBtn: document.getElementById('prevPageBtn'),
    nextPageBtn: document.getElementById('nextPageBtn'),
  };

  /**
   * Show a compact SweetAlert2 message when available.
   */
  const notify = (type, title, text = '') => {
    if (window.Swal) {
      window.Swal.fire({
        icon: type,
        title,
        text,
        confirmButtonColor: '#F68B34',
        timer: type === 'success' ? 2200 : undefined,
      });
      return;
    }

    window.alert(`${title}${text ? `\n${text}` : ''}`);
  };

  /**
   * Keep the current date and live digital clock fresh.
   */
  const startClock = () => {
    const render = () => {
      const now = new Date();

      selectors.currentDate.textContent = now.toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      });

      selectors.liveClock.textContent = now.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    };

    render();
    window.setInterval(render, 1000);
  };

  /**
   * Update a visual status pill.
   */
  const setStatus = (element, label, statusClass) => {
    element.textContent = label;
    element.className = `status-pill ${statusClass}`;
  };

  /**
   * Enable clock-in only when a selfie has been selected.
   */
  const syncClockInButton = () => {
    selectors.clockInBtn.disabled = !state.photoCaptured;
  };

  /**
   * Trigger the hidden native camera file input.
   */
  const openNativeCamera = () => {
    selectors.photoInput.click();
  };

  /**
   * Release the current object URL to avoid keeping image blobs in memory.
   */
  const revokeCurrentPhoto = () => {
    if (state.objectUrl) {
      URL.revokeObjectURL(state.objectUrl);
      state.objectUrl = '';
    }
  };

  /**
   * Preview the image returned by the device camera/file picker.
   */
  const handlePhotoSelection = () => {
    const file = selectors.photoInput.files && selectors.photoInput.files[0];

    if (!file) {
      return;
    }

    if (!file.type.startsWith('image/')) {
      selectors.photoInput.value = '';
      notify('warning', 'Invalid File', 'Please take or select an image file.');
      return;
    }

    revokeCurrentPhoto();
    state.objectUrl = URL.createObjectURL(file);
    selectors.capturedImage.src = state.objectUrl;
    selectors.capturedImage.hidden = false;
    selectors.cameraPlaceholder.hidden = true;

    state.photoCaptured = true;
    setStatus(selectors.photoStatus, 'Photo Ready', 'status-verified');
    selectors.retakePhotoBtn.disabled = false;
    selectors.removePhotoBtn.disabled = false;
    syncClockInButton();
  };

  /**
   * Clear the captured photo preview and return to the empty state.
   */
  const clearPhoto = () => {
    revokeCurrentPhoto();
    selectors.photoInput.value = '';
    selectors.capturedImage.hidden = true;
    selectors.capturedImage.removeAttribute('src');
    selectors.cameraPlaceholder.hidden = false;

    state.photoCaptured = false;
    setStatus(selectors.photoStatus, 'Waiting...', 'status-waiting');
    selectors.retakePhotoBtn.disabled = true;
    selectors.removePhotoBtn.disabled = true;
    syncClockInButton();
  };


  /**
   * Filter and paginate static attendance rows on the frontend.
   */
  const renderHistory = () => {
    const searchTerm = selectors.historySearch.value.trim().toLowerCase();
    const selectedDate = selectors.historyDate.value;

    const filteredRows = selectors.historyRows.filter((row) => {
      const matchesSearch = row.textContent.toLowerCase().includes(searchTerm);
      const matchesDate = !selectedDate || row.dataset.date === selectedDate;

      return matchesSearch && matchesDate;
    });

    const totalPages = Math.max(1, Math.ceil(filteredRows.length / state.rowsPerPage));
    state.historyPage = Math.min(state.historyPage, totalPages);

    const start = (state.historyPage - 1) * state.rowsPerPage;
    const visibleRows = filteredRows.slice(start, start + state.rowsPerPage);

    selectors.historyRows.forEach((row) => {
      row.hidden = true;
    });

    visibleRows.forEach((row) => {
      row.hidden = false;
    });

    selectors.historyCount.textContent = filteredRows.length
      ? `Showing ${start + 1}-${start + visibleRows.length} of ${filteredRows.length} records`
      : 'No matching records';

    selectors.prevPageBtn.disabled = state.historyPage <= 1;
    selectors.nextPageBtn.disabled = state.historyPage >= totalPages;
  };

  /**
   * Bind all page events.
   */
  const bindEvents = () => {
    selectors.takePictureBtn.addEventListener('click', openNativeCamera);
    selectors.retakePhotoBtn.addEventListener('click', openNativeCamera);
    selectors.removePhotoBtn.addEventListener('click', clearPhoto);
    selectors.photoInput.addEventListener('change', handlePhotoSelection);
    selectors.clockInForm?.addEventListener('submit', (event) => {
      if (!state.photoCaptured) {
        event.preventDefault();
        notify('warning', 'Photo Required', 'Please take a fresh selfie before clocking in.');
      }
    });

    selectors.historySearch.addEventListener('input', () => {
      state.historyPage = 1;
      renderHistory();
    });

    selectors.historyDate.addEventListener('change', () => {
      state.historyPage = 1;
      renderHistory();
    });

    selectors.prevPageBtn.addEventListener('click', () => {
      state.historyPage -= 1;
      renderHistory();
    });

    selectors.nextPageBtn.addEventListener('click', () => {
      state.historyPage += 1;
      renderHistory();
    });

    window.addEventListener('beforeunload', revokeCurrentPhoto);
  };

  document.addEventListener('DOMContentLoaded', () => {
    startClock();
    bindEvents();
    renderHistory();
    syncClockInButton();
  });
})();