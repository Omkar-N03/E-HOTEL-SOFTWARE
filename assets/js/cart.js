let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
});

function addToCart(id, name, price, calories, protein) {
    const itemId = parseInt(id);
    const existingItem = cart.find(item => item.id === itemId);
    
    if (existingItem) {
        existingItem.qty += 1;
    } else {
        cart.push({
            id: itemId,
            name: name,
            price: parseFloat(price),
            calories: parseInt(calories) || 0,
            protein: parseInt(protein) || 0,
            qty: 1
        });
    }
    saveAndRefresh();
    showToast(`Added ${name} to order!`);
}
function clearCart() {
    cart = []; 
    localStorage.removeItem('restaurant_cart'); 
    updateCartUI(); 
}
function updateCartUI() {
    const elements = {
        footer: document.getElementById('cartFooter'),
        count: document.getElementById('cartCount'),
        total: document.getElementById('cartTotal'),
        calories: document.getElementById('totalCalories'),
        protein: document.getElementById('totalProtein')
    };
        let stats = cart.reduce((acc, item) => {
        acc.count += item.qty;
        acc.total += (item.price * item.qty);
        acc.calories += (item.calories * item.qty);
        acc.protein += (item.protein * item.qty);
        return acc;
    }, { count: 0, total: 0, calories: 0, protein: 0 });
    if (elements.calories) elements.calories.innerText = stats.calories;
    if (elements.protein) elements.protein.innerText = `${stats.protein}g`;
    if (elements.footer) {
        if (stats.count > 0) {
            elements.footer.style.display = 'flex';
            
            if (elements.count) {
                elements.count.innerText = `${stats.count} ${stats.count === 1 ? 'Item' : 'Items'} added`;
            }
            
            if (elements.total) {
                elements.total.innerText = stats.total.toFixed(2);
            }
        } else {
            elements.footer.style.display = 'none';
        }
    }
}
function saveAndRefresh() {
    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
    updateCartUI();
}
function showToast(msg) {
    const existingToast = document.querySelector('.cart-toast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.style.cssText = `
        position: fixed; 
        bottom: 100px; 
        left: 50%; 
        transform: translateX(-50%);
        background: #0f172a; 
        color: white; 
        padding: 12px 25px; 
        border-radius: 30px; 
        z-index: 3000; 
        font-size: 0.85rem; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
        transition: all 0.4s ease;
        border: 1px solid #10b981; 
        display: flex; 
        align-items: center;
        white-space: nowrap;
    `;
    
    toast.innerHTML = `<i class="fa fa-check-circle" style="color: #10b981; margin-right:8px;"></i> ${msg}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.bottom = '90px';
        setTimeout(() => toast.remove(), 400);
    }, 2000);
}