let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];

document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
});

function addToCart(id, name, price, calories, protein) {
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        existingItem.qty += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            calories: parseInt(calories),
            protein: parseInt(protein),
            qty: 1
        });
    }
    saveAndRefresh();
    showToast(`${name} added to order!`);
}

function updateCartUI() {
    const cartFooter = document.getElementById('cartFooter');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    const totalCalories = document.getElementById('totalCalories');
    const totalProtein = document.getElementById('totalProtein');
    let count = 0;
    let total = 0;
    let calories = 0;
    let protein = 0;
    cart.forEach(item => {
        count += item.qty;
        total += (item.price * item.qty);
        calories += (item.calories * item.qty);
        protein += (item.protein * item.qty);
    });
    if (totalCalories) totalCalories.innerText = calories;
    if (totalProtein) totalProtein.innerText = protein;
    if (cartFooter) {
        if (count > 0) {
            cartFooter.style.display = 'flex';
            if (cartCount) cartCount.innerText = `${count} Items`;
            if (cartTotal) cartTotal.innerText = `Total: ${total.toFixed(2)}`;
        } else {
            cartFooter.style.display = 'none';
        }
    }
}

function saveAndRefresh() {
    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
    updateCartUI();
}

function showToast(msg) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%);
        background: #333; color: white; padding: 10px 20px; border-radius: 20px;
        z-index: 2000; font-size: 0.9rem; transition: opacity 0.5s;
    `;
    toast.innerText = msg;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 2000);
}
