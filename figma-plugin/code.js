// ═══════════════════════════════════════════════════════════════════════════
// HEIM DESIGN SYSTEM — Figma Plugin Sandbox (code.js)
// Enterprise-Grade POS & Inventory Management Design System for Figma
// ═══════════════════════════════════════════════════════════════════════════

figma.showUI(__html__, { width: 480, height: 680, title: 'Heim Design System' });

// ─── Core Utilities ─────────────────────────────────────────────────────────

/** Convert hex string ("#RGB", "#RRGGBB") to Figma {r,g,b} (0-1 range) */
function hexToRgb(hex) {
  let h = String(hex).replace('#', '').trim();
  if (h.length === 3) {
    h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
  }
  return {
    r: parseInt(h.substring(0, 2), 16) / 255 || 0,
    g: parseInt(h.substring(2, 4), 16) / 255 || 0,
    b: parseInt(h.substring(4, 6), 16) / 255 || 0,
  };
}

/** Solid paint from hex and opacity */
function solidPaint(hex, opacity = 1) {
  return [{ type: 'SOLID', color: hexToRgb(hex), opacity: Math.max(0, Math.min(1, opacity)) }];
}

/** Center node in viewport */
function centerInViewport(node) {
  const vp = figma.viewport.center;
  node.x = Math.round(vp.x - node.width / 2);
  node.y = Math.round(vp.y - node.height / 2);
}

/** Add node to page, select it, and scroll into view */
function addToPage(node) {
  figma.currentPage.appendChild(node);
  figma.currentPage.selection = [node];
  figma.viewport.scrollAndZoomIntoView([node]);
}

/** Resilient font loader with multi-step fallback to guarantee execution */
async function loadFont(family, style = 'Regular') {
  const stylesToTry = [style];
  if (style === 'ExtraBold') stylesToTry.push('Bold');
  if (style === 'SemiBold') stylesToTry.push('Bold');
  if (style === 'Medium') stylesToTry.push('Regular');
  stylesToTry.push('Regular');

  for (const s of stylesToTry) {
    try {
      await figma.loadFontAsync({ family, style: s });
      return { family, style: s };
    } catch (_) {}
  }

  for (const s of ['Bold', 'SemiBold', 'Medium', 'Regular']) {
    try {
      await figma.loadFontAsync({ family: 'Inter', style: s });
      return { family: 'Inter', style: s };
    } catch (_) {}
  }

  await figma.loadFontAsync({ family: 'Inter', style: 'Regular' });
  return { family: 'Inter', style: 'Regular' };
}

/** Helper to instantiate a configured text node safely */
async function createTextNode({
  text,
  size = 14,
  family = 'Manrope',
  style = 'Regular',
  color = '#111827',
  opacity = 1,
  x = 0,
  y = 0,
  width,
  height,
  align = 'LEFT',
  letterSpacing = 0,
  lineHeight,
}) {
  const fontName = await loadFont(family, style);
  const node = figma.createText();
  node.fontName = fontName;
  node.fontSize = size;
  node.characters = String(text);
  node.fills = solidPaint(color, opacity);
  node.x = x;
  node.y = y;
  if (letterSpacing) node.letterSpacing = { value: letterSpacing, unit: 'PIXELS' };
  if (lineHeight) node.lineHeight = { value: lineHeight, unit: 'PIXELS' };
  if (align) node.textAlignHorizontal = align;
  if (width !== undefined) {
    node.resize(width, height || Math.round(size * 1.4));
  }
  return node;
}

// ─── Design Tokens ──────────────────────────────────────────────────────────

const COLORS = {
  // Heim Brand Scale
  'heim-50':  '#f0f8f5',
  'heim-100': '#dcf0e9',
  'heim-200': '#bee1d5',
  'heim-300': '#93cbb9',
  'heim-400': '#64b09a',
  'heim-500': '#3f947e',
  'heim-600': '#2c7865',
  'heim-700': '#155d49',
  'heim-800': '#114a3b',
  'heim-900': '#0c352a',
  'heim-950': '#061f19',

  // Brand Aliases
  'brand':         '#155d49',
  'brand-dark':    '#0f4435',
  'brand-light':   '#2c7865',
  'brand-surface': '#f4faf7',

  // Semantic Feedback
  'ui-success':     '#16a34a',
  'ui-error':       '#dc2626',
  'ui-warning':     '#f59e0b',
  'ui-info':        '#3b82f6',
  'ui-notif-badge': '#ef4444',
  'ui-online':      '#10b981',

  // Neutrals
  'gray-50':  '#f9fafb',
  'gray-100': '#f3f4f6',
  'gray-200': '#e5e7eb',
  'gray-300': '#d1d5db',
  'gray-400': '#9ca3af',
  'gray-500': '#6b7280',
  'gray-700': '#374151',
  'gray-800': '#1f2937',
  'gray-900': '#111827',
};

const TEXT_STYLES = [
  { name: 'Display',    family: 'Manrope', style: 'ExtraBold', size: 32, lineHeight: 40 },
  { name: 'H1 Page',    family: 'Manrope', style: 'Bold',      size: 24, lineHeight: 32 },
  { name: 'H2 Section', family: 'Manrope', style: 'SemiBold',  size: 18, lineHeight: 28 },
  { name: 'H3 Card',    family: 'Manrope', style: 'SemiBold',  size: 16, lineHeight: 24 },
  { name: 'Body',       family: 'Manrope', style: 'Regular',   size: 14, lineHeight: 22 },
  { name: 'Body Medium',family: 'Manrope', style: 'Medium',    size: 14, lineHeight: 22 },
  { name: 'Caption',    family: 'Manrope', style: 'Medium',    size: 12, lineHeight: 18 },
  { name: 'Label',      family: 'Manrope', style: 'SemiBold',  size: 11, lineHeight: 16 },
  { name: 'Stat Value', family: 'Manrope', style: 'ExtraBold', size: 28, lineHeight: 36 },
  { name: 'Mono Code',  family: 'Roboto Mono', style: 'Regular', size: 12, lineHeight: 18 },
];

const SPACING = [4, 8, 12, 16, 20, 24, 32, 40, 48, 64];
const RADII   = [
  { name: 'xs (4px)',    value: 4 },
  { name: 'sm (8px)',    value: 8 },
  { name: 'md (12px)',   value: 12 },
  { name: 'xl (16px)',   value: 16 },
  { name: '2xl (24px)',  value: 24 },
  { name: 'pill (9999px)', value: 9999 },
];

// ─── Token Application to Selection ─────────────────────────────────────────

async function applyColorToSelection({ hex, target }) {
  const nodes = figma.currentPage.selection;
  if (!nodes.length) {
    figma.notify('⚠️ Select a layer first', { error: true });
    return;
  }
  for (const node of nodes) {
    if (target === 'fill' && 'fills' in node) {
      node.fills = solidPaint(hex);
    } else if (target === 'stroke' && 'strokes' in node) {
      node.strokes = solidPaint(hex);
    } else if ('fills' in node) {
      node.fills = solidPaint(hex);
    }
  }
  figma.notify(`✅ Applied ${hex}`);
}

async function applyTypographyToSelection(style) {
  const nodes = figma.currentPage.selection.filter(n => n.type === 'TEXT');
  if (!nodes.length) {
    figma.notify('⚠️ Select a text layer first', { error: true });
    return;
  }
  const loaded = await loadFont(style.family, style.style);
  for (const node of nodes) {
    await figma.loadFontAsync(node.fontName);
    node.fontName    = loaded;
    node.fontSize    = style.size;
    node.lineHeight  = { value: style.lineHeight, unit: 'PIXELS' };
  }
  figma.notify(`✅ Applied ${style.name}`);
}

// ─── Design Specimen Sheets ──────────────────────────────────────────────────

async function createColorSwatchSheet() {
  const frame = figma.createFrame();
  frame.name = 'Heim / Color Tokens';
  frame.resize(900, 520);
  frame.fills = solidPaint('#ffffff');
  frame.cornerRadius = 16;

  const entries = Object.entries(COLORS);
  const cols = 9;
  const swatchSize = 78;
  const gap = 14;
  const padX = 32;
  const padY = 40;

  for (let i = 0; i < entries.length; i++) {
    const [name, hex] = entries[i];
    const col = i % cols;
    const row = Math.floor(i / cols);

    const swatch = figma.createRectangle();
    swatch.name = name;
    swatch.resize(swatchSize, swatchSize);
    swatch.x = padX + col * (swatchSize + gap);
    swatch.y = padY + row * (swatchSize + 32 + gap);
    swatch.fills = solidPaint(hex);
    swatch.cornerRadius = 12;
    swatch.strokes = solidPaint('#e5e7eb');
    swatch.strokeWeight = 1;
    frame.appendChild(swatch);

    const label = await createTextNode({
      text: name,
      size: 10,
      family: 'Inter',
      style: 'Regular',
      color: '#4b5563',
      x: swatch.x,
      y: swatch.y + swatchSize + 4,
      width: swatchSize,
      align: 'CENTER',
    });
    frame.appendChild(label);

    const hexLabel = await createTextNode({
      text: hex,
      size: 9,
      family: 'Roboto Mono',
      style: 'Regular',
      color: '#9ca3af',
      x: swatch.x,
      y: swatch.y + swatchSize + 18,
      width: swatchSize,
      align: 'CENTER',
    });
    frame.appendChild(hexLabel);
  }

  const rows = Math.ceil(entries.length / cols);
  frame.resize(padX * 2 + cols * swatchSize + (cols - 1) * gap, padY + rows * (swatchSize + 32 + gap) + 24);
  centerInViewport(frame);
  addToPage(frame);
  figma.notify('✅ Color Swatch Sheet created!');
}

async function createTypographySheet() {
  const frame = figma.createFrame();
  frame.name = 'Heim / Typography Scale';
  frame.resize(680, 100);
  frame.fills = solidPaint('#ffffff');
  frame.cornerRadius = 16;

  const padX = 36;
  let y = 36;

  for (const style of TEXT_STYLES) {
    const sample = await createTextNode({
      text: `${style.name} — Crafting perfection in every cup`,
      size: style.size,
      family: style.family,
      style: style.style,
      color: '#155d49',
      x: padX,
      y,
      width: 600,
      lineHeight: style.lineHeight,
    });
    frame.appendChild(sample);

    const meta = await createTextNode({
      text: `${style.family} ${style.style} · ${style.size}px / ${style.lineHeight}px line-height`,
      size: 11,
      family: 'Inter',
      style: 'Regular',
      color: '#9ca3af',
      x: padX,
      y: y + style.lineHeight + 4,
      width: 600,
    });
    frame.appendChild(meta);

    y += style.lineHeight + 20 + 20;
  }

  frame.resize(680, y + 24);
  centerInViewport(frame);
  addToPage(frame);
  figma.notify('✅ Typography Sheet created!');
}

