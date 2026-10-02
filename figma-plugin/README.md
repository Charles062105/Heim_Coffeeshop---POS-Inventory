# Heim Design System — Figma Plugin

A professional Figma Development Plugin that exposes all design tokens, production UI components, and complete 1440×900 screen scaffolds from the Heim Coffee Shop POS system directly inside Figma.

---

## 📂 Files

| File | Purpose |
|---|---|
| `manifest.json` | Figma Plugin Manifest v2 — defines plugin metadata, capabilities, and permissions |
| `code.js` | Plugin sandbox engine — renders all Figma vectors, autolayouts, frames, and typography safely |
| `ui.html` | Interactive 5-tab UI panel rendered in the Figma desktop plugin drawer |

---

## 🚀 How to Load in Figma Desktop

> **Important:** Local development plugins run in the **Figma Desktop App** (Windows / macOS).

### Step-by-Step

1. Open the **Figma Desktop App**.
2. Navigate to: **Menu (☰)** → **Plugins** → **Development** → **Import plugin from manifest...**
3. Select: `c:\xampp\htdocs\coffee-shop-system\figma-plugin\manifest.json`
4. Run the plugin: **Menu (☰)** → **Plugins** → **Development** → **Heim Design System**
5. The dockable plugin panel opens at **480×680px**.

---

## 🎨 What's Inside

### 1. Colors Tab
- Full **Heim brand palette** (`heim-50` → `heim-950`) — 11 curated green shades
- **Brand aliases** (`brand`, `brand-dark`, `brand-light`, `brand-surface`)
- **Semantic indicators** (Success, Error, Warning, Info, Badge, Online)
- **Gray neutral scale** (8 shades from `#f9fafb` to `#111827`)
- Click **Fill** → sets fill on selected layer
- Click **Stroke** → sets stroke on selected layer
- Click **Copy** → copies hex string to clipboard
- **Generate Color Swatch Sheet** → generates a Figma specimen sheet with all swatches and hex codes

### 2. Typography Tab
- 10 type styles: Display (32px), H1 Page (24px), H2 Section (18px), H3 Card (16px), Body (14px), Body Medium (14px), Caption (12px), Label (11px), Stat Value (28px), Mono Code (12px)
- Font hierarchy: **Manrope** primary, **Inter** fallback, **Roboto Mono** numeric & code
- Resilient font loader with auto-fallback to ensure zero font-missing crashes
- **Generate Typography Sheet** → generates a stacked specimen frame

### 3. Components Tab
Ready-to-use Figma frames crafted to 1:1 parity with the live Blade templates:

| Category | Component | Description |
|---|---|---|
| **POS & Checkout** | **POS Product Card** | Menu tile with image placeholder, category tag, size variants, price |
| **POS & Checkout** | **Thermal Receipt** | Complete thermal paper receipt with store header, itemization, VAT, cash, change, barcode |
| **POS & Checkout** | **Order Cart Panel** | Order summary with line items, quantity steppers, subtotal, discount, VAT, checkout CTA |
| **POS & Checkout** | **Payment Modal** | Fast tender modal with quick cash buttons (₱500, ₱1,000, ₱2,000), GCash, Card, change callout |
| **POS & Checkout** | **Item Customizer** | Product customizer modal with size radio pills, milk selector, add-on checkboxes |
| **POS & Checkout** | **Category Tabs** | Horizontal pill filter bar with active states |
| **Analytics** | **Stat Card** | KPI metric card with icon badge, metric value, subtext |
| **Analytics** | **KPI Trend Card** | Executive metric card with percentage trend indicator (+18.4% vs last week) |
| **Navigation** | **Sidebar Nav** | Full 4-tier dark navigation sidebar (`#0c352a`) with active indicator |
| **Navigation** | **Notif Badge** | Bell icon with red notification counter dot |
| **Modals & Forms** | **Auth Modal** | Manager or owner authorization dialog with authorizer email, password, and reason fields |
| **Modals & Forms** | **Stock Movement Modal** | Stock-In delivery and spoilage adjustment modal |
| **Modals & Forms** | **Role Badges** | Owner, Manager, and Cashier pills |
| **Feedback** | **Flash Messages** | Success (`#16a34a`), Error (`#dc2626`), and Warning (`#f59e0b`) alert toasts |
| **Data & Tables** | **Data Table** | Header row + data rows with status badges and currency alignment |

### 4. Spacing & Radius Tab
- Pixel scale: `4 | 8 | 12 | 16 | 20 | 24 | 32 | 40 | 48 | 64`
- Border radius tokens: `xs (4px)`, `sm (8px)`, `md (12px)`, `xl (16px)`, `2xl (24px)`, `pill (9999px)`
- **Generate Sheet** → drops a combined token reference frame

### 5. Screens Tab (Full 1440×900 Scaffolds)
Full-viewport wireframes for the complete POS ecosystem:
1. **📊 Dashboard**: Top KPIs, 7-day sales line/bar chart, top selling menu items, and recent orders table.
2. **🛒 POS Terminal**: Multi-category filter bar, product grid tiles, and active order cart panel.
3. **📋 Orders Management**: Search bar, status dropdown, date range filters, and orders ledger table with action links.
4. **📦 Inventory Overview**: Stock health status cards, Quick action toolbar (+ Stock-In, Record Waste, Adjust Stock), and raw ingredients table.
5. **⚖️ Daily Consumption**: Target audit date picker, KPI summary (Settled Orders, Cups Dispensed), and Opening + In − Sales − Waste ± Adj = Closing movement ledger.
6. **📈 Sales & Analytics**: Daily/Weekly/Monthly range pills, revenue chart, payment method breakdown (Cash/GCash/Card), and cashier rankings.
7. **🥣 Recipes & BOM**: Product variant picker, recipe ingredients bill of materials, portions, and cost breakdown.
8. **👥 Staff & Access**: Role-based access control matrix, staff member cards, and active toggle controls.

---

## 🎨 Design Tokens Reference

### Primary Brand Colors
```
#155d49  →  heim-700 (Brand Primary)
#0c352a  →  heim-900 (Sidebar Navigation Background)
#f4faf7  →  heim-50  (Brand Surface / Subtle Tint)
#dcf0e9  →  heim-100 (Badge Backgrounds)
```

### Typography
- **Primary Display & Headings**: Manrope (SemiBold, Bold, ExtraBold)
- **Body & Labels**: Manrope / Inter (Regular, Medium, SemiBold)
- **Financial & Code**: Roboto Mono (Medium, Bold)

### Corner Radius
```
xs: 4px | sm: 8px | md: 12px | xl: 16px | 2xl: 24px | pill: 9999px
```
