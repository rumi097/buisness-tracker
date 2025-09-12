import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate, Link } from 'react-router-dom';

function LoginPage({ setUser }) { // Removed unused apiUrl prop
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleLogin = async (e) => {
        e.preventDefault();
        setError(''); // Clear previous errors

        // Get the live API URL from the environment variable set in Vercel
        const liveApiUrl = process.env.REACT_APP_API_URL;

        // Check to ensure the environment variable is loaded
        if (!liveApiUrl) {
            setError("API URL is not configured. Please contact support.");
            return;
        }

        try {
            const response = await axios.post(`${liveApiUrl}/auth.php?action=login`, { username, password });
            const userData = { id: response.data.user_id, username, store_name: response.data.store_name };
            localStorage.setItem('user', JSON.stringify(userData));
            setUser(userData);
            navigate('/inventory');
        } catch (err) {
            setError('Invalid username or password.');
        }
    };

    return (
        <div className="auth-container">
            <div className="auth-animation-section">
                <svg viewBox="0 0 200 200" className="auth-svg-animation">
                    {/* Scene 1: Dashboard */}
                    <g id="dashboard-art">
                        <rect x="20" y="40" width="160" height="120" rx="10" fill="#1e1e1e" stroke="#a8d8ea" strokeWidth="2"/>
                        <path d="M 40 120 Q 60 90, 80 105 T 120 100 T 160 130" stroke="#a8e6cf" strokeWidth="3" fill="none" />
                        <rect x="50" y="70" width="10" height="25" fill="#a8d8ea" rx="2"/>
                        <rect x="70" y="60" width="10" height="35" fill="#a8d8ea" rx="2"/>
                        <rect x="90" y="80" width="10" height="15" fill="#a8d8ea" rx="2"/>
                        <path d="M 130 65 l 10 10" stroke="#a8e6cf" strokeWidth="3" strokeLinecap="round"/>
                        <path d="M 130 75 l -5 -5" stroke="#a8e6cf" strokeWidth="3" strokeLinecap="round"/>
                    </g>
                    {/* Scene 2: Security */}
                    <g id="security-art">
                        <path d="M 60 100 v -20 a 40 40 0 0 1 80 0 v 20 h 10 v 50 a 10 10 0 0 1 -10 10 h -80 a 10 10 0 0 1 -10 -10 v -50 z" fill="#1e1e1e" stroke="#a8d8ea" strokeWidth="2" />
                        <circle cx="100" cy="125" r="10" fill="#a8e6cf"/>
                        <path d="M 90 145 a 10 10 0 0 1 20 0" stroke="#a8e6cf" strokeWidth="2" fill="none"/>
                    </g>
                </svg>
            </div>
            <div className="auth-form-section">
                <div className="auth-card">
                    <h3 className="text-center mb-4">Store Login</h3>
                    {error && <div className="alert alert-danger">{error}</div>}
                    <form onSubmit={handleLogin}>
                        <div className="mb-3">
                            <label className="form-label">Username</label>
                            <input type="text" value={username} onChange={(e) => setUsername(e.target.value)} className="form-control" required />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Password</label>
                            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="form-control" required />
                        </div>
                        <button type="submit" className="btn btn-primary w-100 mt-3">Login</button>
                    </form>
                    <p className="text-center mt-4 text-muted">
                        Don't have an account? <Link to="/register">Register here</Link>
                    </p>
                </div>
            </div>
        </div>
    );
}

export default LoginPage;