async function createSpacingSheet() {
  const frame = figma.createFrame();
  frame.name = 'Heim / Spacing & Radius Tokens';
  frame.fills = solidPaint('#ffffff');
  frame.cornerRadius = 16;
  frame.resize(840, 520);

  const sTitle = await createTextNode({
    text: 'SPACING SCALE',
    size: 13,
    family: 'Inter',
    style: 'Bold',
    color: '#155d49',
    letterSpacing: 1.2,
    x: 36,
    y: 36,
    width: 400,
  });
  frame.appendChild(sTitle);

  let x = 36;
  for (const sp of SPACING) {
    const rect = figma.createRectangle();
    rect.name = `spacing-${sp}`;
    rect.resize(sp, sp);
    rect.x = x;
    rect.y = 76;
    rect.fills = solidPaint('#155d49');
    rect.cornerRadius = 4;
    frame.appendChild(rect);

    const lbl = await createTextNode({
      text: `${sp}px`,
      size: 10,
      family: 'Roboto Mono',
      style: 'Regular',
      color: '#6b7280',
      x,
      y: 76 + sp + 6,
      width: Math.max(sp, 28),
      align: 'CENTER',
    });
    frame.appendChild(lbl);

    x += Math.max(sp, 28) + 14;
  }

  const rTitle = await createTextNode({
    text: 'BORDER RADIUS SCALE',
    size: 13,
    family: 'Inter',
    style: 'Bold',
    color: '#155d49',
    letterSpacing: 1.2,
    x: 36,
    y: 260,
    width: 400,
  });
  frame.appendChild(rTitle);

  x = 36;
  for (const r of RADII) {
    const rect = figma.createRectangle();
    rect.name = r.name;
    rect.resize(84, 84);
    rect.x = x;
    rect.y = 300;
    rect.cornerRadius = Math.min(r.value, 42);
    rect.fills = solidPaint('#dcf0e9');
    rect.strokes = solidPaint('#155d49');
    rect.strokeWeight = 2;
    frame.appendChild(rect);

    const lbl = await createTextNode({
      text: r.name,
      size: 10,
      family: 'Inter',
      style: 'Regular',
      color: '#4b5563',
      x,
      y: 396,
      width: 84,
      align: 'CENTER',
    });
    frame.appendChild(lbl);

    x += 102;
  }

  frame.resize(Math.max(x + 36, 840), 520);
  centerInViewport(frame);
  addToPage(frame);
  figma.notify('✅ Spacing & Radius sheet created!');
}

// ─── Component: Stat Card ────────────────────────────────────────────────────

async function createStatCard({ label = "Today's Net Sales", value = '₱12,450.00', sub = '47 orders completed', iconColor = '#155d49', iconBg = '#f4faf7' } = {}) {
  const card = figma.createFrame();
  card.name = `Stat Card / ${label}`;
  card.resize(280, 114);
  card.fills = solidPaint('#ffffff');
  card.cornerRadius = 16;
  card.strokes = solidPaint('#e5e7eb');
  card.strokeWeight = 1;

  const badge = figma.createFrame();
  badge.resize(46, 46);
  badge.cornerRadius = 12;
  badge.fills = solidPaint(iconBg);
  badge.strokes = solidPaint('#e5e7eb');
  badge.strokeWeight = 1;
  badge.x = card.width - 46 - 16;
  badge.y = (card.height - 46) / 2;
  card.appendChild(badge);

  const iconDot = figma.createRectangle();
  iconDot.resize(20, 20);
  iconDot.x = 13; iconDot.y = 13;
  iconDot.cornerRadius = 4;
  iconDot.fills = solidPaint(iconColor);
  badge.appendChild(iconDot);

  card.appendChild(await createTextNode({
    text: label.toUpperCase(),
    size: 11,
    family: 'Manrope',
    style: 'Bold',
    color: '#9ca3af',
    letterSpacing: 0.8,
    x: 18,
    y: 18,
    width: card.width - 80,
  }));

  card.appendChild(await createTextNode({
    text: value,
    size: 24,
    family: 'Manrope',
    style: 'ExtraBold',
    color: '#111827',
    x: 18,
    y: 40,
    width: card.width - 80,
  }));

  card.appendChild(await createTextNode({
    text: sub,
    size: 12,
    family: 'Manrope',
    style: 'Medium',
    color: '#155d49',
    x: 18,
    y: 80,
    width: card.width - 80,
  }));

  centerInViewport(card);
  addToPage(card);
  figma.notify('✅ Stat Card inserted!');
}

// ─── Component: KPI Card with Trend Delta ────────────────────────────────────

async function createKpiCard({ label = "Today's Gross Sales", value = '₱24,850.00', delta = '+18.4%', isPositive = true } = {}) {
  const card = figma.createFrame();
  card.name = 'Component / KPI Trend Card';
  card.resize(280, 130);
  card.fills = solidPaint('#ffffff');
  card.cornerRadius = 16;
  card.strokes = solidPaint('#e5e7eb');
  card.strokeWeight = 1;

  card.appendChild(await createTextNode({
    text: label.toUpperCase(),
    size: 11,
    family: 'Manrope',
    style: 'Bold',
    color: '#6b7280',
    letterSpacing: 0.8,
    x: 18, y: 18, width: 240,
  }));

  card.appendChild(await createTextNode({
    text: value,
    size: 26,
    family: 'Manrope',
    style: 'ExtraBold',
    color: '#111827',
    x: 18, y: 44, width: 240,
  }));

  const pill = figma.createFrame();
  pill.resize(110, 24);
  pill.x = 18; pill.y = 86;
  pill.cornerRadius = 9999;
  pill.fills = solidPaint(isPositive ? '#dcfce7' : '#fee2e2');

  pill.appendChild(await createTextNode({
    text: `${isPositive ? '↑' : '↓'} ${delta} vs lw`,
    size: 11,
    family: 'Manrope',
    style: 'Bold',
    color: isPositive ? '#15803d' : '#b91c1c',
    x: 0, y: 4, width: 110, align: 'CENTER',
  }));
  card.appendChild(pill);

  centerInViewport(card);
  addToPage(card);
  figma.notify('✅ KPI Trend Card inserted!');
}

// ─── Component: Sidebar Nav ──────────────────────────────────────────────────

async function createSidebarNav() {
  const sidebar = figma.createFrame();
  sidebar.name = 'Component / Sidebar Nav';
  sidebar.resize(256, 760);
  sidebar.fills = solidPaint('#0c352a');

  // Brand Logo Area
  const logoArea = figma.createFrame();
  logoArea.resize(256, 64);
  logoArea.fills = [];
  logoArea.strokes = solidPaint('#114a3b');
  logoArea.strokeWeight = 1;

  logoArea.appendChild(await createTextNode({
    text: 'Heim', size: 22, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 20, y: 16,
  }));
  logoArea.appendChild(await createTextNode({
    text: 'COFFEE SHOP POS', size: 9, family: 'Manrope', style: 'Bold', color: '#3f947e', letterSpacing: 2, x: 20, y: 42,
  }));
  sidebar.appendChild(logoArea);

  const navGroups = [
    { section: 'MAIN', items: [{ label: 'Dashboard', active: false }, { label: 'Point of Sale', active: true }, { label: 'Orders', active: false }, { label: 'Refunds', active: false }] },
    { section: 'MENU', items: [{ label: 'Products', active: false }, { label: 'Recipes', active: false }, { label: 'Categories', active: false }] },
    { section: 'INVENTORY', items: [{ label: 'Stock Overview', active: false }, { label: 'Ingredients', active: false }, { label: 'Stock-In', active: false }, { label: 'Adjustments', active: false }, { label: 'Waste / Spoilage', active: false }] },
    { section: 'SYSTEM', items: [{ label: 'Notifications', active: false }, { label: 'Audit Logs', active: false }, { label: 'Users', active: false }] },
  ];

  let y = 74;
  const padX = 12;

  for (const group of navGroups) {
    sidebar.appendChild(await createTextNode({
      text: group.section, size: 10, family: 'Manrope', style: 'Bold', color: '#3f947e', letterSpacing: 1.5, x: padX + 10, y: y + 6, width: 220,
    }));
    y += 28;

    for (const item of group.items) {
      const navItem = figma.createFrame();
      navItem.name = `Nav / ${item.label}`;
      navItem.resize(232, 38);
      navItem.x = padX;
      navItem.y = y;
      navItem.cornerRadius = 10;
      navItem.fills = item.active ? solidPaint('#15803d') : [];

      const dot = figma.createRectangle();
      dot.resize(14, 14);
      dot.x = 12; dot.y = 12;
      dot.cornerRadius = 4;
      dot.fills = solidPaint(item.active ? '#ffffff' : '#93cbb9');
      navItem.appendChild(dot);

      navItem.appendChild(await createTextNode({
        text: item.label,
        size: 13,
        family: 'Manrope',
        style: item.active ? 'Bold' : 'Medium',
        color: item.active ? '#ffffff' : '#b0c4be',
        x: 36, y: 9, width: 170,
      }));

      sidebar.appendChild(navItem);
      y += 40;
    }
    y += 8;
  }

  sidebar.resize(256, Math.max(y + 20, 760));
  centerInViewport(sidebar);
  addToPage(sidebar);
  figma.notify('✅ Sidebar Nav created!');
}

// ─── Component: POS Product Card ────────────────────────────────────────────

