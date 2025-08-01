const userId = Nova.config('userId');
let alertShown = false;
let lastShownExportId = null;

if (userId) {
  const interval = setInterval(() => {
    fetch(`/nova-vendor/nova-data-sync/export-status/${userId}`)
      .then(res => res.json())
      .then(data => {
        if (data.done && data.export_id !== lastShownExportId) {
          lastShownExportId = data.export_id;

          if (data.filename) {
            Nova.success(`Export finished.`);
          } else {
            Nova.success('Export finished (no data).');
          }

          fetch(`/nova-vendor/nova-data-sync/export-status/${userId}?clear=true`);
        }
      })
      .catch(error => {
        console.error('Error polling export status:', error);
      });
  }, 3000);
}
