import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

function AnalyticsPage({ user }) { // Removed unused apiUrl prop
    const [period, setPeriod] = useState('daily');
    const [analyticsData, setAnalyticsData] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchAnalytics = useCallback(async () => {
        if (!user?.id) return;
        setLoading(true);

        // Get the live API URL from the environment variable set in Vercel
        const liveApiUrl = process.env.REACT_APP_API_URL;

        try {
            const response = await axios.get(`${liveApiUrl}/analytics.php?user_id=${user.id}&period=${period}`);
            setAnalyticsData(response.data);
        } catch (error) {
            console.error("Error fetching analytics:", error);
        } finally {
            setLoading(false);
        }
    }, [user.id, period]);

    useEffect(() => {
        fetchAnalytics();
    }, [fetchAnalytics]);

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: `Product Sales (${period})` },
        },
    };
    
    const chartData = {
        labels: analyticsData?.chartData.map(d => d.product_name) || [],
        datasets: [{
            label: 'Quantity Sold',
            data: analyticsData?.chartData.map(d => d.total_quantity) || [],
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
        }],
    };

    return (
        <div>
            <div className="page-header">
                <h2>Product Analytics</h2>
                <div className="actions">
                    <button className={`btn ${period === 'daily' ? 'btn-primary' : 'btn-secondary'}`} onClick={() => setPeriod('daily')}>Daily</button>
                    <button className={`btn ${period === 'weekly' ? 'btn-primary' : 'btn-secondary'}`} onClick={() => setPeriod('weekly')}>Weekly</button>
                    <button className={`btn ${period === 'monthly' ? 'btn-primary' : 'btn-secondary'}`} onClick={() => setPeriod('monthly')}>Monthly</button>
                </div>
            </div>

            {loading ? <p>Loading analytics...</p> :
            (
                <>
                    <div className="row mb-4">
                        <div className="col-md-6">
                            <div className="card h-100">
                                <div className="card-body text-center">
                                    <h5 className="card-title">Highest Selling Product ({period})</h5>
                                    {analyticsData?.highestProduct ? (
                                        <>
                                            <h3>{analyticsData.highestProduct.product_name}</h3>
                                            <p className="fs-4">{analyticsData.highestProduct.total_quantity} units sold</p>
                                        </>
                                    ) : <p>No sales data for this period.</p>}
                                </div>
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="card h-100">
                                <div className="card-body text-center">
                                    <h5 className="card-title">Lowest Selling Product ({period})</h5>
                                    {analyticsData?.lowestProduct ? (
                                        <>
                                            <h3>{analyticsData.lowestProduct.product_name}</h3>
                                            <p className="fs-4">{analyticsData.lowestProduct.total_quantity} units sold</p>
                                        </>
                                    ) : <p>No sales data for this period.</p>}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body">
                           {analyticsData?.chartData.length > 0 ? <Bar options={chartOptions} data={chartData} /> : <p className="text-center">No chart data available for this period.</p>}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

export default AnalyticsPage;