# Ruffian Target Promotion — Deployment Guide

## For: unlimitedwebhosting.co.uk (or any PHP shared hosting)

---

### Folder Structure

Upload the entire contents of this folder to your hosting under the `/target` directory:

```
public_html/
└── target/
    ├── index.html          ← Main landing page
    ├── .htaccess            ← Security & caching rules
    ├── css/
    │   └── style.css        ← Styles
    ├── js/
    │   └── app.js           ← Frontend logic
    ├── fonts/
    │   ├── ed-celandine-regular.woff2
    │   └── ed-celandine-regular.woff
    ├── api/
    │   └── submit.php       ← Backend API
    └── data/
        ├── .htaccess        ← Blocks direct access to CSV
        └── submissions.csv  ← Data storage (auto-populated)
```

---

### Step-by-Step Deployment

1. **Upload files** via cPanel File Manager or FTP to `public_html/target/`

2. **Set file permissions:**
   ```
   data/ directory      → 755
   data/submissions.csv → 666 (writable by PHP)
   data/.htaccess       → 644
   All other files      → 644
   All directories      → 755
   ```

3. **Verify PHP** is enabled on your hosting (it should be by default).

4. **Test the page** by visiting: `https://ruffian.uz/target/`

5. **Test the API** — enter a test Instagram username and verify:
   - Code is displayed on Step 2
   - `data/submissions.csv` has a new row

---

### Downloading Collected Data

The CSV file at `data/submissions.csv` contains all submissions:
- **code** — Unique 5-digit code
- **timestamp** — Date/time of submission (server time)
- **instagram_username** — User's Instagram handle

To download, use cPanel File Manager or FTP to access:
`public_html/target/data/submissions.csv`

The `.htaccess` rules prevent anyone from downloading it via browser URL.

---

### Security Notes

- The `data/` directory is protected from direct web access via `.htaccess`
- The PHP API uses file locking to prevent CSV corruption from concurrent writes
- Instagram usernames are sanitised and validated server-side
- Duplicate users get their existing code back (no duplicates created)

---

### Troubleshooting

| Issue | Solution |
|-------|----------|
| "Server error" on form submit | Check that `data/` directory and `submissions.csv` are writable (permissions 755/666) |
| Page is blank | Ensure PHP is enabled; check that all files uploaded correctly |
| Fonts not loading | Clear browser cache; verify `fonts/` directory contains both .woff and .woff2 files |
| 403 on API | Check `.htaccess` is not blocking `/api/submit.php` — only `/data/` should be blocked |
