import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';

// --- SVG Icons ---
const SearchIcon = () => <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>;
const SaleIcon = () => <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>;
const AddIcon = () => <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>;
const PlusIcon = () => <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>;
const TrashIcon = () => <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>;

function InventoryPage({ user, apiUrl }) {
    const [products, setProducts] = useState([]);
    const [productTypes, setProductTypes] = useState([]);
    const [groupedProducts, setGroupedProducts] = useState({});
    const [activeTab, setActiveTab] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [cart, setCart] = useState([]);
    const [newProduct, setNewProduct] = useState({ type_id: '', name: '', quantity: '', wholesale_price: '', sale_price: '' });
    const [imageFile, setImageFile] = useState(null);
    const [newTypeName, setNewTypeName] = useState('');

    // --- NEW: State for the sale modal's active category tab ---
    const [saleCategoryTab, setSaleCategoryTab] = useState('');

    const fetchData = useCallback(async () => {
        if (!user?.id) return;
        try {
            const [productsRes, typesRes] = await Promise.all([
                axios.get(`${apiUrl}/products.php?action=get_products&user_id=${user.id}`),
                axios.get(`${apiUrl}/types.php?action=get_types&user_id=${user.id}`)
            ]);
            
            setProducts(productsRes.data);
            setProductTypes(typesRes.data);

            const filtered = productsRes.data.filter(p => p.name.toLowerCase().includes(searchTerm.toLowerCase()));
            const grouped = filtered.reduce((acc, product) => {
                const typeName = product.type_name || 'Uncategorized'; // Ensure there's a fallback category
                if (!acc[typeName]) acc[typeName] = [];
                acc[typeName].push(product);
                return acc;
            }, {});
            setGroupedProducts(grouped);
            
            const firstCategory = Object.keys(grouped)[0] || '';
            if (!activeTab || !grouped[activeTab]) {
                setActiveTab(firstCategory);
            }
            // --- NEW: Set the initial tab for the sale modal as well ---
            if (!saleCategoryTab || !grouped[saleCategoryTab]) {
                setSaleCategoryTab(firstCategory);
            }

        } catch (error) { 
            console.error("Error fetching data:", error); 
            toast.error("Failed to load inventory data.");
        }
    }, [user.id, apiUrl, searchTerm, activeTab, saleCategoryTab]); // Added saleCategoryTab dependency

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const handleAddProduct = async (e) => {
        e.preventDefault();
        if (!newProduct.type_id) {
            return toast.error("Please select a product type.");
        }
        const formData = new FormData();
        formData.append('type_id', newProduct.type_id);
        formData.append('name', newProduct.name);
        formData.append('quantity', newProduct.quantity);
        formData.append('wholesale_price', newProduct.wholesale_price);
        formData.append('sale_price', newProduct.sale_price);
        formData.append('user_id', user.id);
        if (imageFile) formData.append('image', imageFile);

        const promise = axios.post(`${apiUrl}/products.php?action=add_product`, formData, { headers: { 'Content-Type': 'multipart/form-data' } });

        toast.promise(promise, {
            loading: 'Adding new product...',
            success: () => {
                fetchData();
                setNewProduct({ type_id: '', name: '', quantity: '', wholesale_price: '', sale_price: '' });
                setImageFile(null);
                document.getElementById('addProductModalClose').click();
                return 'Product added successfully!';
            },
            error: 'Failed to add product.',
        });
    };
    
    const handleDeleteProduct = (productId) => {
        const deleteAction = axios.post(`${apiUrl}/products.php?action=delete_product`, { product_id: productId, user_id: user.id });
        toast.promise(deleteAction, {
            loading: 'Archiving product...',
            success: () => { fetchData(); return 'Product archived!'; },
            error: 'Could not archive product.',
        });
    };

    const confirmDelete = (productId) => {
        toast((t) => (
            <div className="toast-confirmation">
                <p>Are you sure you want to archive this product?</p>
                <div>
                    <button className="btn btn-danger" onClick={() => { handleDeleteProduct(productId); toast.dismiss(t.id); }}>Archive</button>
                    <button className="btn btn-secondary" onClick={() => toast.dismiss(t.id)}>Cancel</button>
                </div>
            </div>
        ), { duration: 6000 });
    };

    const handleAddQuantity = async (productId) => {
        const quantity_to_add = parseInt(prompt("How many items to add?"), 10);
        if (!isNaN(quantity_to_add) && quantity_to_add > 0) {
            const promise = axios.post(`${apiUrl}/products.php?action=update_quantity`, { product_id: productId, user_id: user.id, quantity_to_add });
            toast.promise(promise, {
                loading: 'Updating stock...',
                success: () => { fetchData(); return 'Stock updated successfully!'; },
                error: 'Failed to update stock.'
            });
        }
    };
    
    const handleAddNewType = async () => {
        if (!newTypeName.trim()) return toast.error("Type name cannot be empty.");
        
        const promise = axios.post(`${apiUrl}/types.php?action=add_type`, { user_id: user.id, type_name: newTypeName });
        toast.promise(promise, {
            loading: 'Adding new type...',
            success: (response) => {
                const newType = response.data.new_type;
                setProductTypes(prev => [...prev, newType].sort((a, b) => a.type_name.localeCompare(b.type_name)));
                setNewProduct(prev => ({ ...prev, type_id: newType.id }));
                setNewTypeName('');
                document.getElementById('addTypeModalClose').click();
                return 'Type added successfully!';
            },
            error: 'Failed to add type.'
        });
    };

    const addToCart = (product) => {
        setCart(prevCart => {
            const existingProduct = prevCart.find(item => item.id === product.id);
            if (existingProduct) {
                if (existingProduct.quantity >= product.quantity) {
                    toast.error(`No more stock available for ${product.name}.`);
                    return prevCart;
                }
                return prevCart.map(item =>
                    item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item
                );
            }
            return [...prevCart, { ...product, quantity: 1 }];
        });
    };

    const handleSale = async () => {
        if (cart.length === 0) {
            toast.error("Cart is empty!");
            return;
        }
        const promise = axios.post(`${apiUrl}/sales.php`, { cart, user_id: user.id });

        toast.promise(promise, {
            loading: 'Processing sale...',
            success: (response) => {
                const invoiceId = response.data.invoice_id;
                window.open(`${apiUrl}/invoice.php?invoice_id=${invoiceId}&action=print`, '_blank');
                setCart([]);
                fetchData();
                document.getElementById('saleModalClose').click();
                return 'Sale successful!';
            },
            error: (err) => err.response?.data?.message || 'Sale failed: Server error',
        });
    };

    return (
        <div>
            <div className="page-header">
                <h2>Inventory</h2>
                <div className="actions">
                    <button className="btn btn-success" data-bs-toggle="modal" data-bs-target="#saleModal"><SaleIcon /> Make a Sale</button>
                    <button className="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"><AddIcon /> Add New Product</button>
                </div>
            </div>

            <div className="search-bar">
                <SearchIcon />
                <input type="text" placeholder="Search for products by name..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} />
            </div>
            
            <ul className="nav nav-tabs mt-4">
                {Object.keys(groupedProducts).map(type => (
                    <li className="nav-item" key={type}>
                        <a className={`nav-link ${activeTab === type ? 'active' : ''}`} href="#!" onClick={(e) => { e.preventDefault(); setActiveTab(type); }}>{type}</a>
                    </li>
                ))}
            </ul>

            <div className="table-container">
                <table className="table">
                    <thead>
                        <tr><th>Image</th><th>Name</th><th>In Stock</th><th>Wholesale Price</th><th>Sale Price</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        {groupedProducts[activeTab]?.map(product => (
                            <tr key={product.id}>
                                <td><img src={product.image_url ? `${apiUrl}/${product.image_url}` : 'https://via.placeholder.com/60?text=N/A'} alt={product.name} className="product-image-thumb" onError={(e) => { e.target.onerror = null; e.target.src='https://via.placeholder.com/60?text=Error'; }}/></td>
                                <td>{product.name}</td>
                                <td>{product.quantity}</td>
                                <td>${parseFloat(product.wholesale_price).toFixed(2)}</td>
                                <td>${parseFloat(product.sale_price).toFixed(2)}</td>
                                <td>
                                    <div className="action-buttons">
                                        <button className="action-btn btn-add" title="Add Stock" onClick={() => handleAddQuantity(product.id)}><PlusIcon /></button>
                                        <button className="action-btn btn-delete" title="Archive Product" onClick={() => confirmDelete(product.id)}><TrashIcon /></button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                 {products.length === 0 && <p className="text-center p-4">Your inventory is empty. Add a product to get started!</p>}
            </div>

            {/* Add Product Modal */}
            <div className="modal fade" id="addProductModal" tabIndex="-1">
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">Add New Product</h5>
                            <button type="button" className="btn-close" id="addProductModalClose" data-bs-dismiss="modal"></button>
                        </div>
                        <form onSubmit={handleAddProduct}>
                            <div className="modal-body">
                                <label className="form-label">Product Type</label>
                                <div className="input-group mb-3">
                                    <select className="form-select" name="type_id" value={newProduct.type_id} onChange={(e) => setNewProduct({...newProduct, type_id: e.target.value})} required>
                                        <option value="" disabled>Select a type...</option>
                                        {productTypes.map(type => (<option key={type.id} value={type.id}>{type.type_name}</option>))}
                                    </select>
                                    <button className="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#addTypeModal">+</button>
                                </div>
                                <input type="text" name="name" value={newProduct.name} onChange={e => setNewProduct({...newProduct, name: e.target.value})} className="form-control mb-3" placeholder="Product Name" required />
                                <div className="row">
                                    <div className="col"><input type="number" name="quantity" value={newProduct.quantity} onChange={e => setNewProduct({...newProduct, quantity: e.target.value})} className="form-control mb-3" placeholder="Quantity" required /></div>
                                    <div className="col"><input type="number" step="0.01" name="sale_price" value={newProduct.sale_price} onChange={e => setNewProduct({...newProduct, sale_price: e.target.value})} className="form-control mb-3" placeholder="Sale Price" required /></div>
                                </div>
                                <input type="number" step="0.01" name="wholesale_price" value={newProduct.wholesale_price} onChange={e => setNewProduct({...newProduct, wholesale_price: e.target.value})} className="form-control mb-3" placeholder="Wholesale Price" required />
                                <label className="form-label">Product Image (Optional)</label>
                                <input type="file" name="image" onChange={e => setImageFile(e.target.files[0])} className="form-control" accept="image/*" />
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" className="btn btn-primary">Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* Add New Type Modal */}
            <div className="modal fade" id="addTypeModal" tabIndex="-1">
                <div className="modal-dialog modal-sm">
                    <div className="modal-content">
                        <div className="modal-header"><h5 className="modal-title">Add New Type</h5><button type="button" className="btn-close" id="addTypeModalClose" data-bs-dismiss="modal"></button></div>
                        <div className="modal-body">
                            <label className="form-label">New Type Name</label>
                            <input type="text" className="form-control" value={newTypeName} onChange={(e) => setNewTypeName(e.target.value)} placeholder="e.g., Electronics" />
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-primary" onClick={handleAddNewType}>Save Type</button>
                        </div>
                    </div>
                </div>
            </div>
            
            {/* ========== CORRECTED SALE MODAL ========== */}
            <div className="modal fade" id="saleModal" tabIndex="-1">
                <div className="modal-dialog modal-lg modal-dialog-scrollable"> {/* Added modal-dialog-scrollable for better mobile view */}
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">Create Sale</h5>
                            <button type="button" className="btn-close" id="saleModalClose" data-bs-dismiss="modal"></button>
                        </div>
                        <div className="modal-body">
                           <div className="row">
                                {/* Products Column */}
                                <div className="col-lg-7 mb-4 mb-lg-0">
                                    <h5>Available Products</h5>
                                    {/* Category Tabs for the Sale Modal */}
                                    <ul className="nav nav-pills flex-nowrap overflow-auto mb-3"> {/* flex-nowrap and overflow-auto for mobile */}
                                        {Object.keys(groupedProducts).map(type => (
                                            <li className="nav-item" key={`sale-tab-${type}`}>
                                                <a 
                                                   className={`nav-link ${saleCategoryTab === type ? 'active' : ''}`} 
                                                   href="#!" 
                                                   onClick={(e) => { e.preventDefault(); setSaleCategoryTab(type); }}>
                                                   {type}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>

                                    {/* Product List based on selected category */}
                                    <div className="list-group" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                                        {groupedProducts[saleCategoryTab]?.filter(p => p.quantity > 0).map(p => (
                                            <button key={p.id} type="button" className="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onClick={() => addToCart(p)}>
                                                <span>{p.name} (${parseFloat(p.sale_price).toFixed(2)})</span>
                                                <span className="badge bg-secondary rounded-pill">Stock: {p.quantity}</span>
                                            </button>
                                        ))}
                                        {(!groupedProducts[saleCategoryTab] || groupedProducts[saleCategoryTab].filter(p => p.quantity > 0).length === 0) &&
                                            <p className="text-center text-muted p-3">No products in this category.</p>
                                        }
                                    </div>
                                </div>
                                
                                {/* Cart Column */}
                                <div className="col-lg-5">
                                    <h5>Cart</h5>
                                    {cart.length === 0 ? <p className="text-muted">Cart is empty.</p> :
                                    <ul className="list-group">
                                        {cart.map(item => (
                                            <li key={item.id} className="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    {item.name}
                                                    <small className="d-block text-muted">x {item.quantity}</small>
                                                </div>
                                                <span>${(item.quantity * item.sale_price).toFixed(2)}</span>
                                            </li>
                                        ))}
                                    </ul>
                                    }
                                    <h4 className="mt-3 text-end">Total: ${cart.reduce((total, item) => total + (item.quantity * item.sale_price), 0).toFixed(2)}</h4>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={() => setCart([])}>Clear Cart</button>
                            <button type="button" className="btn btn-primary" onClick={handleSale}>Complete Sale & Print Invoice</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default InventoryPage;