async function createPosProductCard() {
  const card = figma.createFrame();
  card.name = 'Component / POS Product Card';
  card.resize(164, 206);
  card.fills = solidPaint('#ffffff');
  card.cornerRadius = 16;
  card.strokes = solidPaint('#e5e7eb');
  card.strokeWeight = 1;

  const imgArea = figma.createRectangle();
  imgArea.resize(164, 114);
  imgArea.fills = solidPaint('#f4faf7');
  card.appendChild(imgArea);

  card.appendChild(await createTextNode({
    text: '☕ Product Photo', size: 11, family: 'Manrope', style: 'Medium', color: '#64b09a', x: 0, y: 48, width: 164, align: 'CENTER',
  }));

  const catTag = figma.createFrame();
  catTag.resize(64, 20);
  catTag.x = 10; catTag.y = 10;
  catTag.cornerRadius = 9999;
  catTag.fills = solidPaint('#155d49');

  catTag.appendChild(await createTextNode({
    text: 'COFFEE', size: 9, family: 'Manrope', style: 'Bold', color: '#ffffff', letterSpacing: 0.8, x: 0, y: 4, width: 64, align: 'CENTER',
  }));
  card.appendChild(catTag);

  card.appendChild(await createTextNode({
    text: 'Caramel Macchiato', size: 13, family: 'Manrope', style: 'Bold', color: '#111827', x: 12, y: 124, width: 140,
  }));

  card.appendChild(await createTextNode({
    text: 'Regular · Medium · Large', size: 11, family: 'Manrope', style: 'Regular', color: '#6b7280', x: 12, y: 146, width: 140,
  }));

  card.appendChild(await createTextNode({
    text: 'from ₱120.00', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 12, y: 172, width: 140,
  }));

  centerInViewport(card);
  addToPage(card);
  figma.notify('✅ POS Product Card inserted!');
}

// ─── Component: Thermal Print Receipt ────────────────────────────────────────

async function createReceiptComponent() {
  const receipt = figma.createFrame();
  receipt.name = 'Component / Thermal Receipt';
  receipt.resize(320, 520);
  receipt.fills = solidPaint('#ffffff');
  receipt.cornerRadius = 8;
  receipt.strokes = solidPaint('#e5e7eb');
  receipt.strokeWeight = 1;

  let y = 20;

  receipt.appendChild(await createTextNode({
    text: 'HEIM COFFEE', size: 16, family: 'Manrope', style: 'Bold', color: '#111827', letterSpacing: 1.5, x: 0, y, width: 320, align: 'CENTER',
  }));
  y += 24;

  receipt.appendChild(await createTextNode({
    text: 'Store #01 — Bonifacio Uptown\nTIN: 420-911-000-001\nVAT REG TIN',
    size: 10, family: 'Roboto Mono', style: 'Regular', color: '#6b7280', x: 0, y, width: 320, align: 'CENTER', lineHeight: 14,
  }));
  y += 48;

  // Divider
  const div1 = figma.createLine();
  div1.resize(280, 0);
  div1.x = 20; div1.y = y;
  div1.strokes = solidPaint('#9ca3af');
  div1.strokeWeight = 1;
  receipt.appendChild(div1);
  y += 12;

  receipt.appendChild(await createTextNode({
    text: 'ORDER: #ORD-20260916-0042\nDATE: Sep 16, 2026 09:42 AM\nCASHIER: Maria Santos',
    size: 10, family: 'Roboto Mono', style: 'Regular', color: '#374151', x: 20, y, width: 280, lineHeight: 15,
  }));
  y += 50;

  const div2 = figma.createLine();
  div2.resize(280, 0);
  div2.x = 20; div2.y = y;
  div2.strokes = solidPaint('#9ca3af');
  receipt.appendChild(div2);
  y += 12;

  // Items
  const items = [
    { qty: '1', name: 'Caramel Macchiato (M)', price: '₱140.00', sub: '+ Oat Milk (₱35)' },
    { qty: '2', name: 'Spanish Latte (R)', price: '₱220.00', sub: '+ Extra Espresso (₱30)' },
    { qty: '1', name: 'Butter Croissant', price: '₱85.00', sub: '' },
  ];

  for (const it of items) {
    receipt.appendChild(await createTextNode({
      text: `${it.qty}x ${it.name}`, size: 11, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y, width: 200,
    }));
    receipt.appendChild(await createTextNode({
      text: it.price, size: 11, family: 'Roboto Mono', style: 'Bold', color: '#111827', x: 220, y, width: 80, align: 'RIGHT',
    }));
    y += 16;
    if (it.sub) {
      receipt.appendChild(await createTextNode({
        text: `    ${it.sub}`, size: 9, family: 'Inter', style: 'Regular', color: '#6b7280', x: 20, y, width: 200,
      }));
      y += 14;
    }
  }

  y += 8;
  const div3 = figma.createLine();
  div3.resize(280, 0);
  div3.x = 20; div3.y = y;
  div3.strokes = solidPaint('#9ca3af');
  receipt.appendChild(div3);
  y += 12;

  // Totals
  const totals = [
    { label: 'Subtotal', val: '₱445.00', bold: false },
    { label: 'PWD/Senior 20% Disc', val: '-₱40.00', bold: false, color: '#dc2626' },
    { label: 'VAT 12% (Inclusive)', val: '₱43.39', bold: false },
    { label: 'NET TOTAL DUE', val: '₱405.00', bold: true, color: '#155d49' },
    { label: 'Cash Tendered', val: '₱500.00', bold: false },
    { label: 'CHANGE', val: '₱95.00', bold: true },
  ];

  for (const row of totals) {
    receipt.appendChild(await createTextNode({
      text: row.label, size: row.bold ? 12 : 10, family: 'Manrope', style: row.bold ? 'Bold' : 'Regular', color: row.color || '#374151', x: 20, y, width: 180,
    }));
    receipt.appendChild(await createTextNode({
      text: row.val, size: row.bold ? 13 : 10, family: 'Roboto Mono', style: row.bold ? 'Bold' : 'Regular', color: row.color || '#111827', x: 200, y, width: 100, align: 'RIGHT',
    }));
    y += row.bold ? 22 : 16;
  }

  y += 10;
  receipt.appendChild(await createTextNode({
    text: 'THANK YOU FOR BREWING WITH HEIM!\nWiFi: Heim_Guest · Pass: CoffeeLove2026\nOfficial Receipt for POS Inspection',
    size: 9, family: 'Inter', style: 'Medium', color: '#9ca3af', x: 0, y, width: 320, align: 'CENTER', lineHeight: 13,
  }));

  receipt.resize(320, y + 36);
  centerInViewport(receipt);
  addToPage(receipt);
  figma.notify('✅ Thermal Receipt Component created!');
}

// ─── Component: POS Order Cart Panel ────────────────────────────────────────

async function createOrderCartComponent() {
  const cart = figma.createFrame();
  cart.name = 'Component / POS Cart Panel';
  cart.resize(360, 680);
  cart.fills = solidPaint('#ffffff');
  cart.cornerRadius = 20;
  cart.strokes = solidPaint('#e5e7eb');
  cart.strokeWeight = 1;

  // Header
  cart.appendChild(await createTextNode({
    text: 'Current Order', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 200,
  }));
  cart.appendChild(await createTextNode({
    text: '#ORD-0042 · Dine In', size: 11, family: 'Manrope', style: 'SemiBold', color: '#155d49', x: 20, y: 44, width: 200,
  }));

  // Clear button
  const clearBtn = figma.createFrame();
  clearBtn.resize(60, 26);
  clearBtn.cornerRadius = 8;
  clearBtn.fills = solidPaint('#fee2e2');
  clearBtn.x = cart.width - 20 - 60; clearBtn.y = 20;
  clearBtn.appendChild(await createTextNode({
    text: 'Clear', size: 11, family: 'Manrope', style: 'Bold', color: '#dc2626', x: 0, y: 5, width: 60, align: 'CENTER',
  }));
  cart.appendChild(clearBtn);

  // Divider
  const div = figma.createLine();
  div.resize(320, 0);
  div.x = 20; div.y = 72;
  div.strokes = solidPaint('#f3f4f6');
  cart.appendChild(div);

  // Cart line items
  let y = 84;
  const items = [
    { name: 'Spanish Latte', size: 'Large', price: '₱160.00', qty: 2, addons: ['+ Extra Shot', '+ Oat Milk'] },
    { name: 'Matcha Espresso', size: 'Medium', price: '₱145.00', qty: 1, addons: [] },
    { name: 'Butter Croissant', size: 'Regular', price: '₱85.00', qty: 1, addons: [] },
  ];

  for (const it of items) {
    const itemCard = figma.createFrame();
    itemCard.resize(320, it.addons.length > 0 ? 84 : 64);
    itemCard.x = 20; itemCard.y = y;
    itemCard.cornerRadius = 12;
    itemCard.fills = solidPaint('#f9fafb');
    itemCard.strokes = solidPaint('#f3f4f6');
    itemCard.strokeWeight = 1;

    itemCard.appendChild(await createTextNode({
      text: it.name, size: 13, family: 'Manrope', style: 'Bold', color: '#111827', x: 12, y: 10, width: 180,
    }));
    itemCard.appendChild(await createTextNode({
      text: `${it.size} · ${it.price}`, size: 11, family: 'Manrope', style: 'Medium', color: '#6b7280', x: 12, y: 28, width: 180,
    }));

    if (it.addons.length > 0) {
      itemCard.appendChild(await createTextNode({
        text: it.addons.join(' · '), size: 10, family: 'Manrope', style: 'Medium', color: '#155d49', x: 12, y: 46, width: 180,
      }));
    }

    // Stepper (- 2 +)
    const stepper = figma.createFrame();
    stepper.resize(80, 30);
    stepper.x = itemCard.width - 80 - 12;
    stepper.y = (itemCard.height - 30) / 2;
    stepper.cornerRadius = 8;
    stepper.fills = solidPaint('#ffffff');
    stepper.strokes = solidPaint('#e5e7eb');
    stepper.strokeWeight = 1;

    stepper.appendChild(await createTextNode({
      text: '−', size: 14, family: 'Manrope', style: 'Bold', color: '#6b7280', x: 0, y: 4, width: 26, align: 'CENTER',
    }));
    stepper.appendChild(await createTextNode({
      text: String(it.qty), size: 12, family: 'Manrope', style: 'Bold', color: '#111827', x: 26, y: 6, width: 28, align: 'CENTER',
    }));
    stepper.appendChild(await createTextNode({
      text: '+', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 54, y: 4, width: 26, align: 'CENTER',
    }));

    itemCard.appendChild(stepper);
    cart.appendChild(itemCard);
    y += itemCard.height + 10;
  }

  // Calculation bottom box
  const calcBox = figma.createFrame();
  calcBox.resize(320, 220);
  calcBox.x = 20; calcBox.y = 440;
  calcBox.cornerRadius = 16;
  calcBox.fills = solidPaint('#f4faf7');
  calcBox.strokes = solidPaint('#dcf0e9');
  calcBox.strokeWeight = 1;

  let cy = 14;
  const sums = [
    { label: 'Subtotal', val: '₱550.00' },
    { label: 'Discount', val: '−₱0.00' },
    { label: 'VAT (12% included)', val: '₱58.93' },
  ];
  for (const s of sums) {
    calcBox.appendChild(await createTextNode({
      text: s.label, size: 12, family: 'Manrope', style: 'Medium', color: '#4b5563', x: 16, y: cy, width: 140,
    }));
    calcBox.appendChild(await createTextNode({
      text: s.val, size: 12, family: 'Roboto Mono', style: 'Medium', color: '#111827', x: 160, y: cy, width: 144, align: 'RIGHT',
    }));
    cy += 20;
  }

  // Total
  cy += 6;
  calcBox.appendChild(await createTextNode({
    text: 'TOTAL DUE', size: 13, family: 'Manrope', style: 'Bold', color: '#155d49', x: 16, y: cy, width: 140,
  }));
  calcBox.appendChild(await createTextNode({
    text: '₱550.00', size: 22, family: 'Manrope', style: 'ExtraBold', color: '#111827', x: 140, y: cy - 4, width: 164, align: 'RIGHT',
  }));

  // Checkout Button
  const checkoutBtn = figma.createFrame();
  checkoutBtn.resize(288, 46);
  checkoutBtn.x = 16; checkoutBtn.y = 156;
  checkoutBtn.cornerRadius = 12;
  checkoutBtn.fills = solidPaint('#155d49');

  checkoutBtn.appendChild(await createTextNode({
    text: 'Proceed to Payment →', size: 14, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 0, y: 13, width: 288, align: 'CENTER',
  }));
  calcBox.appendChild(checkoutBtn);

  cart.appendChild(calcBox);
  centerInViewport(cart);
  addToPage(cart);
  figma.notify('✅ POS Order Cart created!');
}

