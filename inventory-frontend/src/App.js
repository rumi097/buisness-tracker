import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, NavLink, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import axios from 'axios';
import LoginPage from './LoginPage';
import RegisterPage from './RegisterPage';
import InventoryPage from './InventoryPage';
import ReportsPage from './ReportsPage';
import SalesHistoryPage from './SalesHistoryPage';
import ExpensesPage from './ExpensesPage';
import AnalyticsPage from './AnalyticsPage';
import NotificationsPage from './NotificationsPage';
import './App.css'; 

// --- Icons for the Sidebar ---
const InventoryIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>;
const HistoryIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>;
const ReportsIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 12h-5"></path><path d="M12 17V7"></path><path d="M2 12h5"></path><path d="M7 2v20"></path><path d="M12 2v3"></path><path d="M12 20v2"></path><path d="M17 2v8"></path><path d="M17 17v5"></path><path d="M5 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0z"></path><path d="M12 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0z"></path><path d="M17 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0z"></path></svg>;
const ExpensesIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M19 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"></path><path d="M15 9h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3H9"></path><path d="M12 15V9"></path></svg>;
const AnalyticsIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>;
const BellIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>;
const MenuIcon = () => <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>;

function App() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notificationCount, setNotificationCount] = useState(0);
    const [isSidebarOpen, setIsSidebarOpen] = useState(false); // State for mobile sidebar
    const API_BASE_URL = 'http://localhost/inventory-api';

    useEffect(() => {
        try {
            const loggedInUser = localStorage.getItem("user");
            if (loggedInUser) setUser(JSON.parse(loggedInUser));
        } catch (error) { localStorage.removeItem("user"); }
        setLoading(false);
    }, []);
    
    useEffect(() => {
        document.body.className = user ? 'theme-main' : 'theme-auth';
        if (user) {
            const fetchNotifCount = async () => {
                try {
                    const response = await axios.get(`${API_BASE_URL}/notifications.php?action=get_count&user_id=${user.id}`);
                    setNotificationCount(response.data.low_stock_count);
                } catch (error) { console.error("Could not fetch notification count."); }
            };
            fetchNotifCount();
        }
    }, [user]);

    const handleLogout = () => {
        setUser(null);
        localStorage.removeItem("user");
    };
    
    if (loading) return <div className="loading-fullscreen"></div>;

    return (
        <Router>
            <Toaster position="top-center" reverseOrder={false} />
            { !user ? (
                <Routes>
                    <Route path="/login" element={<LoginPage setUser={setUser} apiUrl={API_BASE_URL} />} />
                    <Route path="/register" element={<RegisterPage apiUrl={API_BASE_URL} />} />
                    <Route path="*" element={<Navigate to="/login" />} />
                </Routes>
            ) : (
                <div className="app-layout">
                    <div className={`sidebar-overlay ${isSidebarOpen ? 'open' : ''}`} onClick={() => setIsSidebarOpen(false)}></div>
                    <nav className={`sidebar ${isSidebarOpen ? 'open' : ''}`}>
                        <div className="sidebar-header">
                            🏢 {user.store_name}
                        </div>
                        <ul className="sidebar-nav">
                            {/* Close sidebar on link click */}
                            <li onClick={() => setIsSidebarOpen(false)}><NavLink to="/inventory"><InventoryIcon /><span>Inventory</span></NavLink></li>
                            <li onClick={() => setIsSidebarOpen(false)}><NavLink to="/sales-history"><HistoryIcon /><span>Sales History</span></NavLink></li>
                            <li onClick={() => setIsSidebarOpen(false)}><NavLink to="/analytics"><AnalyticsIcon /><span>Analytics</span></NavLink></li>
                            <li onClick={() => setIsSidebarOpen(false)}><NavLink to="/reports"><ReportsIcon /><span>Reports</span></NavLink></li>
                            <li onClick={() => setIsSidebarOpen(false)}><NavLink to="/expenses"><ExpensesIcon /><span>Expenses</span></NavLink></li>
                            <li onClick={() => setIsSidebarOpen(false)}>
                                <NavLink to="/notifications">
                                    <BellIcon />
                                    <span>Notifications</span>
                                    {notificationCount > 0 && <span className="notification-badge">{notificationCount}</span>}
                                </NavLink>
                            </li>
                        </ul>
                        <div className="sidebar-footer">
                            <button className="btn-logout" onClick={handleLogout}>Logout</button>
                        </div>
                    </nav>

                    <div className="main-content-wrapper">
                        <header className="mobile-header">
                            <button className="menu-btn" onClick={() => setIsSidebarOpen(true)}>
                                <MenuIcon />
                            </button>
                            <div className="mobile-header-title">🏢 {user.store_name}</div>
                        </header>
                        <main className="main-content">
                            <Routes>
                                <Route path="/inventory" element={<InventoryPage user={user} apiUrl={API_BASE_URL} />} />
                                <Route path="/sales-history" element={<SalesHistoryPage user={user} apiUrl={API_BASE_URL} />} />
                                <Route path="/analytics" element={<AnalyticsPage user={user} apiUrl={API_BASE_URL} />} />
                                <Route path="/reports" element={<ReportsPage user={user} apiUrl={API_BASE_URL} />} />
                                <Route path="/expenses" element={<ExpensesPage user={user} apiUrl={API_BASE_URL} />} />
                                <Route path="/notifications" element={<NotificationsPage user={user} apiUrl={API_BASE_URL} setNotificationCount={setNotificationCount} />} />
                                <Route path="*" element={<Navigate to="/inventory" />} />
                            </Routes>
                        </main>
                    </div>
                </div>
            )}
        </Router>
    );
}

export default App;