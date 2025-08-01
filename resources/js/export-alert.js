const userId = Nova.config('userId');
let alertShown = false;

const userId = Nova.config('userId');

if (userId) {
  fetch(`/nova-vendor/nova-data-sync/export-ongoing/${userId}`)
    .then(res => res.json())
    .then(exportIdsToWatch => {
      exportIdsToWatch.forEach(exportId => {
        const interval = setInterval(() => {
          fetch(`/nova-vendor/nova-data-sync/export-status/${exportId}`)
            .then(res => res.json())
            .then(data => {
              if (data.done) {
                clearInterval(interval);

                if (data.filename) {
                  Nova.success(`Export finished.`);
                } else {
                  Nova.success(`xport finished (no data).`);
                }

                // optional: clear status
                fetch(`/nova-vendor/nova-data-sync/export-status/${exportId}?clear=true`);
              }
            })
            .catch(err => {
              console.error(`Error polling export ${exportId}:`, err);
            });
        }, 3000); // tiap 3 detik
      });
    });
}