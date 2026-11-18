# BK Packages ERP — System State & Project Handoff

> **Last Updated**: November 18, 2026  
> **Repository**: [https://github.com/logfroze/bk-packages-erp](https://github.com/logfroze/bk-packages-erp) (`main` branch)  
> **Client**: BK Packages — Okara Plant ERP (Mirza Sohail)

---

## 1. Tech Stack & Environment
* **Backend Framework**: Laravel 13.x / PHP 8.3
* **Database**: Neon Serverless PostgreSQL (AWS Singapore, Pooled connection)
  * *Critical Config*: `\PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php` (avoids `cached plan must not change result type` error with pgBouncer pooler).
* **Frontend**: Blade templates, Tailwind CSS, Google Material Symbols, Vanilla JS.
* **Offline/PWA**: Service Worker (`public/sw.js`), IndexedDB offline sync queue, PWA manifest.
* **AI Copilot**: Gemini Flash (`gemini-3.5-flash-lite` / `gemini-3-flash-preview`), Guzzle IPv4 transport, system knowledge of production, inventory, cash ledger, and gate registers.
* **Serverless Deployment**: Vercel ready with `vercel.json` and `api/index.php`.

---

## 2. Key Modules & Database Models
| Module | Model | Primary Route | Purpose |
| :--- | :--- | :--- | :--- |
| **Dashboard** | Unified Stats | `/` | Real-time production, safe balance, trucks, worker headcounts with 20s aggregate caching. |
| **Production** | `ProductionBatch` | `/production` | 3-step corrugated box workflow (Printing, Corrugation/Nali, Finishing/Conversion). |
| **Stock & Reels** | `PaperReel` | `/stock` | Paper reel inventory (weight, GSM, BF, fluting/kraft, supplier). |
| **Gate Register** | `GateRegister` | `/gate` | Truck inward/outward registers, challan photos, net weight calculations. |
| **Party Khata** | `Party`, `Voucher` | `/khata` | Customer/vendor credit ledgers, Roznamcha cash book, billing. |
| **Local Party** | `LocalPartyTransaction` | `/khata` (Local tab) | Dual-direction daily small-shop expenses (bought & sold/supplied). |
| **Petty Cash** | `PettyCashTransaction` | `/petty-cash` | Daily factory cash drawer/safe, spot sales, kharch slips, printable day sheets. |
| **Media Vault** | `MediaVaultItem` | `/media-vault` | Hybrid model master gallery of all receipts, slips, and camera snaps across forms. |
| **Factory Chat** | `ChatMessage` | `/chat` | Internal encrypted messaging between Admin, Manager, and Owner. |
| **AI Copilot** | `AiMemory` | `POST /ai-copilot/chat` | Built-in smart factory assistant panel. |

---

## 3. Latest Completed Features (Nov 17-18, 2026)
1. **Local Party Ledger**:
   - Added in Party Khata for daily vendor micro-expenses.
   - Dual-direction tracking (what we bought vs what they took).
   - Fully customizable descriptions, amounts, and notes.
2. **Media & Receipts Vault (Hybrid Model C)**:
   - Live camera capture and file upload directly inside Petty Cash, Gate Register, and Khata forms.
   - Centralized `/media-vault` gallery with search, category filtering, lightbox preview, and downloads.
3. **WhatsApp-Style Internal Chat System (`/chat`)**:
   - **Inline Edit (✏️)**: Edit sent messages with Telegram/WhatsApp-style inline banner and `(edited)` badge.
   - **Delete for Everyone (🗑️)**: Replaces bubble with placeholder and **immediately unlinks/deletes the physical file from `public/uploads/chat/`** to prevent disk bloat.
   - **Clear Chat (🧹)**: Wipes room messages and unlinks attached files from disk.
   - **Storage Auto-Clean Modal**: Choice of 24 Hours, 7 Days, 30 Days, or All.
   - **Automated Daily Pruning**: CLI command `php artisan chat:prune --days=30` scheduled in `routes/console.php` every night at 02:00 AM.
4. **Solid & Minimalist Enterprise UI**:
   - Replaced bubbly/shiny modal designs with crisp `bg-slate-50` headers and solid `#0F172A` buttons.
   - Applied global high-contrast borders (`#CBD5E1`), 2px solid table header baselines, and crisp form inputs.
5. **Database & Query Performance**:
   - Consolidated 15+ sequential queries into unified `selectRaw` calls.
   - Neon PostgreSQL pooler `ATTR_EMULATE_PREPARES` permanent fix.

---

## 4. User Profiles & Credentials
1. **Mirza Sohail** (`mz.sohail764245@gmail.com`): Role `ADMIN` (Full administrative & editing access).
2. **Dr. Ghulam Abbas** (`drghulamabbas55@gmail.com`): Role `MANAGER` (Operational management & expense entry).
3. **Company Owner** (`owner@bkpackages.com`): Role `OWNER` (Executive view-only dashboard & financial oversight).

---

## 5. Local Dev & Testing Commands
* **Start local server**: `php -S 127.0.0.1:8000 server.php`
* **Run migrations**: `php artisan migrate`
* **Clear view cache**: `php artisan view:clear`
* **Prune old chat files**: `php artisan chat:prune --hours=24` (or `--days=30`)
