<div id="cartDrawerBackdrop" onclick="toggleCartDrawer()" class="fixed inset-0 bg-slate-900/30 z-[90] hidden transition-opacity duration-300 opacity-0 backdrop-blur-[2px]"></div>

<div id="cartDrawer" class="fixed top-0 right-0 h-full w-full md:w-[450px] bg-white z-[100] transform translate-x-full transition-transform duration-300 ease-[cubic-bezier(0.25,1,0.5,1)] shadow-2xl flex flex-col font-sans">
    
    <div class="px-6 py-5 bg-white border-b border-gray-50 flex justify-between items-center z-20">
        <h2 class="text-lg font-black tracking-tight text-slate-900 flex items-center gap-2">
            YOUR CART
            <span id="headerCartCount" class="bg-black text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                <?php
                    $total_count = 0;
                    if(isset($user_id)){
                        $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $total_count = $stmt->rowCount();
                    }
                    echo $total_count;
                ?>
            </span>
        </h2>
        <button onclick="toggleCartDrawer()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors cursor-pointer group">
            <i class="fas fa-times text-gray-400 group-hover:text-black text-lg"></i>
        </button>
    </div>

    <?php if($total_count > 0): ?>
    <div class="px-6 py-3 bg-gray-50 flex justify-between items-center z-10" id="cartActionsHeader">
        <label class="flex items-center gap-3 cursor-pointer select-none group">
            <div class="relative flex items-center">
                <input type="checkbox" id="selectAllCart" class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-gray-400 bg-transparent checked:bg-black checked:border-black transition-all" onchange="toggleAllItems(this)">
                <i class="fas fa-check absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-[9px] opacity-0 peer-checked:opacity-100 pointer-events-none"></i>
            </div>
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-widest group-hover:text-black transition-colors">Select All</span>
        </label>
        
        <button onclick="clearCart()" class="text-[10px] font-bold text-gray-400 hover:text-red-600 transition-colors uppercase tracking-widest">
            Clear Cart
        </button>
    </div>
    <?php endif; ?>

    <form id="mainCheckoutForm" action="checkout.php" method="POST" class="flex-1 flex flex-col overflow-hidden relative">
        <div class="flex-1 overflow-y-auto px-6 py-2 space-y-0" id="cartContainer">
            <?php
            $has_items = false;
            if(isset($user_id)) {
                $select_cart = $conn->prepare("SELECT c.*, p.details, p.discount_percentage, p.variants AS raw_product_variants 
                                             FROM cart c 
                                             LEFT JOIN products p ON c.pid = p.id 
                                             WHERE c.user_id = ? ORDER BY c.id DESC");
                $select_cart->execute([$user_id]);

                if ($select_cart->rowCount() > 0) {
                    $has_items = true;
                    while ($item = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                        $price = $item['price'];
                        
                        // PARSE VARIANTS
                        $raw_variants = $item['raw_product_variants'] ?? '';
                        $variant_options = [];
                        if(!empty($raw_variants)) {
                            if(strpos($raw_variants, '__') !== false) {
                                $rows = explode('||', $raw_variants);
                                foreach($rows as $r) { $c = explode('__', $r); if(isset($c[1])) $variant_options[] = $c[1]; }
                            } else {
                                $groups = explode(';', $raw_variants);
                                foreach($groups as $g){
                                    $parts = explode(':', $g);
                                    if(count($parts)==2) {
                                        $opts = explode(',', $parts[1]);
                                        foreach($opts as $o) $variant_options[] = trim($o);
                                    }
                                }
                            }
                        }
            ?>
            
            <div class="cart-item-row group relative flex gap-4 py-6 border-b border-gray-100 last:border-0" id="item-<?= $item['id']; ?>">
                
                <div class="flex items-center">
                     <div class="relative flex items-center">
                        <input type="checkbox" name="selected_items[]" value="<?= $item['id']; ?>" 
                               data-price="<?= $price; ?>" 
                               data-qty="<?= $item['quantity']; ?>"
                               class="cart-item-check peer h-4 w-4 cursor-pointer appearance-none rounded border border-gray-300 bg-white checked:bg-black checked:border-black transition-all"
                               onchange="updateTotalDisplay()">
                        <i class="fas fa-check absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-[9px] opacity-0 peer-checked:opacity-100 pointer-events-none"></i>
                    </div>
                </div>

                <div class="w-24 h-24 bg-gray-50 rounded-lg flex-shrink-0 relative overflow-hidden group/img border border-gray-100">
                    <img src="uploaded_img/<?= $item['image']; ?>" class="w-full h-full object-contain mix-blend-multiply p-2" alt="<?= $item['name']; ?>">
                    
                    <a href="view_page.php?pid=<?= $item['pid']; ?>" class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-all duration-200 cursor-pointer z-10">
                        <i class="fas fa-eye text-white bg-black/20 p-2 rounded-full backdrop-blur-sm text-sm"></i>
                    </a>
                </div>

                <div class="flex-1 flex flex-col justify-between py-0.5 min-w-0">
                    
                    <h3 class="text-sm font-black text-slate-900 leading-snug truncate pr-2"><?= $item['name']; ?></h3>

                    <div class="flex justify-between items-center my-2">
                        
                        <?php if(!empty($variant_options)): ?>
                            <div class="relative inline-block text-left max-w-[120px]">
                                <select class="appearance-none bg-gray-100 hover:bg-gray-200 border-none text-gray-800 text-[10px] font-bold rounded py-1 pl-2 pr-6 focus:ring-0 cursor-pointer uppercase tracking-wide transition-colors w-full truncate"
                                        onchange="updateVariant(<?= $item['id']; ?>, this.value)">
                                    <?php 
                                        $curr_val = $item['selected_variants'];
                                        echo "<option value='$curr_val'>$curr_val</option>";
                                        foreach($variant_options as $opt) {
                                            if(strpos($curr_val, $opt) === false) echo "<option value='$opt'>$opt</option>";
                                        }
                                    ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1.5 text-gray-500">
                                    <i class="fas fa-chevron-down text-[8px]"></i>
                                </div>
                            </div>
                        <?php else: ?>
                             <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Standard</span>
                        <?php endif; ?>

                        <div class="text-sm font-black text-slate-900 whitespace-nowrap">
                            <span class="text-[9px] font-bold text-gray-400 mr-0.5">RM</span><?= number_format($price, 2); ?>
                        </div>
                    </div>

                    <div class="flex justify-between items-end">
                        <button type="button" onclick="deleteItem(<?= $item['id']; ?>)" class="text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1 group/del p-1 -ml-1">
                            <i class="far fa-trash-alt text-xs"></i>
                            <span class="text-[9px] font-bold opacity-0 group-hover/del:opacity-100 transition-opacity -ml-1">Remove</span>
                        </button>

                        <div class="flex items-center border border-gray-200 rounded h-7">
                            <button type="button" onclick="changeQty(<?= $item['id']; ?>, -1)" class="w-6 h-full flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 rounded-l transition-colors">
                                <i class="fas fa-minus text-[8px]"></i>
                            </button>
                            <input type="number" readonly value="<?= $item['quantity']; ?>" id="qty-<?= $item['id']; ?>" class="w-8 text-center text-[11px] font-bold bg-transparent border-none p-0 text-slate-900 focus:ring-0">
                            <button type="button" onclick="changeQty(<?= $item['id']; ?>, 1)" class="w-6 h-full flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 rounded-r transition-colors">
                                <i class="fas fa-plus text-[8px]"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            
            <?php 
                    } 
                } else {
                    // EMPTY STATE
                    echo '<div class="h-full flex flex-col items-center justify-center text-center opacity-40" id="emptyCartMsg">
                            <h3 class="text-2xl font-black text-gray-300 mb-2 tracking-tight">EMPTY</h3>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Your cart is feeling light</p>
                            <button type="button" onclick="toggleCartDrawer()" class="mt-6 px-6 py-2 border-2 border-black text-black text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-all">
                                Start Shopping
                            </button>
                          </div>';
                }
            } else {
                 echo '<div class="h-full flex flex-col items-center justify-center text-center p-8">
                        <p class="text-sm font-bold text-gray-600 mb-4">Please login to view cart.</p>
                        <a href="login.php" class="px-6 py-3 bg-black text-white rounded text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">Login</a>
                      </div>';
            }
            ?>
        </div>

        <?php if ($has_items): ?>
        <div class="p-6 bg-white border-t border-gray-100 z-20" id="cartFooter">
            <div class="flex justify-between items-end mb-4">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1" id="totalSelectedLabel">TOTAL (0 ITEMS)</span>
                <span class="text-2xl font-black text-slate-900 tracking-tight leading-none" id="displayGrandTotal">RM0.00</span>
            </div>
            
            <button type="submit" id="checkoutBtn" disabled class="w-full py-4 bg-black text-white text-xs font-bold uppercase tracking-widest hover:bg-gray-900 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition-all flex justify-center items-center gap-2 shadow-lg">
                <span style="color: #ffffff !important;">Checkout</span>
                <i class="fas fa-arrow-right" style="color: #ffffff !important;"></i>
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
    const ajaxUrl = 'cart_ajax.php';

    function toggleCartDrawer() {
        const drawer = document.getElementById('cartDrawer');
        const backdrop = document.getElementById('cartDrawerBackdrop');
        const body = document.body;
        
        if (drawer.classList.contains('translate-x-full')) {
            backdrop.classList.remove('hidden');
            body.style.overflow = 'hidden'; 
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                drawer.classList.remove('translate-x-full');
            }, 10);
            
            const anyChecked = document.querySelector('.cart-item-check:checked');
            const selectAll = document.getElementById('selectAllCart');
            if(!anyChecked && selectAll) { selectAll.checked = true; toggleAllItems(selectAll); }
        } else {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0');
            body.style.overflow = '';
            setTimeout(() => { backdrop.classList.add('hidden'); }, 300);
        }
    }

    function updateVariant(cartId, newVal) {
        const formData = new FormData();
        formData.append('action', 'update_variant');
        formData.append('cart_id', cartId);
        formData.append('variant', newVal);

        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.status !== 'success') console.error('Variant update failed'); });
    }

    function deleteItem(cartId) {
        if(!confirm('Remove item?')) return;
        const row = document.getElementById(`item-${cartId}`);
        row.style.opacity = '0.5';
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('cart_id', cartId);

        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                row.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    row.remove();
                    updateTotalDisplay();
                    checkEmptyState(data.cart_count);
                    updateHeaderBadge(data.cart_count);
                }, 300);
            }
        });
    }

    function clearCart() {
        if(!confirm('Clear cart?')) return;
        const formData = new FormData();
        formData.append('action', 'delete_all');
        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('cartContainer').innerHTML = getEmptyHtml();
                const h = document.getElementById('cartActionsHeader'); if(h) h.remove();
                const f = document.getElementById('cartFooter'); if(f) f.remove();
                updateHeaderBadge(0);
            }
        });
    }

    let debounceTimer;
    function changeQty(cartId, change) {
        const input = document.getElementById(`qty-${cartId}`);
        const checkbox = document.querySelector(`input[value="${cartId}"]`);
        let currentQty = parseInt(input.value);
        let newQty = currentQty + change;
        if(newQty < 1) return; 

        input.value = newQty;
        checkbox.setAttribute('data-qty', newQty);
        updateTotalDisplay(); 

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const formData = new FormData();
            formData.append('action', 'update_qty');
            formData.append('cart_id', cartId);
            formData.append('qty', newQty);
            fetch(ajaxUrl, { method: 'POST', body: formData });
        }, 500);
    }

    function toggleAllItems(source) {
        const checkboxes = document.querySelectorAll('.cart-item-check');
        checkboxes.forEach(cb => { cb.checked = source.checked; });
        updateTotalDisplay();
    }

    function updateTotalDisplay() {
        const checkboxes = document.querySelectorAll('.cart-item-check:checked');
        const totalDisplay = document.getElementById('displayGrandTotal');
        const totalSelectedLabel = document.getElementById('totalSelectedLabel');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const selectAllBox = document.getElementById('selectAllCart');
        
        let total = 0;
        let count = 0;
        checkboxes.forEach(cb => {
            const price = parseFloat(cb.getAttribute('data-price'));
            const qty = parseInt(cb.getAttribute('data-qty'));
            total += (price * qty);
            count++;
        });

        // 1. Update Price
        totalDisplay.innerText = "RM" + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // 2. Update Label with Count
        if(totalSelectedLabel) {
            totalSelectedLabel.innerText = `TOTAL (${count} ITEM${count !== 1 ? 'S' : ''})`;
        }

        // 3. Update Button
        if (count > 0) {
            checkoutBtn.disabled = false;
            checkoutBtn.querySelector('span').innerText = `CHECKOUT (${count})`;
        } else {
            checkoutBtn.disabled = true;
            checkoutBtn.querySelector('span').innerText = `CHECKOUT`;
        }
        
        const allCheckboxes = document.querySelectorAll('.cart-item-check');
        if(selectAllBox && allCheckboxes.length > 0) {
             selectAllBox.checked = (count === allCheckboxes.length && count > 0);
        }
    }

    function updateHeaderBadge(count) {
        const badge = document.getElementById('headerCartCount');
        if(badge) badge.innerText = count;
    }

    function checkEmptyState(count) {
        if(count == 0) {
            document.getElementById('cartContainer').innerHTML = getEmptyHtml();
            const h = document.getElementById('cartActionsHeader'); if(h) h.remove();
            const f = document.getElementById('cartFooter'); if(f) f.remove();
        }
    }

    function getEmptyHtml() {
        return `<div class="h-full flex flex-col items-center justify-center text-center opacity-40">
                    <h3 class="text-2xl font-black text-gray-300 mb-2 tracking-tight">EMPTY</h3>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Your cart is feeling light</p>
                    <button type="button" onclick="toggleCartDrawer()" class="mt-6 px-6 py-2 border-2 border-black text-black text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-all">
                        Start Shopping
                    </button>
                </div>`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAllCart');
        if(selectAll) selectAll.click();
    });
</script>