// ─── Component: Payment Modal ────────────────────────────────────────────────

async function createPaymentModal() {
  const modal = figma.createFrame();
  modal.name = 'Component / Payment Modal';
  modal.resize(460, 480);
  modal.fills = solidPaint('#ffffff');
  modal.cornerRadius = 24;
  modal.strokes = solidPaint('#e5e7eb');
  modal.strokeWeight = 1;

  // Header banner
  const banner = figma.createFrame();
  banner.resize(460, 80);
  banner.fills = solidPaint('#155d49');
  banner.cornerRadius = 0;

  banner.appendChild(await createTextNode({
    text: 'TOTAL AMOUNT DUE', size: 11, family: 'Manrope', style: 'Bold', color: '#93cbb9', letterSpacing: 1, x: 24, y: 16, width: 300,
  }));
  banner.appendChild(await createTextNode({
    text: '₱345.00', size: 28, family: 'Manrope', style: 'ExtraBold', color: '#ffffff', x: 24, y: 34, width: 300,
  }));
  modal.appendChild(banner);

  // Method Tabs
  const tabs = ['💵 Cash', '📱 GCash', '💳 Card'];
  let tx = 24;
  for (let i = 0; i < tabs.length; i++) {
    const tab = figma.createFrame();
    tab.resize(128, 38);
    tab.x = tx; tab.y = 100;
    tab.cornerRadius = 10;
    tab.fills = solidPaint(i === 0 ? '#dcf0e9' : '#f9fafb');
    tab.strokes = solidPaint(i === 0 ? '#155d49' : '#e5e7eb');
    tab.strokeWeight = 1;

    tab.appendChild(await createTextNode({
      text: tabs[i], size: 12, family: 'Manrope', style: 'Bold', color: i === 0 ? '#155d49' : '#4b5563', x: 0, y: 10, width: 128, align: 'CENTER',
    }));
    modal.appendChild(tab);
    tx += 140;
  }

  // Quick bills
  modal.appendChild(await createTextNode({
    text: 'QUICK CASH TENDER', size: 11, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.8, x: 24, y: 154, width: 300,
  }));

  const bills = ['Exact (₱345)', '₱500', '₱1,000', '₱2,000'];
  let bx = 24;
  for (const b of bills) {
    const btn = figma.createFrame();
    btn.resize(96, 36);
    btn.x = bx; btn.y = 176;
    btn.cornerRadius = 10;
    btn.fills = solidPaint('#f3f4f6');
    btn.appendChild(await createTextNode({
      text: b, size: 11, family: 'Roboto Mono', style: 'Bold', color: '#111827', x: 0, y: 9, width: 96, align: 'CENTER',
    }));
    modal.appendChild(btn);
    bx += 105;
  }

  // Amount Received Input
  modal.appendChild(await createTextNode({
    text: 'AMOUNT RECEIVED', size: 11, family: 'Manrope', style: 'Bold', color: '#6b7280', x: 24, y: 228, width: 300,
  }));

  const inp = figma.createFrame();
  inp.resize(412, 48);
  inp.x = 24; inp.y = 250;
  inp.cornerRadius = 12;
  inp.fills = solidPaint('#ffffff');
  inp.strokes = solidPaint('#155d49');
  inp.strokeWeight = 2;

  inp.appendChild(await createTextNode({
    text: '₱500.00', size: 18, family: 'Roboto Mono', style: 'Bold', color: '#111827', x: 16, y: 12, width: 380,
  }));
  modal.appendChild(inp);

  // Change highlight card
  const chgCard = figma.createFrame();
  chgCard.resize(412, 60);
  chgCard.x = 24; chgCard.y = 314;
  chgCard.cornerRadius = 14;
  chgCard.fills = solidPaint('#ecfdf5');
  chgCard.strokes = solidPaint('#a7f3d0');
  chgCard.strokeWeight = 1;

  chgCard.appendChild(await createTextNode({
    text: 'CHANGE TO CUSTOMER', size: 11, family: 'Manrope', style: 'Bold', color: '#047857', x: 16, y: 10, width: 200,
  }));
  chgCard.appendChild(await createTextNode({
    text: '₱155.00', size: 22, family: 'Manrope', style: 'ExtraBold', color: '#065f46', x: 16, y: 28, width: 200,
  }));
  modal.appendChild(chgCard);

  // Buttons
  const submit = figma.createFrame();
  submit.resize(412, 48);
  submit.x = 24; submit.y = 394;
  submit.cornerRadius = 14;
  submit.fills = solidPaint('#155d49');

  submit.appendChild(await createTextNode({
    text: 'Complete Order & Print Receipt ✓', size: 14, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 0, y: 14, width: 412, align: 'CENTER',
  }));
  modal.appendChild(submit);

  centerInViewport(modal);
  addToPage(modal);
  figma.notify('✅ Payment Modal created!');
}

// ─── Component: Category Filter Tabs ────────────────────────────────────────

async function createCategoryFilterTabs() {
  const bar = figma.createFrame();
  bar.name = 'Component / Category Filter Tabs';
  bar.resize(680, 48);
  bar.fills = solidPaint('#ffffff');
  bar.cornerRadius = 14;
  bar.strokes = solidPaint('#e5e7eb');
  bar.strokeWeight = 1;

  const categories = [
    { label: 'All Items (28)', active: true },
    { label: '☕ Espresso & Coffee', active: false },
    { label: '🧊 Cold Brew', active: false },
    { label: '🍵 Non-Coffee & Tea', active: false },
    { label: '🥐 Pastries', active: false },
  ];

  let x = 8;
  for (const cat of categories) {
    const tab = figma.createFrame();
    tab.resize(cat.label.length * 8 + 24, 32);
    tab.x = x; tab.y = 8;
    tab.cornerRadius = 9999;
    tab.fills = solidPaint(cat.active ? '#155d49' : '#f3f4f6');

    tab.appendChild(await createTextNode({
      text: cat.label,
      size: 11,
      family: 'Manrope',
      style: 'Bold',
      color: cat.active ? '#ffffff' : '#4b5563',
      x: 0, y: 8, width: tab.width, align: 'CENTER',
    }));
    bar.appendChild(tab);
    x += tab.width + 8;
  }

  bar.resize(Math.max(x + 8, 680), 48);
  centerInViewport(bar);
  addToPage(bar);
  figma.notify('✅ Category Tabs created!');
}

// ─── Component: Item Customizer Modal ───────────────────────────────────────

