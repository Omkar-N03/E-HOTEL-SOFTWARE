const POLL_INTERVAL = 10000;
const hotelId = document.body.dataset.hotelId;

async function refreshKitchenBoard() {
    try {
        const response = await fetch(`../api/get-live-orders.php?hotel_id=${hotelId}`);
        const data = await response.json();
        if (data.success) {
            renderOrders(data.orders);
            checkForNewOrders(data.orders);
        }
    } catch (error) {
        console.error("Kitchen Sync Error:", error);
    }
}

function renderOrders(orders) {
    const grid = document.querySelector('.kitchen-grid');
    if (!grid) return;
    if (orders.length === 0) {
        grid.innerHTML = `
            <div class="no-orders">
                <i class="fa fa-check-circle"></i>
                <h2>All clear! No pending orders.</h2>
            </div>`;
        return;
    }
    const orderHTML = orders.map(order => {
        const elapsed = calculateMinutes(order.order_time);
        return `
            <div class="order-card ${order.status}" id="order-${order.id}">
                <div class="order-header">
                    <span class="table-badge">TABLE ${order.table_number}</span>
                    <span class="time-ago"><i class="fa fa-clock"></i> ${elapsed}m ago</span>
                </div>
                <div class="order-items">
                    ${formatItems(order.items_summary)}
                </div>
                <div class="order-actions">
                    ${renderActionButton(order)}
                </div>
            </div>
        `;
    }).join('');
    grid.innerHTML = orderHTML;
}

function formatItems(summary) {
    const items = summary.split(',');
    return items.map(item => `
        <div class="item-row">
            <span>${item.trim()}</span>
        </div>
    `).join('');
}

function renderActionButton(order) {
    if (order.status === 'pending') {
        return `<button onclick="updateStatus(${order.id}, 'preparing')" class="btn-kitchen btn-prep">START COOKING</button>`;
    } else {
        return `<button onclick="updateStatus(${order.id}, 'served')" class="btn-kitchen btn-serve">MARK AS SERVED</button>`;
    }
}

async function updateStatus(orderId, newStatus) {
    const formData = new FormData();
    formData.append('id', orderId);
    formData.append('status', newStatus);
    try {
        const response = await fetch('../api/update-order-status.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            refreshKitchenBoard();
            if(newStatus === 'preparing') playSound('start');
        }
    } catch (error) {
        alert("Action failed. Check connection.");
    }
}

let lastOrderCount = 0;
function checkForNewOrders(orders) {
    const currentPending = orders.filter(o => o.status === 'pending').length;
    if (currentPending > lastOrderCount) {
        playSound('new_order');
    }
    lastOrderCount = currentPending;
}

function playSound(type) {
    const audio = new Audio(`../assets/audio/${type}.mp3`);
    audio.play().catch(e => console.log("Audio requires user interaction first."));
}

function calculateMinutes(timestamp) {
    const start = new Date(timestamp);
    const now = new Date();
    return Math.floor((now - start) / 60000);
}

setInterval(refreshKitchenBoard, POLL_INTERVAL);
