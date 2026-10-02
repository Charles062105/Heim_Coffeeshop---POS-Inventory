// ─── MOCK DATA ─────────────────────────────────────────────────────────────

const MOCK_INVENTORY = [
  { id: 'ing1', name: 'Arabica Coffee Beans', unit: 'g', stock: 1500, minStock: 500 },
  { id: 'ing2', name: 'Full Cream Milk', unit: 'ml', stock: 2000, minStock: 1000 },
  { id: 'ing3', name: 'Oat Milk', unit: 'ml', stock: 800, minStock: 500 },
  { id: 'ing4', name: 'Caramel Syrup', unit: 'ml', stock: 400, minStock: 200 },
  { id: 'ing5', name: 'Ice Cubes', unit: 'g', stock: 5000, minStock: 1000 },
  { id: 'ing6', name: '16oz Cups', unit: 'pcs', stock: 150, minStock: 50 },
  { id: 'ing7', name: 'Cup Lids', unit: 'pcs', stock: 145, minStock: 50 }
];

const MOCK_PRODUCTS = [
  { 
    id: 'p1', name: 'Spanish Latte', price: 160, emoji: '☕', 
    recipe: [
      { id: 'ing1', qty: 18 }, // 18g beans
      { id: 'ing2', qty: 180 }, // 180ml milk
      { id: 'ing6', qty: 1 },  // 1 cup
      { id: 'ing7', qty: 1 }   // 1 lid
    ]
  },
  { 
    id: 'p2', name: 'Iced Caramel Macchiato', price: 180, emoji: '🧊',
    recipe: [
      { id: 'ing1', qty: 18 }, // 18g beans
      { id: 'ing2', qty: 150 }, // 150ml milk
      { id: 'ing4', qty: 30 },  // 30ml syrup
      { id: 'ing5', qty: 150 }, // 150g ice
      { id: 'ing6', qty: 1 },
      { id: 'ing7', qty: 1 }
    ]
  },
  { 
    id: 'p3', name: 'Oat Flat White', price: 190, emoji: '☕',
    recipe: [
      { id: 'ing1', qty: 22 }, // 22g beans
      { id: 'ing3', qty: 150 }, // 150ml oat milk
      { id: 'ing6', qty: 1 },
      { id: 'ing7', qty: 1 }
    ]
  }
];

// ─── STATE ────────────────────────────────────────────────────────────────

let cart = []; // Array of { product, quantity }

// ─── DOM ELEMENTS ─────────────────────────────────────────────────────────

const navItems = document.querySelectorAll('.nav-item');
const views = document.querySelectorAll('.view');
const topbarTitle = document.getElementById('topbar-title');

const productsGrid = document.getElementById('products-grid');
const cartItemsContainer = document.getElementById('cart-items');
const cartSubtotalEl = document.getElementById('cart-subtotal');
const cartVatEl = document.getElementById('cart-vat');
const cartTotalEl = document.getElementById('cart-total');
const checkoutBtn = document.getElementById('checkout-btn');
const clearCartBtn = document.getElementById('clear-cart-btn');

const invTableBody = document.getElementById('inventory-table-body');
const totalIngredientsKpi = document.getElementById('total-ingredients-kpi');
const lowStockKpi = document.getElementById('low-stock-kpi');

// ─── INIT ─────────────────────────────────────────────────────────────────

function init() {
  renderProducts();
  renderInventory();
  updateCartUI();
  setupNavigation();
  
  checkoutBtn.addEventListener('click', handleCheckout);
  clearCartBtn.addEventListener('click', () => { cart = []; updateCartUI(); });
}

// ─── NAVIGATION ───────────────────────────────────────────────────────────

function setupNavigation() {
  navItems.forEach(item => {
    item.addEventListener('click', () => {
      // Update active nav
      navItems.forEach(n => n.classList.remove('active'));
      item.classList.add('active');
      
      // Update view
      const targetView = item.getAttribute('data-target');
      views.forEach(v => v.classList.remove('active'));
      document.getElementById(targetView).classList.add('active');
      
      // Update title
      if (targetView === 'pos-view') topbarTitle.textContent = 'Point of Sale Terminal';
      if (targetView === 'inventory-view') topbarTitle.textContent = 'Inventory Management';
    });
  });
}

// ─── POS LOGIC ────────────────────────────────────────────────────────────

