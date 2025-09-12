import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';

// FIX: Accept setNotificationCount as a prop
function NotificationsPage({ user, apiUrl, setNotificationCount }) {
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);

    // FIX: Add a useEffect to clear the count when the page is viewed
    useEffect(() => {
        // This function is passed down from App.js
        setNotificationCount(0);
    }, [setNotificationCount]);

    const fetchNotifications = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axios.get(`${apiUrl}/notifications.php?action=get_all&user_id=${user.id}`);
            setNotifications(response.data);
        } catch (error) {
            console.error("Error fetching notifications:", error);
            toast.error("Could not load notifications.");
        } finally {
            setLoading(false);
        }
    }, [user.id, apiUrl]);

    useEffect(() => {
        fetchNotifications();
    }, [fetchNotifications]);

    return (
        <div>
            <div className="page-header">
                <h2>Stock Notifications</h2>
            </div>
            <div className="card">
                <div className="card-body">
                    {loading ? <p>Loading notifications...</p> : (
                        notifications.length > 0 ? (
                            <ul className="list-group list-group-flush">
                                {notifications.map((item, index) => (
                                    <li key={index} className="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{item.name}</strong>
                                            <p className="mb-0 text-secondary">Current stock: {item.quantity}</p>
                                        </div>
                                        {item.quantity <= 0 ? (
                                            <span className="badge bg-danger">Out of Stock</span>
                                        ) : (
                                            <span className="badge bg-warning text-dark">Low Stock</span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-center text-secondary p-4">No low stock notifications. Everything looks good!</p>
                        )
                    )}
                </div>
            </div>
        </div>
    );
}

export default NotificationsPage;