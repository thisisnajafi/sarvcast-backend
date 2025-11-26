SarvCast Admin Dashboard – Responsive Fix Task List
===================================================

## Global Layout & Navigation

- [x] **Convert fixed sidebar to responsive off‑canvas on small screens**
  - `resources/views/admin/layouts/app.blade.php` currently uses a fixed `w-64` sidebar with `mr-64` main content.
  - On small screens, this causes horizontal overflow and wasted space even though JS hides it conditionally.
  - Refactor to:
    - Use Tailwind responsive utilities (`hidden lg:block`, `block lg:hidden`, etc.) instead of manual JS width toggling.
    - Wrap main content in a container that uses `lg:mr-64` and `mr-0` on smaller breakpoints.
    - Ensure `overflow-x-hidden` on `body` and main container when sidebar is open on mobile.

- [x] **Unify and simplify sidebar toggle logic for mobile**
  - Current toggle in `app.blade.php` manually checks `window.innerWidth < 1024` and toggles `hidden`/`mr-64` classes via JS.
  - This is fragile on orientation change and resize, and can desync from Tailwind breakpoints.
  - Replace with:
    - A single source of truth (e.g. `data-state="open|closed"`) and Tailwind utility classes driven by that state.
    - Use CSS (e.g. `translate-x-full`, `fixed`, `inset-y-0 right-0`) for slide‑in panel on mobile instead of toggling `hidden` only.

- [x] **Ensure header and top bar elements wrap/stack correctly on small screens**
  - `header` in `app.blade.php` uses `flex justify-between items-center` with a large search bar and full user profile info.
  - On narrow viewports, search + dark mode toggle + notifications + user avatar overflow horizontally.
  - Tasks:
    - Add responsive classes to hide/compact secondary elements on mobile (e.g. hide text email, shrink or hide date, collapse search).
    - Allow header content to wrap (`flex-wrap`, `space-y-*`) or stack in two rows below `md`.
    - Provide a small “search” icon that opens a modal / overlay on mobile instead of always showing a 256px input.

- [x] **Audit all `grid-cols-*` vs fixed widths to avoid horizontal scroll**
  - Multiple dashboard pages use `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3/4` while child cards contain fixed paddings, large icons, and sometimes long numbers/text.
  - On older devices with small viewport width, the combination of padding + card min‑width can cause slight horizontal scrolling.
  - For each dashboard:
    - Ensure cards do not have implicit min‑width > 100% of the grid column.
    - Use `w-full` and avoid hardcoded widths (except icons).
    - Add `overflow-hidden` and `text-ellipsis` where long numbers/titles appear in cards.

## Core Admin Dashboard (`resources/views/admin/dashboard.blade.php`)

- [x] **Make the hero / welcome section fully responsive**
  - Current layout uses `flex items-center justify-between` with a left content block and a right decorative icon (`hidden md:block`).
  - On small screens, the left stats row (`flex items-center space-x-6`) can compress uncomfortably.
  - Tasks:
    - Allow the stats row to wrap using `flex-wrap` and responsive spacing (`space-y-*` on small screens).
    - Ensure the hero padding is reduced on small viewports and text sizes use responsive variants (`text-xl sm:text-2xl md:text-3xl`).

- [x] **Normalize statistics card grid across breakpoints**
  - The main stats section uses `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4`.
  - All four cards are relatively wide with long labels and three stats per card in some cases.
  - Tasks:
    - Verify that at `md` breakpoint layout still looks balanced (two columns); if not, consider `md:grid-cols-2 xl:grid-cols-4` instead of `lg`.
    - Add `min-w-0` to internal flex containers to let text truncate instead of forcing overflow.

- [x] **Ensure secondary “Plan Sales Overview” row behaves on small screens**
  - `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3` with 3 wide gradient cards.
  - On medium breakpoints, the last card can get squashed or wrap awkwardly if container width is small.
  - Tasks:
    - Validate at typical tablet widths; if necessary, use `md:grid-cols-1 lg:grid-cols-3` or `sm:grid-cols-2 xl:grid-cols-3`.
    - Ensure internal icon blocks do not enforce min‑width that breaks grid layout.

