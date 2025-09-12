import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

function ReportsPage({ user, apiUrl }) {
    const [reportData, setReportData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [filter, setFilter] = useState('daily');
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));

    const fetchReports = useCallback(async () => {
        setIsLoading(true);
        try {
            const response = await axios.get(`${apiUrl}/reports.php?user_id=${user.id}&filter=${filter}&month=${month}`);
            setReportData(response.data);
        } catch (error) {
            console.error("Error fetching reports:", error);
            setReportData(null);
        } finally {
            setIsLoading(false);
        }
    }, [user.id, apiUrl, filter, month]);

    useEffect(() => {
        fetchReports();
    }, [fetchReports]);
    
    const handleReset = () => {
        if (window.confirm("This will reset the filters to the default daily report. Are you sure?")) {
            setFilter('daily');
            setMonth(new Date().toISOString().slice(0, 7));
        }
    };

    const formatCurrency = (value) => `$${parseFloat(value || 0).toFixed(2)}`;

    return (
        <div>
            <div className="page-header">
                <h2>Reports</h2>
            </div>

            <div className="card p-3 mb-4">
                <div className="row g-3 align-items-center">
                    <div className="col-auto">
                        <label className="col-form-label">Report Type:</label>
                    </div>
                    <div className="col-auto">
                        <select className="form-select" value={filter} onChange={(e) => setFilter(e.target.value)}>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    {filter === 'monthly' && (
                        <div className="col-auto">
                            <input type="month" className="form-control" value={month} onChange={(e) => setMonth(e.target.value)} />
                        </div>
                    )}
                    <div className="col-auto">
                        <button className="btn btn-secondary" onClick={handleReset}>Reset Filters</button>
                    </div>
                </div>
            </div>

            {isLoading ? <p>Generating report...</p> : (
            <div className="row">
                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h5 className="card-title">Sales Report ({filter === 'monthly' ? month : filter})</h5></div>
                        <div className="card-body">
                            {reportData?.sales_report ? (
                                <>
                                    <p><strong>Total Sales:</strong> {formatCurrency(reportData.sales_report.total_sales)}</p>
                                    <p><strong>Cost of Goods Sold:</strong> {formatCurrency(reportData.sales_report.total_investment)}</p>
                                    <hr/>
                                    <h4 className="text-success"><strong>Profit:</strong> {formatCurrency(reportData.sales_report.total_profit)}</h4>
                                </>
                            ) : <p>No sales data for this period.</p>}
                        </div>
                    </div>
                </div>
                
                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h5 className="card-title">Investment Overview</h5></div>
                        <div className="card-body">
                             {reportData ? (
                                <>
                                    <p><strong>Value of Current Stock:</strong> {formatCurrency(reportData.current_stock_value)}</p>
                                    <hr/>
                                    <p><strong>Monthly Investment ({month}):</strong> {formatCurrency(reportData.monthly_investment)}</p>
                                    <p><strong>Total Investment (All-Time):</strong> {formatCurrency(reportData.total_investment)}</p>
                                </>
                            ) : <p>No investment data available.</p>}
                        </div>
                    </div>
                </div>
            </div>
            )}
        </div>
    );
}

export default ReportsPage;