async function createItemCustomizerModal() {
  const modal = figma.createFrame();
  modal.name = 'Component / Item Customizer Modal';
  modal.resize(420, 560);
  modal.fills = solidPaint('#ffffff');
  modal.cornerRadius = 24;
  modal.strokes = solidPaint('#e5e7eb');
  modal.strokeWeight = 1;

  // Header
  modal.appendChild(await createTextNode({
    text: 'Spanish Latte', size: 20, family: 'Manrope', style: 'Bold', color: '#111827', x: 24, y: 24, width: 300,
  }));
  modal.appendChild(await createTextNode({
    text: 'COFFEE · BASE ₱120.00', size: 10, family: 'Manrope', style: 'Bold', color: '#155d49', letterSpacing: 1, x: 24, y: 52, width: 300,
  }));

  // Sizes Section
  modal.appendChild(await createTextNode({
    text: 'SELECT SIZE', size: 11, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.8, x: 24, y: 86, width: 300,
  }));

  const sizes = [
    { name: 'Regular (12oz)', price: '₱120.00', sel: true },
    { name: 'Medium (16oz)', price: '+₱20.00', sel: false },
    { name: 'Large (22oz)', price: '+₱40.00', sel: false },
  ];

  let sy = 110;
  for (const s of sizes) {
    const sCard = figma.createFrame();
    sCard.resize(372, 40);
    sCard.x = 24; sCard.y = sy;
    sCard.cornerRadius = 10;
    sCard.fills = solidPaint(s.sel ? '#f4faf7' : '#ffffff');
    sCard.strokes = solidPaint(s.sel ? '#155d49' : '#e5e7eb');
    sCard.strokeWeight = s.sel ? 2 : 1;

    sCard.appendChild(await createTextNode({
      text: s.name, size: 12, family: 'Manrope', style: s.sel ? 'Bold' : 'Medium', color: '#111827', x: 14, y: 11, width: 200,
    }));
    sCard.appendChild(await createTextNode({
      text: s.price, size: 12, family: 'Roboto Mono', style: 'Bold', color: s.sel ? '#155d49' : '#6b7280', x: 250, y: 11, width: 108, align: 'RIGHT',
    }));
    modal.appendChild(sCard);
    sy += 48;
  }

  // Addons Section
  sy += 8;
  modal.appendChild(await createTextNode({
    text: 'ADD-ONS & MODIFIERS', size: 11, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.8, x: 24, y: sy, width: 300,
  }));
  sy += 24;

  const addons = [
    { name: 'Extra Espresso Shot', price: '+₱30.00', checked: true },
    { name: 'Oat Milk Substitute', price: '+₱35.00', checked: true },
    { name: 'Vanilla Syrup', price: '+₱20.00', checked: false },
  ];

  for (const a of addons) {
    const aCard = figma.createFrame();
    aCard.resize(372, 38);
    aCard.x = 24; aCard.y = sy;
    aCard.cornerRadius = 10;
    aCard.fills = solidPaint('#f9fafb');

    const check = figma.createRectangle();
    check.resize(18, 18);
    check.x = 12; check.y = 10;
    check.cornerRadius = 5;
    check.fills = solidPaint(a.checked ? '#155d49' : '#ffffff');
    check.strokes = solidPaint(a.checked ? '#155d49' : '#d1d5db');
    check.strokeWeight = 1.5;
    aCard.appendChild(check);

    aCard.appendChild(await createTextNode({
      text: a.name, size: 12, family: 'Manrope', style: 'Medium', color: '#1f2937', x: 40, y: 10, width: 220,
    }));
    aCard.appendChild(await createTextNode({
      text: a.price, size: 11, family: 'Roboto Mono', style: 'Regular', color: '#6b7280', x: 260, y: 11, width: 98, align: 'RIGHT',
    }));
    modal.appendChild(aCard);
    sy += 44;
  }

  // Footer CTA
  const cta = figma.createFrame();
  cta.resize(372, 48);
  cta.x = 24; cta.y = 486;
  cta.cornerRadius = 14;
  cta.fills = solidPaint('#155d49');

  cta.appendChild(await createTextNode({
    text: 'Add to Order · ₱185.00', size: 14, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 0, y: 14, width: 372, align: 'CENTER',
  }));
  modal.appendChild(cta);

  centerInViewport(modal);
  addToPage(modal);
  figma.notify('✅ Item Customizer created!');
}

// ─── Component: Stock Movement Modal ─────────────────────────────────────────

async function createStockMovementModal() {
  const modal = figma.createFrame();
  modal.name = 'Component / Stock Movement Modal';
  modal.resize(440, 460);
  modal.fills = solidPaint('#ffffff');
  modal.cornerRadius = 24;
  modal.strokes = solidPaint('#e5e7eb');
  modal.strokeWeight = 1;

  modal.appendChild(await createTextNode({
    text: 'Record Stock-In / Delivery', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 24, y: 24, width: 380,
  }));
  modal.appendChild(await createTextNode({
    text: 'Receive fresh ingredients into inventory ledger', size: 11, family: 'Manrope', style: 'Regular', color: '#6b7280', x: 24, y: 48, width: 380,
  }));

  const fields = [
    { label: 'Ingredient', val: 'Arabica Coffee Beans (Current: 4.25 kg)', y: 84 },
    { label: 'Quantity to Add (kg)', val: '10.00', y: 154 },
    { label: 'Supplier', val: 'Highland Farms Coffee Co.', y: 224 },
    { label: 'Invoice / Ref #', val: 'INV-2026-88412', y: 294 },
  ];

  for (const f of fields) {
    modal.appendChild(await createTextNode({
      text: f.label, size: 11, family: 'Manrope', style: 'Bold', color: '#4b5563', x: 24, y: f.y, width: 392,
    }));

    const inp = figma.createFrame();
    inp.resize(392, 40);
    inp.x = 24; inp.y = f.y + 20;
    inp.cornerRadius = 10;
    inp.fills = solidPaint('#f9fafb');
    inp.strokes = solidPaint('#d1d5db');
    inp.strokeWeight = 1;

    inp.appendChild(await createTextNode({
      text: f.val, size: 12, family: 'Manrope', style: 'Medium', color: '#111827', x: 12, y: 11, width: 368,
    }));
    modal.appendChild(inp);
  }

  // Submit
  const sub = figma.createFrame();
  sub.resize(392, 46);
  sub.x = 24; sub.y = 388;
  sub.cornerRadius = 12;
  sub.fills = solidPaint('#155d49');

  sub.appendChild(await createTextNode({
    text: 'Confirm Stock In (+10.00 kg)', size: 13, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 0, y: 14, width: 392, align: 'CENTER',
  }));
  modal.appendChild(sub);

  centerInViewport(modal);
  addToPage(modal);
  figma.notify('✅ Stock Movement Modal created!');
}

// ─── Component: Flash Message ────────────────────────────────────────────────

async function createFlashMessage({ type = 'success' } = {}) {
  const config = {
    success: { bg: '#16a34a', label: '✓  Order #ORD-0042 processed successfully!' },
    error:   { bg: '#dc2626', label: '✕  Authorization failed. Invalid credentials.' },
    warning: { bg: '#f59e0b', label: '⚠  Low stock alert: Espresso Beans (1.2 kg remaining).' },
  }[type] || { bg: '#155d49', label: 'Notification' };

  const flash = figma.createFrame();
  flash.name = `Component / Flash (${type})`;
  flash.resize(600, 46);
  flash.cornerRadius = 12;
  flash.fills = solidPaint(config.bg);

  flash.appendChild(await createTextNode({
    text: config.label, size: 13, family: 'Manrope', style: 'SemiBold', color: '#ffffff', x: 16, y: 13, width: 530,
  }));
  flash.appendChild(await createTextNode({
    text: '✕', size: 13, family: 'Manrope', style: 'Bold', color: '#ffffff', opacity: 0.7, x: 566, y: 13, width: 20, align: 'CENTER',
  }));

  centerInViewport(flash);
  addToPage(flash);
  figma.notify(`✅ Flash Message (${type}) inserted!`);
}

// ─── Component: Role Badges ──────────────────────────────────────────────────

async function createRoleBadges() {
  const roles = [
    { label: 'OWNER',      bg: '#155d49', text: '#ffffff' },
    { label: 'MANAGER',    bg: '#2c7865', text: '#ffffff' },
    { label: 'CASHIER',    bg: '#f3f4f6', text: '#374151' },
  ];

  const group = figma.createFrame();
  group.name = 'Component / Role Badges';
  group.fills = [];
  group.resize(400, 36);

  let x = 0;
  for (const r of roles) {
    const badge = figma.createFrame();
    badge.name = `Badge / ${r.label}`;
    badge.resize(r.label.length * 8 + 24, 28);
    badge.x = x; badge.y = 4;
    badge.cornerRadius = 9999;
    badge.fills = solidPaint(r.bg);

    badge.appendChild(await createTextNode({
      text: r.label, size: 10, family: 'Manrope', style: 'Bold', color: r.text, letterSpacing: 0.8, x: 0, y: 7, width: badge.width, align: 'CENTER',
    }));
    group.appendChild(badge);
    x += badge.width + 10;
  }

  group.resize(x, 36);
  centerInViewport(group);
  addToPage(group);
  figma.notify('✅ Role Badges inserted!');
}

// ─── Component: Notification Badge ───────────────────────────────────────────

async function createNotifBadge() {
  const group = figma.createFrame();
  group.name = 'Component / Bell + Badge';
  group.resize(44, 44);
  group.fills = [];

  const bell = figma.createFrame();
  bell.resize(40, 40);
  bell.x = 2; bell.y = 2;
  bell.cornerRadius = 10;
  bell.fills = solidPaint('#f3f4f6');

  bell.appendChild(await createTextNode({
    text: '🔔', size: 18, family: 'Inter', style: 'Regular', x: 0, y: 9, width: 40, align: 'CENTER',
  }));
  group.appendChild(bell);

  const badge = figma.createFrame();
  badge.resize(20, 20);
  badge.x = 24; badge.y = 0;
  badge.cornerRadius = 9999;
  badge.fills = solidPaint('#ef4444');

  badge.appendChild(await createTextNode({
    text: '3', size: 10, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 0, y: 3, width: 20, align: 'CENTER',
  }));
  group.appendChild(badge);

  centerInViewport(group);
  addToPage(group);
  figma.notify('✅ Notification Badge inserted!');
}

// ─── Component: Data Table ───────────────────────────────────────────────────

async function createDataTableRow() {
  const container = figma.createFrame();
  container.name = 'Component / Data Table';
  container.resize(880, 160);
  container.fills = solidPaint('#ffffff');
  container.cornerRadius = 16;
  container.strokes = solidPaint('#e5e7eb');
  container.strokeWeight = 1;
  container.clipsContent = true;

  // Header Row
  const header = figma.createFrame();
  header.resize(880, 44);
  header.fills = solidPaint('#f9fafb');
  header.strokes = solidPaint('#e5e7eb');
  header.strokeWeight = 1;

  const cols = ['ORDER #', 'CASHIER', 'ITEMS', 'PAYMENT', 'TOTAL', 'STATUS', 'DATE'];
  const widths = [130, 180, 70, 110, 110, 110, 170];
  let x = 0;
  for (let i = 0; i < cols.length; i++) {
    header.appendChild(await createTextNode({
      text: cols[i], size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.6, x: x + 16, y: 15, width: widths[i] - 16,
    }));
    x += widths[i];
  }
  container.appendChild(header);

  // Sample data rows
  const rows = [
    ['#ORD-0042', 'Maria Santos', '3', 'Cash', '₱345.00', 'Completed', 'Sep 16, 2026 9:42 AM'],
    ['#ORD-0041', 'Carlos Reyes', '2', 'GCash', '₱210.00', 'Completed', 'Sep 16, 2026 9:30 AM'],
  ];

  let y = 44;
  for (const r of rows) {
    const rowFrame = figma.createFrame();
    rowFrame.resize(880, 52);
    rowFrame.y = y;
    rowFrame.fills = solidPaint('#ffffff');
    rowFrame.strokes = solidPaint('#f3f4f6');
    rowFrame.strokeWeight = 1;

    x = 0;
    for (let i = 0; i < r.length; i++) {
      rowFrame.appendChild(await createTextNode({
        text: r[i],
        size: 12,
        family: i === 0 ? 'Roboto Mono' : 'Manrope',
        style: i === 0 || i === 4 ? 'Bold' : 'Regular',
        color: i === 5 ? '#15803d' : (i === 4 ? '#111827' : '#374151'),
        x: x + 16, y: 17, width: widths[i] - 16,
      }));
      x += widths[i];
    }
    container.appendChild(rowFrame);
    y += 52;
  }

  container.resize(880, y);
  centerInViewport(container);
  addToPage(container);
  figma.notify('✅ Data Table created!');
}