function formatCurrency(amount) {
  return '₱' + parseFloat(amount).toFixed(2);
}

function renderProducts() {
  productsGrid.innerHTML = '';
  MOCK_PRODUCTS.forEach(product => {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.onclick = () => addToCart(product);
    
    card.innerHTML = `
      <div class="product-img-placeholder">${product.emoji}</div>
      <div class="product-title">${product.name}</div>
      <div class="product-price">${formatCurrency(product.price)}</div>
    `;
    productsGrid.appendChild(card);
  });
}

function addToCart(product) {
  const existing = cart.find(item => item.product.id === product.id);
  if (existing) {
    existing.quantity++;
  } else {
    cart.push({ product, quantity: 1 });
  }
  updateCartUI();
}

function updateCartQty(productId, delta) {
  const item = cart.find(item => item.product.id === productId);
  if (!item) return;
  
  item.quantity += delta;
  if (item.quantity <= 0) {
    cart = cart.filter(i => i.product.id !== productId);
  }
  updateCartUI();
}

function updateCartUI() {
  cartItemsContainer.innerHTML = '';
  
  let subtotal = 0;
  
  if (cart.length === 0) {
    cartItemsContainer.innerHTML = '<p style="color:#9ca3af; text-align:center; margin-top:20px; font-weight:500;">Cart is empty</p>';
  } else {
    cart.forEach(item => {
      const lineTotal = item.product.price * item.quantity;
      subtotal += lineTotal;
      
      const el = document.createElement('div');
      el.className = 'cart-item';
      el.innerHTML = `
        <div class="cart-item-header">
          <span class="cart-item-title">${item.product.name}</span>
          <span class="cart-item-price">${formatCurrency(lineTotal)}</span>
        </div>
        <div class="cart-item-controls">
          <div class="stepper">
            <button onclick="updateCartQty('${item.product.id}', -1)">-</button>
            <span>${item.quantity}</span>
            <button onclick="updateCartQty('${item.product.id}', 1)">+</button>
          </div>
        </div>
      `;
      cartItemsContainer.appendChild(el);
    });
  }
  
  const vat = subtotal * 0.12; // Just for display (inclusive)
  
  cartSubtotalEl.textContent = formatCurrency(subtotal - vat);
  cartVatEl.textContent = formatCurrency(vat);
  cartTotalEl.textContent = formatCurrency(subtotal);
  
  checkoutBtn.disabled = cart.length === 0;
  checkoutBtn.style.opacity = cart.length === 0 ? '0.5' : '1';
}

function handleCheckout() {
  if (cart.length === 0) return;
  
  // Deduct inventory based on recipes
  cart.forEach(cartItem => {
    const product = cartItem.product;
    product.recipe.forEach(recipeIng => {
      const invItem = MOCK_INVENTORY.find(i => i.id === recipeIng.id);
      if (invItem) {
        // Multiply recipe qty by how many of this product were sold
        invItem.stock -= (recipeIng.qty * cartItem.quantity); 
      }
    });
  });
  
  // Clear cart and show success
  cart = [];
  updateCartUI();
  renderInventory(); // Re-render inventory to show new values
  
  alert('Order Processed successfully! Inventory has been automatically deducted based on recipes.');
}

// ─── INVENTORY LOGIC ──────────────────────────────────────────────────────

function getStatusBadge(stock, minStock) {
  if (stock <= 0) return '<span class="status-badge status-out">Out of Stock</span>';
  if (stock <= minStock) return '<span class="status-badge status-low">Low Stock</span>';
  return '<span class="status-badge status-good">Good</span>';
}

function renderInventory() {
  invTableBody.innerHTML = '';
  
  let lowCount = 0;
  
  MOCK_INVENTORY.forEach(item => {
    if (item.stock <= item.minStock) lowCount++;
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${item.name}</td>
      <td>${item.unit}</td>
      <td style="font-family: 'Roboto Mono', monospace; font-weight:700;">${item.stock}</td>
      <td>${item.minStock}</td>
      <td>${getStatusBadge(item.stock, item.minStock)}</td>
    `;
    invTableBody.appendChild(tr);
  });
  
  totalIngredientsKpi.textContent = MOCK_INVENTORY.length + ' Items';
  lowStockKpi.textContent = lowCount + ' Warnings';
}

// Start
init();
