import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

function SalesHistoryPage({ user, apiUrl }) {
    const [history, setHistory] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchHistory = useCallback(async () => {
        try {
            setLoading(true);
            const response = await axios.get(`${apiUrl}/sales_history.php?user_id=${user.id}`);
            setHistory(response.data);
        } catch (error) {
            console.error("Error fetching sales history:", error);
        } finally {
            setLoading(false);
        }
    }, [user.id, apiUrl]);

    useEffect(() => {
        fetchHistory();
    }, [fetchHistory]);
    
    const formatCurrency = (value) => `$${parseFloat(value || 0).toFixed(2)}`;

    return (
        <div>
            <h2 className="mb-4">Sales History</h2>
            {loading ? <p>Loading history...</p> : (
            <div className="table-responsive">
                <table className="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {history.length > 0 ? history.map((item, index) => (
                            <tr key={index}>
                                <td>{item.invoice_id}</td>
                                <td>{new Date(item.sale_date).toLocaleString()}</td>
                                <td>{item.product_name}</td>
                                <td>{item.quantity_sold}</td>
                                <td>{formatCurrency(item.sale_price_each)}</td>
                                <td>{formatCurrency(item.total_amount)}</td>
                            </tr>
                        )) : (
                            <tr><td colSpan="6" className="text-center">No sales recorded yet.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            )}
        </div>
    );
}

export default SalesHistoryPage;