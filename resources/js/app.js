import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import NotificationBell from '../../src/components/NotificationBell.jsx';

const notificationRoot = document.getElementById('notification-bell-root');

if (notificationRoot) {
	createRoot(notificationRoot).render(React.createElement(NotificationBell, {
		userId: notificationRoot.dataset.userId,
	}));
}