- [x] **Improve responsiveness of “Quick Actions” icon grid**
  - Quick actions use `grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6`.
  - On small phones, 2 columns with large icons and labels may still be tight.
  - Tasks:
    - Confirm buttons have adequate tap area (>44px) but card width does not exceed screen width.
    - Add `text-center` but allow label text to wrap to 2 lines with `max-w-[X]` to avoid overflow.

- [x] **Tighten content grids and tables to avoid nested scroll on small screens**
  - Sections like “Recent Stories”, “Recent Users”, “Recent Payments”, “Top Categories”, “Top Rated Stories” are side‑by‑side on large screens (`lg:grid-cols-2` / `xl:grid-cols-3`).
  - On mobile they stack, but inner containers use multi‑column flex for status chips and metadata that may overflow.
  - Tasks:
    - Add `min-w-0` to flex children and `truncate` to long titles (e.g. story names, emails).
    - Confirm `overflow-x-auto` is used only around actual tables, not around card‑style lists, to avoid unnecessary horizontal scrolling.

- [x] **Chart area responsiveness**
  - “Analytics Chart Section” placeholder and card uses fixed `p-8` and multiple chips in a single `flex`.
  - On small screens, chips can wrap poorly or overflow.
  - Tasks:
    - Reduce padding for mobile (`p-4 sm:p-6 lg:p-8`).
    - Allow the chip row to wrap (`flex-wrap gap-2`) for narrow widths.

## Stories Dashboard (`resources/views/admin/dashboards/stories.blade.php`)

- [x] **Standardize statistics card spacing and wrapping**
  - Multiple rows of four cards are used (`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4`).
  - Labels like “اپیزودهای منتشر شده” and numbers may overlap at `md` width depending on language direction.
  - Tasks:
    - Use responsive text sizes and allow labels to wrap to two lines.
    - Ensure icons and labels are aligned vertically without forcing extra width.

- [x] **Make chart containers fully height‑responsive**
  - Charts use `div id="...Chart" class="h-64"` with `maintainAspectRatio: false` in Chart.js.
  - On small screens, 64 Tailwind units may be too tall, causing significant vertical scrolling in stacked layout.
  - Tasks:
    - Consider using responsive heights (`h-56 sm:h-64 md:h-72`) or `min-h-[...]` depending on device.
    - Ensure parent grid (`grid grid-cols-1 lg:grid-cols-2`) does not create nested scrollbars.

- [x] **Optimize “Performance Metrics” grid on mobile**
  - Uses `grid grid-cols-1 md:grid-cols-4`, four numeric KPIs in one row at `md+`.
  - On smaller widths, these stack but internal text may still compress or overlap.
  - Tasks:
    - Add `space-y-4` when stacked and ensure `text-center` and `max-w-full` on values and labels.
 
- [x] **Tables and lists overflow control**
  - Lists like “Top Performing Stories” and “Recent Stories” cards rely on `p-3 bg-gray-50 rounded-lg` with right/left aligned counts.
  - Very long titles or category names can overflow.
  - Tasks:
    - Apply `truncate` or `line-clamp-2` to titles and limit category labels’ width.
    - Confirm `min-w-0` on flex children so truncation works properly.

## Sales Dashboard (`resources/views/admin/dashboards/sales.blade.php`)

- [x] **Ensure revenue statistics cards scale well on small widths**
  - Several top cards with long formatted numbers and “تومان” label.
  - Risk of text wrapping in awkward ways or clipping at small `md` widths.
  - Tasks:
    - Use `text-sm sm:text-base` for labels and `text-xl sm:text-2xl` for amounts.
    - Add `whitespace-nowrap` on short labels where appropriate, but not on long ones.
 
- [x] **Responsive behavior of revenue and payment method charts**
  - Both charts are in `grid grid-cols-1 lg:grid-cols-2`; at `lg` they sit side by side.
  - At intermediate widths (small laptops) their combined width and `h-64` height need confirmation.
  - Tasks:
    - Verify no horizontal scrollbar appears at typical laptop widths.
    - Possibly shift the second chart under the first at `xl` instead of `lg` if space is constrained.
 
