import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';

const AddIcon = () => <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>;

function ExpensesPage({ user, apiUrl }) {
    const [summary, setSummary] = useState({ daily: 0, weekly: 0, monthly: 0 });
    const [newExpense, setNewExpense] = useState({ title: '', amount: '' });

    const fetchSummary = useCallback(async () => {
        try {
            const response = await axios.get(`${apiUrl}/expenses.php?action=get_summary&user_id=${user.id}`);
            setSummary(response.data);
        } catch (error) {
            console.error("Error fetching expense summary:", error);
            toast.error("Could not load expense summary.");
        }
    }, [user.id, apiUrl]);

    useEffect(() => {
        fetchSummary();
    }, [fetchSummary]);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setNewExpense({ ...newExpense, [name]: value });
    };

    const handleAddExpense = async (e) => {
        e.preventDefault();
        if (!newExpense.title || !newExpense.amount) {
            return toast.error("Please fill in both fields.");
        }

        const promise = axios.post(`${apiUrl}/expenses.php?action=add_expense`, {
            user_id: user.id,
            title: newExpense.title,
            amount: newExpense.amount,
        });

        toast.promise(promise, {
            loading: 'Adding expense...',
            success: () => {
                fetchSummary(); // Refresh summary after adding
                setNewExpense({ title: '', amount: '' }); // Clear form
                return 'Expense added successfully!';
            },
            error: (err) => err.response?.data?.message || 'Failed to add expense.',
        });
    };

    const formatCurrency = (value) => `$${parseFloat(value || 0).toFixed(2)}`;

    return (
        <div>
            <div className="page-header">
                <h2>Additional Expenses</h2>
            </div>

            <div className="row">
                <div className="col-lg-5 mb-4">
                    <div className="card h-100">
                        <div className="card-header">
                            <h5 className="card-title">Add New Expense</h5>
                        </div>
                        <div className="card-body">
                            <form onSubmit={handleAddExpense}>
                                <div className="mb-3">
                                    <label className="form-label">Expense Title</label>
                                    <input type="text" name="title" className="form-control" placeholder="e.g., Office Rent, Utilities" value={newExpense.title} onChange={handleInputChange} required />
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Amount ($)</label>
                                    <input type="number" step="0.01" name="amount" className="form-control" placeholder="e.g., 500.00" value={newExpense.amount} onChange={handleInputChange} required />
                                </div>
                                <button type="submit" className="btn btn-primary w-100">
                                    <AddIcon /> Add Expense
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div className="col-lg-7 mb-4">
                    <div className="card h-100">
                         <div className="card-header">
                            <h5 className="card-title">Expense Summary</h5>
                        </div>
                        <div className="card-body">
                            <div className="summary-cards">
                                <div className="summary-card">
                                    <h4>Today's Expenses</h4>
                                    <p>{formatCurrency(summary.daily)}</p>
                                </div>
                                <div className="summary-card">
                                    <h4>This Week's Expenses</h4>
                                    <p>{formatCurrency(summary.weekly)}</p>
                                </div>
                                <div className="summary-card">
                                    <h4>This Month's Expenses</h4>
                                    <p>{formatCurrency(summary.monthly)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ExpensesPage;