// ─── Component: Auth Modal ───────────────────────────────────────────────────

async function createAuthModal() {
  const modal = figma.createFrame();
  modal.name = 'Component / Authorization Modal';
  modal.resize(440, 400);
  modal.fills = solidPaint('#ffffff');
  modal.cornerRadius = 24;
  modal.strokes = solidPaint('#e5e7eb');
  modal.strokeWeight = 1;

  modal.appendChild(await createTextNode({
    text: 'Authorization Required', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 24, y: 24, width: 380,
  }));
  modal.appendChild(await createTextNode({
    text: 'Manager or Owner credentials required to proceed.', size: 12, family: 'Manrope', style: 'Regular', color: '#6b7280', x: 24, y: 48, width: 380,
  }));

  const fields = [
    { label: 'Authorizer Email', val: 'manager@heim.com', y: 86 },
    { label: 'Password', val: '••••••••••••', y: 160 },
    { label: 'Reason for Action', val: 'Customer requested refund due to duplicate order', y: 234 },
  ];

  for (const f of fields) {
    modal.appendChild(await createTextNode({
      text: f.label, size: 11, family: 'Manrope', style: 'Bold', color: '#4b5563', x: 24, y: f.y, width: 392,
    }));

    const inp = figma.createFrame();
    inp.resize(392, 42);
    inp.x = 24; inp.y = f.y + 20;
    inp.cornerRadius = 10;
    inp.fills = solidPaint('#f9fafb');
    inp.strokes = solidPaint('#d1d5db');
    inp.strokeWeight = 1;

    inp.appendChild(await createTextNode({
      text: f.val, size: 12, family: 'Manrope', style: 'Medium', color: '#111827', x: 14, y: 12, width: 364,
    }));
    modal.appendChild(inp);
  }

  // Buttons
  const cancelBtn = figma.createFrame();
  cancelBtn.resize(188, 44);
  cancelBtn.x = 24; cancelBtn.y = 330;
  cancelBtn.cornerRadius = 12;
  cancelBtn.fills = solidPaint('#f3f4f6');
  cancelBtn.appendChild(await createTextNode({
    text: 'Cancel', size: 13, family: 'Manrope', style: 'Bold', color: '#4b5563', x: 0, y: 13, width: 188, align: 'CENTER',
  }));
  modal.appendChild(cancelBtn);

  const authBtn = figma.createFrame();
  authBtn.resize(188, 44);
  authBtn.x = 228; authBtn.y = 330;
  authBtn.cornerRadius = 12;
  authBtn.fills = solidPaint('#155d49');
  authBtn.appendChild(await createTextNode({
    text: 'Authorize Action', size: 13, family: 'Manrope', style: 'Bold', color: '#ffffff', x: 0, y: 13, width: 188, align: 'CENTER',
  }));
  modal.appendChild(authBtn);

  centerInViewport(modal);
  addToPage(modal);
  figma.notify('✅ Auth Modal created!');
}

// ═══════════════════════════════════════════════════════════════════════════
// SCREEN SCAFFOLDS (1440 × 900)
// ═══════════════════════════════════════════════════════════════════════════

function createBaseScreen(name, titleText) {
  const screen = figma.createFrame();
  screen.name = `Screen / ${name}`;
  screen.resize(1440, 900);
  screen.fills = solidPaint('#f9fafb');

  // Sidebar
  const sidebar = figma.createRectangle();
  sidebar.resize(256, 900);
  sidebar.fills = solidPaint('#0c352a');
  screen.appendChild(sidebar);

  // Topbar
  const topbar = figma.createRectangle();
  topbar.resize(1184, 64);
  topbar.x = 256; topbar.y = 0;
  topbar.fills = solidPaint('#ffffff');
  topbar.strokes = solidPaint('#e5e7eb');
  topbar.strokeWeight = 1;
  screen.appendChild(topbar);

  return screen;
}

// ─── 1. Dashboard Screen Scaffold ────────────────────────────────────────────