- [x] **Monthly comparison and conversion metrics grids**
  - `grid grid-cols-1 md:grid-cols-3` and `grid grid-cols-1 md:grid-cols-4` within relatively narrow containers.
  - On small tablets, each card can become too narrow.
  - Tasks:
    - Consider `md:grid-cols-2 xl:grid-cols-3` or `xl:grid-cols-4` instead of using `md` for 3/4 columns.
    - Ensure each cell has `min-w-[8rem]` or similar to maintain readability, and allow wrapping when stacked.

- [x] **Top customers/transactions lists truncation**
  - Email addresses, names, and long Persian strings may overflow list rows.
  - Tasks:
    - Add `truncate` and `max-w-[X]` on name/email lines.
    - Ensure amounts and status chips stay right‑aligned without forcing container overflow.

## Partners Dashboard (`resources/views/admin/dashboards/partners.blade.php`)

- [x] **Align statistics cards with global responsive pattern**
  - Similar 4‑card grid as other dashboards; apply the same font‑size and wrapping adjustments as above.
  - Ensure Y‑axis labels and long text don’t overlap icons.

- [x] **Check charts row responsiveness**
  - Two side‑by‑side charts in `grid grid-cols-1 lg:grid-cols-2` for partner types and commissions.
  - As with other charts, confirm that on small screens they stack cleanly and don’t cause nested scrollbars.

- [x] **Monthly performance table horizontal scrolling**
  - `table` is wrapped in `overflow-x-auto`, which is good for small screens.
  - Tasks:
    - Confirm no fixed widths or long labels push beyond viewport without scroll.
    - Add `text-xs sm:text-sm` for table cells so that 4 columns fit reasonably on small devices.

## Coin & Affiliate Dashboards (JS‑driven)

- [x] **Ensure Coin dashboard views use responsive CSS classes**
  - `public/js/coin-dashboard.js` injects HTML strings for transaction items, achievements, packages, and notifications.
  - These fragments use classes like `flex items-center justify-between` and `space-x-3` that may not adapt well on small screens.
  - Tasks:
    - Review the Blade templates for the coin dashboard (`resources/views/user/coins/*.blade.php`) and update them with responsive wrappers and `min-w-0` on flex children.
    - Update JS‑generated HTML snippets to mirror responsive Tailwind patterns (allow wrapping of description and breakpoint‑aware spacing).

- [x] **Validate admin‑side JS dashboards for layout assumptions**
  - `public/js/admin-dashboard.js` and `public/js/admin-affiliate-dashboard.js` update existing DOM nodes (`#total-users`, `#total-commissions`, `#recent-activity`, `#top-performers`, etc.).
  - These scripts assume certain IDs and layout but do not account for responsive behavior directly.
  - Tasks:
    - Ensure the Blade markup for the affiliate/admin dashboards around these IDs is using responsive grid/flex layouts and is mobile‑safe.
    - Avoid JS injecting content that assumes fixed width (e.g. lots of inline text with no truncation) into narrow containers.

## Cross‑Cutting Tasks & QA

- [x] **Add a responsive preview checklist for all dashboard routes**
  - For `admin.dashboard`, `admin.dashboards.stories`, `admin.dashboards.sales`, `admin.dashboards.partners`, `admin.affiliate.dashboard`, user coin dashboard:
    - ✅ Small phone (~360px): no horizontal scroll, cards stack cleanly, headers/filters readable.
    - ✅ Medium phone (~414px): quick actions and stats grids remain legible; no clipped text or icons.
    - ✅ Small tablet (~768px): 2‑column grids feel comfortable; charts and tables don’t create nested scroll.
    - ✅ Desktop: multi‑column layouts (3–4 cols) stay balanced with truncation where needed.
  - Any newly observed overflow issues should be logged as follow‑up bugs outside this checklist.

- [x] **Standardize RTL‑friendly responsive utilities**
  - Some components use `space-x-*` with `space-x-reverse`; confirm this does not introduce layout anomalies at breakpoints.
  - Where necessary, switch to `gap-*` for grid/flex containers to avoid RTL reversal quirks on very small screens.

- [x] **Document responsive design guidelines for future admin pages**
  - Extend `ADMIN_DASHBOARD_UI.md` with concrete responsive patterns:
    - Standard grid breakpoints for analytics pages.
    - Preferred patterns for charts, tables, and KPI cards.
    - Examples of mobile‑first markup for new admin screens.