async function createDashboardScreen() {
  const screen = createBaseScreen('Dashboard', 'Dashboard');

  screen.appendChild(await createTextNode({
    text: 'Heim POS · Dashboard', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  // 4 KPIs
  const kpis = [
    { label: "TODAY'S NET SALES", val: '₱18,450.00', sub: '+14% vs yesterday' },
    { label: 'ORDERS COMPLETED', val: '64', sub: 'Active cashiers: 2' },
    { label: '7-DAY ROLLING SALES', val: '₱112,800.00', sub: 'Target: ₱120,000' },
    { label: 'LOW STOCK ALERTS', val: '2 items', sub: 'Beans, Full Cream Milk' },
  ];

  for (let i = 0; i < 4; i++) {
    const card = figma.createFrame();
    card.resize(268, 108);
    card.x = 280 + i * (268 + 16);
    card.y = 88;
    card.cornerRadius = 16;
    card.fills = solidPaint('#ffffff');
    card.strokes = solidPaint('#e5e7eb');
    card.strokeWeight = 1;

    card.appendChild(await createTextNode({
      text: kpis[i].label, size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.6, x: 16, y: 16, width: 220,
    }));
    card.appendChild(await createTextNode({
      text: kpis[i].val, size: 22, family: 'Manrope', style: 'ExtraBold', color: '#111827', x: 16, y: 38, width: 220,
    }));
    card.appendChild(await createTextNode({
      text: kpis[i].sub, size: 11, family: 'Manrope', style: 'Medium', color: '#155d49', x: 16, y: 76, width: 220,
    }));
    screen.appendChild(card);
  }

  // Chart Frame
  const chart = figma.createFrame();
  chart.resize(720, 320);
  chart.x = 280; chart.y = 216;
  chart.cornerRadius = 16;
  chart.fills = solidPaint('#ffffff');
  chart.strokes = solidPaint('#e5e7eb');
  chart.strokeWeight = 1;
  chart.appendChild(await createTextNode({
    text: 'Sales Trend — Past 7 Days', size: 14, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 300,
  }));
  screen.appendChild(chart);

  // Best Sellers Panel
  const bestSellers = figma.createFrame();
  bestSellers.resize(380, 320);
  bestSellers.x = 1020; bestSellers.y = 216;
  bestSellers.cornerRadius = 16;
  bestSellers.fills = solidPaint('#ffffff');
  bestSellers.strokes = solidPaint('#e5e7eb');
  bestSellers.strokeWeight = 1;
  bestSellers.appendChild(await createTextNode({
    text: '🏆 Top Selling Items Today', size: 14, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 300,
  }));
  screen.appendChild(bestSellers);

  // Recent Orders Table Placeholder
  const ordersTbl = figma.createFrame();
  ordersTbl.resize(1120, 300);
  ordersTbl.x = 280; ordersTbl.y = 556;
  ordersTbl.cornerRadius = 16;
  ordersTbl.fills = solidPaint('#ffffff');
  ordersTbl.strokes = solidPaint('#e5e7eb');
  ordersTbl.strokeWeight = 1;
  ordersTbl.appendChild(await createTextNode({
    text: 'Recent Completed Orders', size: 14, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 300,
  }));
  screen.appendChild(ordersTbl);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Dashboard screen created!');
}

// ─── 2. POS Terminal Screen Scaffold ─────────────────────────────────────────

async function createPosScreen() {
  const screen = createBaseScreen('POS Terminal', 'POS Terminal');

  screen.appendChild(await createTextNode({
    text: 'Point of Sale Terminal · Cashier: Maria Santos', size: 16, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  // Categories Bar
  const catBar = figma.createFrame();
  catBar.resize(750, 42);
  catBar.x = 280; catBar.y = 80;
  catBar.fills = [];
  catBar.appendChild(await createTextNode({
    text: 'All Items (28)   ·   Espresso (12)   ·   Cold Brew (4)   ·   Matcha & Tea (6)   ·   Pastries (6)',
    size: 12, family: 'Manrope', style: 'Bold', color: '#155d49', x: 0, y: 12, width: 750,
  }));
  screen.appendChild(catBar);

  // Product Grid Area (3 x 3 sample tiles)
  for (let r = 0; r < 3; r++) {
    for (let c = 0; c < 4; c++) {
      const tile = figma.createFrame();
      tile.resize(172, 210);
      tile.x = 280 + c * (172 + 16);
      tile.y = 136 + r * (210 + 16);
      tile.cornerRadius = 16;
      tile.fills = solidPaint('#ffffff');
      tile.strokes = solidPaint('#e5e7eb');
      tile.strokeWeight = 1;

      const img = figma.createRectangle();
      img.resize(172, 110);
      img.fills = solidPaint('#f4faf7');
      tile.appendChild(img);

      tile.appendChild(await createTextNode({
        text: 'Item #' + (r * 4 + c + 1), size: 13, family: 'Manrope', style: 'Bold', color: '#111827', x: 12, y: 122, width: 148,
      }));
      tile.appendChild(await createTextNode({
        text: '₱140.00', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 12, y: 168, width: 148,
      }));
      screen.appendChild(tile);
    }
  }

  // Cart Panel on the right (360px wide)
  const cart = figma.createFrame();
  cart.resize(360, 804);
  cart.x = 1048; cart.y = 76;
  cart.cornerRadius = 20;
  cart.fills = solidPaint('#ffffff');
  cart.strokes = solidPaint('#e5e7eb');
  cart.strokeWeight = 1;

  cart.appendChild(await createTextNode({
    text: 'Current Order Cart', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 240,
  }));
  screen.appendChild(cart);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ POS Terminal screen created!');
}

// ─── 3. Orders Management Screen Scaffold ────────────────────────────────────

async function createOrdersScreen() {
  const screen = createBaseScreen('Orders', 'Orders');

  screen.appendChild(await createTextNode({
    text: 'Orders Ledger & History', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  // Filters Row (Search + Status + Date range + Export)
  const filterRow = figma.createFrame();
  filterRow.resize(1120, 56);
  filterRow.x = 280; filterRow.y = 84;
  filterRow.cornerRadius = 16;
  filterRow.fills = solidPaint('#ffffff');
  filterRow.strokes = solidPaint('#e5e7eb');
  filterRow.strokeWeight = 1;

  filterRow.appendChild(await createTextNode({
    text: '🔍 Search by order # or cashier...', size: 12, family: 'Inter', style: 'Regular', color: '#9ca3af', x: 18, y: 18, width: 280,
  }));
  filterRow.appendChild(await createTextNode({
    text: 'Status: All Statuses ▾       Date: Today ▾       Payment: All ▾',
    size: 12, family: 'Manrope', style: 'SemiBold', color: '#4b5563', x: 380, y: 18, width: 400,
  }));
  screen.appendChild(filterRow);

  // Orders Table
  const table = figma.createFrame();
  table.resize(1120, 680);
  table.x = 280; table.y = 156;
  table.cornerRadius = 16;
  table.fills = solidPaint('#ffffff');
  table.strokes = solidPaint('#e5e7eb');
  table.strokeWeight = 1;
  table.clipsContent = true;

  // Header
  const th = figma.createFrame();
  th.resize(1120, 46);
  th.fills = solidPaint('#f9fafb');
  th.strokes = solidPaint('#e5e7eb');
  th.strokeWeight = 1;

  const cols = ['ORDER #', 'CASHIER', 'ITEMS', 'PAYMENT', 'DISCOUNT', 'TOTAL DUE', 'STATUS', 'CREATED AT', 'ACTIONS'];
  const w = [130, 160, 70, 100, 100, 110, 110, 170, 130];
  let x = 0;
  for (let i = 0; i < cols.length; i++) {
    th.appendChild(await createTextNode({
      text: cols[i], size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.6, x: x + 16, y: 16, width: w[i] - 16,
    }));
    x += w[i];
  }
  table.appendChild(th);

  // 10 Sample Rows
  const statuses = ['Completed', 'Completed', 'Refunded', 'Completed', 'Cancelled', 'Completed', 'Completed', 'Completed'];
  let y = 46;
  for (let i = 0; i < 8; i++) {
    const tr = figma.createFrame();
    tr.resize(1120, 52);
    tr.y = y;
    tr.fills = solidPaint(i % 2 === 0 ? '#ffffff' : '#fcfdfd');
    tr.strokes = solidPaint('#f3f4f6');
    tr.strokeWeight = 1;

    x = 0;
    const rowVals = [
      `#ORD-${42 - i}`, 'Maria Santos', '3', i % 2 === 0 ? 'Cash' : 'GCash', '₱0.00', `₱${345 - i * 30}.00`,
      statuses[i], 'Sep 16, 2026 09:42 AM', 'View →'
    ];
    for (let c = 0; c < rowVals.length; c++) {
      tr.appendChild(await createTextNode({
        text: rowVals[c],
        size: 12,
        family: c === 0 ? 'Roboto Mono' : 'Manrope',
        style: c === 0 || c === 5 ? 'Bold' : 'Regular',
        color: c === 6 ? (rowVals[c] === 'Completed' ? '#15803d' : '#b91c1c') : (c === 8 ? '#155d49' : '#374151'),
        x: x + 16, y: 17, width: w[c] - 16,
      }));
      x += w[c];
    }
    table.appendChild(tr);
    y += 52;
  }

  screen.appendChild(table);
  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Orders screen created!');
}

// ─── 4. Inventory Management Screen Scaffold ─────────────────────────────────

async function createInventoryScreen() {
  const screen = createBaseScreen('Inventory', 'Inventory');

  screen.appendChild(await createTextNode({
    text: 'Raw Ingredients & Stock Overview', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  // KPI Strip
  const stats = [
    { label: 'ALL INGREDIENTS', val: '24 Items', color: '#111827' },
    { label: 'HEALTHY STOCK', val: '21 Good', color: '#15803d' },
    { label: 'LOW STOCK ALERT', val: '2 Warning', color: '#d97706' },
    { label: 'OUT OF STOCK', val: '1 Depleted', color: '#dc2626' },
  ];

  for (let i = 0; i < 4; i++) {
    const card = figma.createFrame();
    card.resize(268, 86);
    card.x = 280 + i * (268 + 16);
    card.y = 84;
    card.cornerRadius = 16;
    card.fills = solidPaint('#ffffff');
    card.strokes = solidPaint('#e5e7eb');
    card.strokeWeight = 1;

    card.appendChild(await createTextNode({
      text: stats[i].label, size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.8, x: 16, y: 16, width: 220,
    }));
    card.appendChild(await createTextNode({
      text: stats[i].val, size: 20, family: 'Manrope', style: 'ExtraBold', color: stats[i].color, x: 16, y: 38, width: 220,
    }));
    screen.appendChild(card);
  }

  // Action Buttons row (+ Stock-In, Record Waste, Stock Adjustment)
  const btnRow = figma.createFrame();
  btnRow.resize(1120, 48);
  btnRow.x = 280; btnRow.y = 186;
  btnRow.fills = [];

  const actions = [
    { label: '+ Stock-In Delivery', bg: '#155d49', text: '#ffffff' },
    { label: '⚠ Record Waste / Spoilage', bg: '#fee2e2', text: '#b91c1c' },
    { label: '± Physical Count Adjustment', bg: '#eff6ff', text: '#1d4ed8' },
  ];
  let ax = 0;
  for (const a of actions) {
    const btn = figma.createFrame();
    btn.resize(200, 42);
    btn.x = ax; btn.y = 0;
    btn.cornerRadius = 12;
    btn.fills = solidPaint(a.bg);

    btn.appendChild(await createTextNode({
      text: a.label, size: 12, family: 'Manrope', style: 'Bold', color: a.text, x: 0, y: 12, width: 200, align: 'CENTER',
    }));
    btnRow.appendChild(btn);
    ax += 214;
  }
  screen.appendChild(btnRow);

  // Table Placeholder
  const tbl = figma.createFrame();
  tbl.resize(1120, 600);
  tbl.x = 280; tbl.y = 244;
  tbl.cornerRadius = 16;
  tbl.fills = solidPaint('#ffffff');
  tbl.strokes = solidPaint('#e5e7eb');
  tbl.strokeWeight = 1;
  tbl.appendChild(await createTextNode({
    text: 'Raw Ingredient Stock Levels & Reorder Thresholds', size: 14, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 400,
  }));
  screen.appendChild(tbl);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Inventory screen created!');
}

// ─── 5. Daily Consumption Ledger Screen Scaffold ─────────────────────────────

async function createConsumptionScreen() {
  const screen = createBaseScreen('Daily Consumption', 'Daily Consumption');

  screen.appendChild(await createTextNode({
    text: 'Daily Consumption & Reconciliation Ledger', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 450,
  }));

  // Date selection bar
  const dateBar = figma.createFrame();
  dateBar.resize(1120, 56);
  dateBar.x = 280; dateBar.y = 84;
  dateBar.cornerRadius = 16;
  dateBar.fills = solidPaint('#ffffff');
  dateBar.strokes = solidPaint('#e5e7eb');
  dateBar.strokeWeight = 1;

  dateBar.appendChild(await createTextNode({
    text: 'TARGET AUDIT DATE:  Sep 16, 2026 ▾      [ Audit Date ]       Formula: Opening + In - Sales - Waste ± Adj = Closing',
    size: 12, family: 'Manrope', style: 'SemiBold', color: '#155d49', x: 20, y: 18, width: 800,
  }));
  screen.appendChild(dateBar);

  // 3 KPI cards
  const kpis = [
    { label: 'AUDIT DATE', val: 'Sep 16, 2026', sub: 'Today' },
    { label: 'SETTLED ORDERS', val: '64 Completed', sub: 'Cash, GCash, Card' },
    { label: 'ITEMS DISPENSED', val: '142 Cups', sub: 'Prepared products' },
  ];
  for (let i = 0; i < 3; i++) {
    const card = figma.createFrame();
    card.resize(362, 86);
    card.x = 280 + i * (362 + 17);
    card.y = 156;
    card.cornerRadius = 16;
    card.fills = solidPaint('#ffffff');
    card.strokes = solidPaint('#e5e7eb');
    card.strokeWeight = 1;

    card.appendChild(await createTextNode({
      text: kpis[i].label, size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.8, x: 16, y: 16, width: 220,
    }));
    card.appendChild(await createTextNode({
      text: kpis[i].val, size: 20, family: 'Manrope', style: 'ExtraBold', color: '#111827', x: 16, y: 38, width: 220,
    }));
    screen.appendChild(card);
  }

  // Ledger Matrix Table
  const table = figma.createFrame();
  table.resize(1120, 600);
  table.x = 280; table.y = 258;
  table.cornerRadius = 16;
  table.fills = solidPaint('#ffffff');
  table.strokes = solidPaint('#e5e7eb');
  table.strokeWeight = 1;
  table.clipsContent = true;

  const th = figma.createFrame();
  th.resize(1120, 46);
  th.fills = solidPaint('#f9fafb');
  th.strokes = solidPaint('#e5e7eb');
  th.strokeWeight = 1;

  const cols = ['INGREDIENT', 'OPENING', '+ DELIVERIES', '− POS USAGE', '− WASTE/SPOIL', '± AUDIT ADJ', 'CLOSING BALANCE'];
  const w = [220, 150, 150, 150, 150, 150, 150];
  let x = 0;
  for (let i = 0; i < cols.length; i++) {
    th.appendChild(await createTextNode({
      text: cols[i], size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.6, x: x + 16, y: 16, width: w[i] - 16,
    }));
    x += w[i];
  }
  table.appendChild(th);

  screen.appendChild(table);
  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Consumption screen created!');
}

// ─── 6. Sales & Analytics Reports Screen Scaffold ────────────────────────────

async function createReportsScreen() {
  const screen = createBaseScreen('Reports', 'Reports');

  screen.appendChild(await createTextNode({
    text: 'Sales & Inventory Analytics', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  // Period Selector Pills
  const periodBar = figma.createFrame();
  periodBar.resize(1120, 48);
  periodBar.x = 280; periodBar.y = 84;
  periodBar.fills = [];
  periodBar.appendChild(await createTextNode({
    text: 'Daily Report (Active)   ·   Weekly   ·   Monthly   ·   Yearly   ·   Custom Range   |   [ Export PDF ]  [ Export CSV ]',
    size: 12, family: 'Manrope', style: 'Bold', color: '#155d49', x: 0, y: 14, width: 1000,
  }));
  screen.appendChild(periodBar);

  // 4 KPI Summary
  const rStats = [
    { label: 'GROSS REVENUE', val: '₱142,500.00' },
    { label: 'TOTAL ORDERS', val: '482 Completed' },
    { label: 'ITEMS PREPARED', val: '1,120 Units' },
    { label: 'AVERAGE BASKET', val: '₱295.64' },
  ];
  for (let i = 0; i < 4; i++) {
    const card = figma.createFrame();
    card.resize(268, 86);
    card.x = 280 + i * (268 + 16);
    card.y = 142;
    card.cornerRadius = 16;
    card.fills = solidPaint('#ffffff');
    card.strokes = solidPaint('#e5e7eb');
    card.strokeWeight = 1;

    card.appendChild(await createTextNode({
      text: rStats[i].label, size: 10, family: 'Manrope', style: 'Bold', color: '#6b7280', letterSpacing: 0.8, x: 16, y: 16, width: 220,
    }));
    card.appendChild(await createTextNode({
      text: rStats[i].val, size: 20, family: 'Manrope', style: 'ExtraBold', color: '#111827', x: 16, y: 38, width: 220,
    }));
    screen.appendChild(card);
  }

  // Chart
  const chart = figma.createFrame();
  chart.resize(720, 360);
  chart.x = 280; chart.y = 244;
  chart.cornerRadius = 16;
  chart.fills = solidPaint('#ffffff');
  chart.strokes = solidPaint('#e5e7eb');
  chart.strokeWeight = 1;
  chart.appendChild(await createTextNode({
    text: 'Hourly Volume & Sales Distribution', size: 14, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 300,
  }));
  screen.appendChild(chart);

  // Payment Breakdown
  const payCard = figma.createFrame();
  payCard.resize(380, 360);
  payCard.x = 1020; payCard.y = 244;
  payCard.cornerRadius = 16;
  payCard.fills = solidPaint('#ffffff');
  payCard.strokes = solidPaint('#e5e7eb');
  payCard.strokeWeight = 1;
  payCard.appendChild(await createTextNode({
    text: 'Payment Method Breakdown\nCash: 62%   ·   GCash: 26%   ·   Card: 12%', size: 13, family: 'Manrope', style: 'Bold', color: '#155d49', x: 20, y: 20, width: 340, lineHeight: 22,
  }));
  screen.appendChild(payCard);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Reports screen created!');
}

// ─── 7. Recipes & BOM Screen Scaffold ────────────────────────────────────────

async function createRecipesScreen() {
  const screen = createBaseScreen('Recipes', 'Recipes');

  screen.appendChild(await createTextNode({
    text: 'Recipes & Bill of Materials (BOM)', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  // Left column: Product & Size Selector (320px)
  const leftCol = figma.createFrame();
  leftCol.resize(320, 780);
  leftCol.x = 280; leftCol.y = 84;
  leftCol.cornerRadius = 16;
  leftCol.fills = solidPaint('#ffffff');
  leftCol.strokes = solidPaint('#e5e7eb');
  leftCol.strokeWeight = 1;
  leftCol.appendChild(await createTextNode({
    text: 'Select Menu Variant', size: 14, family: 'Manrope', style: 'Bold', color: '#111827', x: 20, y: 20, width: 280,
  }));
  screen.appendChild(leftCol);

  // Right column: Recipe Ingredient Configuration
  const rightCol = figma.createFrame();
  rightCol.resize(780, 780);
  rightCol.x = 620; rightCol.y = 84;
  rightCol.cornerRadius = 16;
  rightCol.fills = solidPaint('#ffffff');
  rightCol.strokes = solidPaint('#e5e7eb');
  rightCol.strokeWeight = 1;
  rightCol.appendChild(await createTextNode({
    text: 'Recipe Specification: Spanish Latte (Regular)', size: 16, family: 'Manrope', style: 'Bold', color: '#155d49', x: 24, y: 24, width: 500,
  }));
  screen.appendChild(rightCol);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Recipes screen created!');
}

// ─── 8. Users & Access Management Screen Scaffold ────────────────────────────

async function createUsersScreen() {
  const screen = createBaseScreen('Users', 'Users');

  screen.appendChild(await createTextNode({
    text: 'Staff & Role-Based Access Control', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  const userTable = figma.createFrame();
  userTable.resize(1120, 780);
  userTable.x = 280; userTable.y = 84;
  userTable.cornerRadius = 16;
  userTable.fills = solidPaint('#ffffff');
  userTable.strokes = solidPaint('#e5e7eb');
  userTable.strokeWeight = 1;

  userTable.appendChild(await createTextNode({
    text: 'Active Team Accounts & Role Hierarchy (Owner · Manager · Cashier)', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 24, y: 24, width: 700,
  }));
  screen.appendChild(userTable);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Users screen created!');
}

// ─── 9. Products Menu Management Screen Scaffold ──────────────────────────────

async function createProductsScreen() {
  const screen = createBaseScreen('Products Menu Management', 'Products');

  screen.appendChild(await createTextNode({
    text: 'Menu Products & Categories', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  const layout = figma.createFrame();
  layout.resize(1120, 780);
  layout.x = 280; layout.y = 84;
  layout.cornerRadius = 16;
  layout.fills = solidPaint('#ffffff');
  layout.strokes = solidPaint('#e5e7eb');
  layout.strokeWeight = 1;

  layout.appendChild(await createTextNode({
    text: 'Category management · Product variant pricing · Add-ons & Active Status Control', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 24, y: 24, width: 700,
  }));
  screen.appendChild(layout);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Products screen created!');
}

// ─── 10. Audit Logs Screen Scaffold ──────────────────────────────────────────

async function createAuditLogsScreen() {
  const screen = createBaseScreen('Audit Logs', 'Audit Logs');

  screen.appendChild(await createTextNode({
    text: 'System Audit Logs & Accountability', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  const logTable = figma.createFrame();
  logTable.resize(1120, 780);
  logTable.x = 280; logTable.y = 84;
  logTable.cornerRadius = 16;
  logTable.fills = solidPaint('#ffffff');
  logTable.strokes = solidPaint('#e5e7eb');
  logTable.strokeWeight = 1;

  logTable.appendChild(await createTextNode({
    text: 'History of sensitive actions (Refunds, Adjustments, Logins) with User Roles & Timestamps', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 24, y: 24, width: 700,
  }));
  screen.appendChild(logTable);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Audit Logs screen created!');
}

// ─── 11. Settings Screen Scaffold ────────────────────────────────────────────

async function createSettingsScreen() {
  const screen = createBaseScreen('Settings', 'Settings');

  screen.appendChild(await createTextNode({
    text: 'System Configuration & Settings', size: 18, family: 'Manrope', style: 'Bold', color: '#111827', x: 280, y: 20, width: 400,
  }));

  const settingsArea = figma.createFrame();
  settingsArea.resize(1120, 780);
  settingsArea.x = 280; settingsArea.y = 84;
  settingsArea.cornerRadius = 16;
  settingsArea.fills = solidPaint('#ffffff');
  settingsArea.strokes = solidPaint('#e5e7eb');
  settingsArea.strokeWeight = 1;

  settingsArea.appendChild(await createTextNode({
    text: 'General Shop Profile · Tax/VAT Rules · Receipt Formatting · System Preferences', size: 14, family: 'Manrope', style: 'Bold', color: '#155d49', x: 24, y: 24, width: 700,
  }));
  screen.appendChild(settingsArea);

  centerInViewport(screen);
  addToPage(screen);
  figma.notify('✅ Settings screen created!');
}

// ─── Main Message Router ────────────────────────────────────────────────────

figma.ui.onmessage = async (msg) => {
  try {
    switch (msg.type) {
      // Tokens
      case 'apply-color':          await applyColorToSelection(msg); break;
      case 'create-color-sheet':   await createColorSwatchSheet(); break;
      case 'apply-typography':     await applyTypographyToSelection(msg.style); break;
      case 'create-type-sheet':    await createTypographySheet(); break;
      case 'create-spacing-sheet': await createSpacingSheet(); break;

      // Components
      case 'create-stat-card':     await createStatCard(msg.options); break;
      case 'create-kpi-card':      await createKpiCard(msg.options); break;
      case 'create-sidebar':       await createSidebarNav(); break;
      case 'create-pos-card':      await createPosProductCard(); break;
      case 'create-receipt':       await createReceiptComponent(); break;
      case 'create-cart':          await createOrderCartComponent(); break;
      case 'create-payment-modal': await createPaymentModal(); break;
      case 'create-customizer':    await createItemCustomizerModal(); break;
      case 'create-stock-modal':   await createStockMovementModal(); break;
      case 'create-category-tabs': await createCategoryFilterTabs(); break;
      case 'create-auth-modal':    await createAuthModal(); break;
      case 'create-flash':         await createFlashMessage(msg.options); break;
      case 'create-role-badges':   await createRoleBadges(); break;
      case 'create-table':         await createDataTableRow(); break;
      case 'create-notif-badge':   await createNotifBadge(); break;

      // Screens
      case 'scaffold-dashboard':   await createDashboardScreen(); break;
      case 'scaffold-pos':         await createPosScreen(); break;
      case 'scaffold-orders':      await createOrdersScreen(); break;
      case 'scaffold-inventory':   await createInventoryScreen(); break;
      case 'scaffold-consumption': await createConsumptionScreen(); break;
      case 'scaffold-reports':     await createReportsScreen(); break;
      case 'scaffold-recipes':     await createRecipesScreen(); break;
      case 'scaffold-users':       await createUsersScreen(); break;
      case 'scaffold-products':    await createProductsScreen(); break;
      case 'scaffold-audit-logs':  await createAuditLogsScreen(); break;
      case 'scaffold-settings':    await createSettingsScreen(); break;

      case 'close': figma.closePlugin(); break;
      default: figma.notify(`Unknown message: ${msg.type}`, { error: true });
    }
  } catch (err) {
    figma.notify(`❌ Error: ${err.message}`, { error: true });
    console.error(err);
  }
};
